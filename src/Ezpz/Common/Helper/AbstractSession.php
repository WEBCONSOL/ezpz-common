<?php

namespace Ezpz\Common\Helper;

use \Ezpz\Common\ApiGateway\Endpoints;
use Doctrine\ORM\EntityManager;
use \Ezpz\Common\Utilities\Envariable;
use Slim\Http;
use \Ezpz\Common\Utilities\HostNames;
use \Ezpz\Common\Utilities\HttpClient;
use WC\Models\ListModel;
use WC\Models\SessionModel;
use \Ezpz\Common\Security\Jwt;
use WC\Utilities\DateTimeFormat;
use WC\Utilities\EncodingUtil;

abstract class AbstractSession extends AbstractApiContextProcessor
{
    protected $tb = 'sessions';
    protected $clientSID = '';
    protected $key = '';
    /**
     * @var ListModel $uriParams
     */
    protected $uriParams;
    /**
     * @var SessionModel $sessionData
     */
    private $sessionData;

    private $jwtAccessToken = '';
    private $userId = '';
    private $accessToken = '';

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);

        $this->sessionData = new SessionModel([]);
        $this->jwtAccessToken = $this->request->getHeaderLine(HEADER_ACCESS_TOKEN);
        $this->userId = $this->request->getHeaderLine(HEADER_USER_ID);
    }

    abstract function exec();

    public function __invoke(Http\Request $request, Http\Response $response, array $uriParams): Http\Response
    {
        $this->uriParams = new ListModel($uriParams);
        if ($this->uriParams->has('client_sid')) {$this->clientSID = $this->uriParams->get('client_sid');}
        if ($this->uriParams->has('key')) {$this->key = $this->uriParams->get('key');}
        $this->processRequest();
        return $response->withJson($this->getResponse());
    }

    protected final function getSessionData(): SessionModel {return $this->sessionData;}

    protected final function update(string $data) {
        $sql = 'UPDATE '.$this->tb.' SET content='.$this->quote($data).
            ' WHERE id='.$this->quote($this->clientSID).' AND access_token='.$this->quote($this->accessToken);
        $this->executeQuery($sql);
    }

    protected final function destroy() {
        $sql = 'DELETE FROM '.$this->tb.' WHERE id='.$this->quote($this->clientSID).' AND access_token='.$this->quote($this->accessToken);
        $this->executeQuery($sql);
    }

    private function processRequest()
    {
        if ($this->jwtAccessToken && $this->clientSID)
        {
            if (Envariable::isPostmanMode()) {
                $this->accessToken = $this->jwtAccessToken;
            }
            else {
                $jwtToken = Jwt::decryptToken($this->jwtAccessToken);
                $this->accessToken = $jwtToken->access_token;
            }
            $this->load();
            if (!$this->sessionData->isValid()) {$this->start();}
            $this->exec();
        }
    }

    private function load()
    {
        $sql = 'SELECT * FROM '.$this->tb.' WHERE id='.$this->quote($this->clientSID).' AND access_token='.$this->quote($this->accessToken);
        $this->sessionData = new SessionModel($this->fetchRow($sql));
    }

    private function start() {
        $token = $this->getTokenDetails();
        if (!empty($token)) {
            $token = isset($token['data']) ? $token['data'] : [];
            if (!empty($token)) {
                $sql = 'INSERT INTO '.$this->tb.'(id,access_token,user_id,created_on,expired_on,content) VALUES('.
                    $this->quote($this->clientSID).','.
                    $this->quote($this->accessToken).','.
                    $this->quote('0').','.
                    $this->quote(DateTimeFormat::getFormatUnix()).','.
                    $this->quote(strtotime($token['expires'])).','.
                    $this->quote('').
                    ')';
                $this->executeQuery($sql);
            }
            else {
                $this->setResponseStatus(false);
                $this->setResponseStatusCode(500);
                $this->setResponseMessage('invalid_token_data_in_session');
            }
        }
        else {
            $this->setResponseStatus(false);
            $this->setResponseStatusCode(500);
            $this->setResponseMessage('invalid_token_in_session');
        }
    }

    private function getTokenDetails(): array
    {
        $endpoint = Endpoints::auth('tokenDetails', ['token' => $this->accessToken]);
        if (!empty($endpoint)) {
            $options = [
                'headers' => [
                    HEADER_USER_NAME => EZPZ_USERNAME,
                    HEADER_ACCESS_TOKEN => $this->request->getHeaderLine(HEADER_ACCESS_TOKEN)
                ]
            ];
            $method = $endpoint[0];
            $url = HostNames::getAuth() . $endpoint[1];
            $resp = HttpClient::request($method, $url, $options);
            if ($resp->getStatusCode() === 200) {
                $content = $resp->getBody()->getContents();
                if (EncodingUtil::isValidJSON($content)) {
                    return json_decode($content, true);
                }
            }
        }
        return [];
    }

    private function getUserId(string $username)
    {
        $endpoint = Endpoints::auth('userDetailsBy', ['by' => $username]);
        if (!empty($endpoint)) {
            $options = [
                'headers' => [
                    HEADER_USER_NAME => EZPZ_USERNAME,
                    HEADER_ACCESS_TOKEN => $this->request->getHeaderLine(HEADER_ACCESS_TOKEN)
                ]
            ];
            $method = $endpoint[0];
            $url = HostNames::getAuth() . $endpoint[1];
            $resp = HttpClient::request($method, $url, $options);
        }
    }
}