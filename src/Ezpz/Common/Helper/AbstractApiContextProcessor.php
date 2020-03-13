<?php

namespace Ezpz\Common\Helper;

use Doctrine\ORM\EntityManager;
use Ezpz\Common\Repository\DataService;
use Ezpz\Common\Utilities\Envariable;
use Ezpz\Common\Utilities\Request;
use Ezpz\Common\Security\Jwt;
use Ezpz\Common\Security\Token;
use WC\Utilities\CustomResponse;
use WC\Utilities\Logger;

abstract class AbstractApiContextProcessor
{
    /**
     * @var EntityManager $em
     */
    protected $em;
    /**
     * @var Request $request
     */
    protected $request;
    /**
     * @var array $result
     */
    protected $result = [];
    /**
     * @var array $response
     */
    private $response = ['success'=>true,'statusCode'=>200,'message'=>'Successfully process.','data'=>[]];
    /**
     * @var Token $jwtToken
     */
    protected $jwtToken;
    /**
     * @var DataService
     */
    protected $dataService;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->request = new Request();
        $this->dataService = new DataService($this->em);
    }

    protected final function getResponse(): array {
        $this->response['data'] = $this->result;
        return $this->response;
    }

    protected final function executeQuery(string $query): bool {return $this->dataService->executeQuery($query);}

    protected final function fetchRow(string $query): array {return $this->dataService->fetchRow($query);}

    protected final function fetchRows(string $query): array {return $this->dataService->fetchRows($query);}

    protected final function jsonDecode(array &$arr) {$this->dataService->decode($arr);}

    protected final function getTableColumns(string $tableName): array {
        $rows = $this->fetchRows('DESCRIBE ' . $this->quoteName($tableName));
        if (!empty($rows)) {
            $newRows = array();
            foreach ($rows as $i=>$row) {
                $newRows[] = $row['Field'];
            }
            return $newRows;
        }
        return [];
    }

    public final function quote($str): string {return $this->em->getConnection()->quote($str);}

    public final function quoteName($str): string {return $this->em->getConnection()->quoteIdentifier($str);}

    protected final function getResponseSuccess(): bool {return $this->response['success'];}
    protected final function setResponseStatus(bool $a) {$this->response['success']=$a;}
    protected final function setResponseStatusCode(int $a) {$this->response['statusCode']=$a;}
    protected final function setResponseMessage(string $a) {$this->response['message']=$a;}
    protected final function setResponseData(array $a) {$this->result=$a;}
    protected final function isResponseDebug() {$this->response['debug']=true;}
    public final function setResponseElement(string $key, $value) {$this->response[$key] = $value;}

    protected final function validateJwtTokenOnRequestForAccessToken() {
        if (!Envariable::isPostmanMode()) {
            $this->jwtToken = $this->request->getHeaderLine(Jwt::HEADER_TOKEN_NAME);
            if (!$this->jwtToken) {
                Logger::error('jwt_token. Invalid JWT-Token data (0)');
                CustomResponse::render(500, 'Invalid JWT-Token data (0)');
            }
            $this->jwtToken = Jwt::decryptToken($this->jwtToken);
            $keys = $this->dataService->getOauthPublicKeys($this->jwtToken);
            if (empty($keys)) {
                Logger::error('jwt_token. Invalid JWT-Token data (1)');
                CustomResponse::render(500, 'Invalid JWT-Token data (1)');
            }
            if (!Jwt::verifyClientTokenForAccessTokenRequest($this->jwtToken, $keys['private_key'], $keys['app_name'])) {
                Logger::error('jwt_token. Invalid JWT-Token data (2)');
                CustomResponse::render(500, 'Invalid JWT-Token data (2)');
            }
        }
        else if ($this->request->getHeaderLine('App-Name')) {
            $this->jwtToken = new Token();
            $this->jwtToken->appName = $this->request->getHeaderLine('App-Name');
        }
    }

    protected final function pagination() {
        if (isset($this->result[0])) {
            $numPerPage = $this->request->getParam('numPerPage', Query::getNumPerPage());
            if ($numPerPage !== 'all') {
                $size = sizeof(isset($list['data'])?$this->result['data']:$this->result);
                $numRows = $size ? ($size > $numPerPage ? $size-1 : $size) : Query::getNumRows();
                $prev = Query::getPage() - 1;
                $next = $size > $numPerPage ? Query::getPage() + 1 : 0;
                $this->response['pagination'] = [
                    'prev' => $prev < 0 ? 0 : $prev,
                    'current' => Query::getPage(),
                    'next' => $next,
                    'numRows' => $numRows,
                    'numPerPage' => $numPerPage
                ];
                if ($next > 0) {
                    unset($this->result[$size-1]);
                }
            }
            else {
                $this->response['pagination'] = [
                    'prev' => 0,
                    'current' => Query::getPage(),
                    'next' => 0,
                    'numRows' => Query::getNumRows(),
                    'numPerPage' => $numPerPage
                ];
            }
        }
    }
}