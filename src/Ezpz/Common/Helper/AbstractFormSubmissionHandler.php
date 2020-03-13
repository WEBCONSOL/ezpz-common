<?php

namespace Ezpz\Common\Helper;

use \Ezpz\Common\ApiGateway\Endpoints;
use \Ezpz\Common\KafkaService\Constants;
use \Ezpz\Common\KafkaService\Producer;
use \Ezpz\Common\OAuth2Server\AccessTokenValidator;
use \Ezpz\Common\Utilities\HostNames;
use \Ezpz\Common\Utilities\HttpClient;
use WC\Models\ListModel;
use WC\Models\UserModel;
use \Ezpz\Common\Security\Jwt;
use WC\Utilities\CustomResponse;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use WC\Utilities\DateTimeFormat;
use WC\Utilities\Logger;

abstract class AbstractFormSubmissionHandler extends AbstractApiContextProcessor
{
    /**
     * @var Connection $conn
     */
    private $conn;
    /**
     * @var string|int $editId
     */
    private $editId;
    /**
     * @var bool $exists
     */
    private $exists = false;
    /**
     * @var bool $success
     */
    private $success = false;
    /**
     * @var int $insertId
     */
    private $insertId = 0;
    /**
     * @var UserModel $userData
     */
    private $userData;
    /**
     * @var string $kafkaKey
     */
    protected $kafkaKey = '';

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
        $this->conn = $this->em->getConnection();
        $token = $this->request->getHeaderLine(HEADER_ACCESS_TOKEN);
        $this->userData = $this->getUserData($token);

        $this->prepareData();

        $this->editId = (int)$this->request->getParam('edit_id', '0');
        if ($this->editId <= 0) {
            if ($this->request->hasRequestParam('editid')) {
                $this->editId = (int)$this->request->getParam('editid', '0');
            }
            else if ($this->request->hasRequestParam('id')) {
                $this->editId = (int)$this->request->getParam('id', '0');
            }
        }
    }

    protected final function setEditId(int $id) {$this->editId = $id;}

    protected abstract function prepareData();
    public abstract function validRequiredParams(): bool;
    public abstract function getMainQueryForProcessRequest(): Query;
    public abstract function onBeforeProcessRequest();
    public abstract function onAfterProcessRequest();

    protected function dataForKafka(): array {return [];}

    public final function getEditId() {return $this->editId;}
    public final function getInsertId() {return $this->insertId;}
    public final function getDbResource(): Connection {return $this->conn;}

    protected final function processRequest() {

        if (!$this->validRequiredParams()) {
            CustomResponse::render(405, 'Bad form submission: required data missing');
        }
        else {
            $this->onBeforeProcessRequest();

            if (strlen($this->getMainQueryForProcessRequest()->getQueryString())) {
                if ($this->editId) {
                    $this->success = $this->executeQuery($this->getMainQueryForProcessRequest()->getQueryString());
                    $this->setResponseData(['id'=>$this->editId]);
                }
                else if ($this->exists) {
                    $this->setResponseStatus(false);
                    $this->setResponseStatusCode(500);
                    $this->setResponseMessage('Item already exists.');
                }
                else {
                    $this->success = $this->insert($this->getMainQueryForProcessRequest());
                    if (!$this->success) {
                        $this->setResponseStatus(false);
                        $this->setResponseStatusCode(500);
                        $this->setResponseMessage('Failed to add new record.');
                        $this->result = [];
                    }
                    else {
                        $this->setResponseData(['id'=>$this->insertId]);
                    }
                }
            }

            $this->onAfterProcessRequest();

            // kafka producer invocation goes here
            if (KAFKA_INTEGRATED && $this->kafkaKey) {
                $this->sendKafkaTopic();
            }

            Query::appendToResponseResult($this->result);
        }
    }

    protected final function setQueryForExistenceValidation(string $tb, string $condition) {
        $result = $this->fetchRow('SELECT * FROM '.$tb.' WHERE '.$condition);
        if (!empty($result)) {
            $this->exists = true;
        }
    }

    protected final function defaultQuery(string $tb): Query {
        $columns = $this->getTableColumns($tb);
        $values = array();
        if ($this->getEditId()) {
            foreach ($columns as $column) {
                if ($this->request->hasRequestParam($column)) {
                    $param = $this->request->getRequestParam($column);
                    $values[] = $this->quoteName($column) . '=' . $this->quote(is_array($param)||is_object($param)?json_encode($param):$param);
                }
                else if ($column === 'modified_on') {
                    $values[] = $this->quoteName($column) . '=' . $this->quote(DateTimeFormat::getFormatUnix());
                }
                else if ($column === 'modified_by') {
                    $id = $this->userData->getId() ;
                    if($id === null) {
                        $id = '0';
                    }
                    $values[] = $this->quoteName($column) . '=' . $this->quote($id);
                }
            }
            $sql = 'UPDATE '.$tb.' SET ' . implode(',', $values) . ' WHERE id=' . $this->getEditId();
        }
        else {
            $fields = array();
            foreach ($columns as $column) {
                if ($this->request->hasRequestParam($column)) {
                    $param = $this->request->getRequestParam($column);
                    $values[] = $this->quote(is_array($param)||is_object($param)?json_encode($param):$param);
                    $fields[] = $this->quoteName($column);
                }
                else if ($column === 'modified_on' || $column === 'created_on') {
                    $values[] = $this->quote(DateTimeFormat::getFormatUnix());
                    $fields[] = $this->quoteName($column);
                }
                else if ($column === 'modified_by' || $column === 'created_by') {
                    $id = $this->userData->getId() ;
                    if($id === null) {
                        $id = '0';
                    }
                    $values[] = $this->quote($id);
                    $fields[] = $this->quoteName($column);
                }

            }
            $sql = 'INSERT INTO '.$tb.'('.implode(',', $fields).') VALUES('.implode(',', $values).')';
        }
        return new Query($sql);
    }

    protected final function populateDataToTable(string $tb, ListModel $params, string $condition=''): bool {
        $columns = $this->getTableColumns($tb);
        $values = array();
        $paramsAsArray = $params->getAsArray();
        $sql = '';
        if (strlen($condition)) {
            if (is_array($paramsAsArray) && !isset($paramsAsArray[0])) {
                foreach ($columns as $column) {
                    if ($params->has($column)) {
                        $param = $params->get($column);
                        $values[] = $this->quoteName($column) . '=' . $this->quote(is_array($param)||is_object($param)?json_encode($param):$param);
                    }
                    else if ($column === 'modified_on') {
                        $values[] = $this->quoteName($column) . '=' . $this->quote(DateTimeFormat::getFormatUnix());
                    }
                    else if ($column === 'modified_by') {
                        $id = $this->userData->getId() ;
                        if($id === null) {
                            $id = '0';
                        }
                        $values[] = $this->quoteName($column) . '=' . $this->quote($id);
                    }
                }
                $sql = 'UPDATE '.$tb.' SET ' . implode(',', $values) . ' WHERE ' . $condition;
            }
            else {
                CustomResponse::render(500, 'Invalid params passed to populateDataToTable', false, []);
            }
        }
        else {
            $fields = array();
            $values = array();
            // if params is [[key:value]] (2 dimensions array)
            if (isset($paramsAsArray[0])) {
                foreach ($paramsAsArray as $item) {
                    if (is_array($item)) {
                        $params = new ListModel($item);
                        $innerValues = [];
                        foreach ($columns as $column) {
                            if ($params->has($column)) {
                                $param = $params->get($column);
                                $innerValues[] = $this->quote(is_array($param)||is_object($param)?json_encode($param):$param);
                                if (!in_array($this->quoteName($column), $fields)) {
                                    $fields[] = $this->quoteName($column);
                                }
                            }
                            else if ($column === 'modified_on' || $column === 'created_on') {
                                $innerValues[] = $this->quote(DateTimeFormat::getFormatUnix());
                                if (!in_array($this->quoteName($column), $fields)) {
                                    $fields[] = $this->quoteName($column);
                                }
                            }
                            else if ($column === 'modified_by' || $column === 'created_by') {
                                $id = $this->userData->getId() ;
                                if($id === null) {
                                    $id = '0';
                                }
                                $innerValues[] = $this->quote($id);
                                if (!in_array($this->quoteName($column), $fields)) {
                                    $fields[] = $this->quoteName($column);
                                }
                            }
                        }
                        $values[] = '('.implode(',', $innerValues).')';
                    }
                }
                $sql = 'INSERT INTO '.$tb.'('.implode(',', $fields).') VALUES'.implode(',', $values);
            }
            else {
                // if params is [key:value] (1 dimension array)
                foreach ($columns as $column) {
                    if ($params->has($column)) {
                        $param = $params->get($column);
                        $values[] = $this->quote(is_array($param)||is_object($param)?json_encode($param):$param);
                        $fields[] = $this->quoteName($column);
                    }
                    else if ($column === 'modified_on' || $column === 'created_on') {
                        $values[] = $this->quote(DateTimeFormat::getFormatUnix());
                        $fields[] = $this->quoteName($column);
                    }
                    else if ($column === 'modified_by' || $column === 'created_by') {
                        $id = $this->userData->getId() ;
                        if($id === null) {
                            $id = '0';
                        }
                        $values[] = $this->quote($id);
                        $fields[] = $this->quoteName($column);
                    }
                }
                $sql = 'INSERT INTO '.$tb.'('.implode(',', $fields).') VALUES('.implode(',', $values).')';
            }
        }
        if ($sql) {
            return $this->executeQuery($sql);
        }
        return false;
    }

    protected final function insert(Query $executeQuery): bool {
        $inserted = $this->executeQuery($executeQuery->getQueryString());
        if ($inserted) {
            $this->insertId = $this->conn->lastInsertId();
        }
        return $inserted;
    }

    protected final function exists(): bool {return $this->exists;}

    protected final function isSuccess(): bool {return $this->success;}

    protected final function getUserData(string $token): UserModel {
        $uri = Endpoints::auth('userDetailsBy', array('by' => EZPZ_USERNAME));
        $options = [];
        $options['headers'] = array(HEADER_ACCESS_TOKEN => $token);
        $url = HostNames::getAuth() . $uri[1];
        $subReqResponse = HttpClient::request($uri[0], $url, $options);
        $json = json_decode($subReqResponse->getBody(), true);
        $json = isset($json['data']) ? $json['data'] : [];
        return new UserModel(!empty($json) ? $json : []);
    }

    protected function sendKafkaTopic() {
        // Apache Kafka
        $data = $this->dataForKafka();
        if (empty($data) && !empty($this->result)) {$data = $this->result;}
        if(!empty($data)) {
            $producer = new Producer();
            $producer->setTopic(Constants::KAFKA_TOPIC_UPDATE_CLIENT);
            $producer->setKey($this->kafkaKey);
            if (!isset($data['action'])) {
                $data['action'] = $this->getInsertId() ? Constants::KAFKA_ACTION_ADD : Constants::KAFKA_ACTION_UPDATE;
                $accessToken = AccessTokenValidator::getAccessToken();
                $appName = AccessTokenValidator::getAppName();
                if ($accessToken && $appName) {
                    $uri = Endpoints::config('appIdForKafka', ['access_token'=>$accessToken, 'hashed_app_name'=>md5($appName)]);
                    $url = HostNames::getConfig() . $uri[1];
                    $subReqResponse = HttpClient::request($uri[0], $url, []);
                    $json = json_decode($subReqResponse->getBody(), true);
                    $data['app'] = Jwt::encryptData(isset($json['data']) && !empty($json['data']) ? $json['data'] : '');
                }
            }
            $producer->setValue([Constants::KAFKA_DATA_KEY_DATA=>$data]);
            $producer->send();
        }
    }
}