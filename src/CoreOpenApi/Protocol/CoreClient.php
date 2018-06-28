<?php

namespace CoreOpenApi\Protocol;

use Exception;
use CoreOpenApi\Api\RequestService;
use CoreOpenApi\Config\Config;
use stdClass;

/**
 * Class CoreClient
 */
abstract class CoreClient
{
    public $token;
    public $requestUrl;

    /**
     * @var Config
     */
    private $config;
    private $logger;

    protected $connectTimeout = 3000;
    protected $readTimeout = 60000;

    protected $charset = "UTF-8";
    protected $apiVersion = "2.0";
    protected $sdkVersion = "20180628";

    public function __construct($token, Config $config)
    {
        $this->appKey     = $config->getAppKey();
        $this->appSecret  = $config->getAppSecret();
        $this->requestUrl = $config->getRequestUrl();
        $this->logger     = $config->getLogger();
        $this->token      = $token;
        $this->config     = $config;
    }

    /**
     * 根据构造好的参数请求api
     *
     * @param string      $url         请求地址
     * @param array|mixed $postFields
     * @param array       $curlOptions curl option参数
     *
     * @return mixed
     * @throws Exception
     */
    public function doRequest($url, $postFields = null, $curlOptions = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->readTimeout)
        {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }
        if ($this->connectTimeout)
        {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }

        if (stripos($url, 'https') === 0) //https请求
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($postFields) && $postFields)
        {
            if (!isset($curlOptions[CURLOPT_HTTPHEADER]))
            {
                $header = array('content-type: application/x-www-form-urlencoded; charset=UTF-8');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $postFields = http_build_query($postFields, '', '&');
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }

        if ($curlOptions && is_array($curlOptions))
        {
            curl_setopt_array($ch, $curlOptions);
        }

        $reponse = curl_exec($ch);

        if (curl_errno($ch))
        {
            throw new Exception(curl_error($ch), 0);
        }
        else
        {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode)
            {
                throw new Exception($reponse, $httpStatusCode);
            }
        }
        curl_close($ch);

        return $reponse;
    }

    /**
     * @TODO 这个需要根据不同的api 实现不同的构建方法
     * 构建请求数据
     */
    public abstract function buildRequestParams($params);

    public function getRequestUri($action)
    {
        return $this->requestUrl . $action;
    }

    /**
     * @param RequestService $request
     *
     * @return stdClass
     * @throws Exception
     */
    public function execute(RequestService $request)
    {
        try
        {
            $request->check();
        } catch (Exception $e)
        {
            $result          = new StdClass();
            $result->code    = $e->getCode();
            $result->message = $e->getMessage();

            return $result;
        }
        $requestParams = $this->buildRequestParams($request->getParams());
        try
        {
            $requestUrl = $this->getRequestUri($request->getAction());
            $resp       = $this->doRequest($requestUrl, $requestParams);
            if ($this->logger != null)
            {
                $this->logger->info("request data: " . json_encode($requestParams, JSON_UNESCAPED_UNICODE));
                $this->logger->info("response data: " . $resp);
            }
        } catch (Exception $e)
        {
            throw $e;
        }

        return $this->onResponse($resp);
    }

    /**
     * @todo 返回解析 需要根据不同的情况进行解析
     *
     * @param $resp
     *
     * @return mixed
     * @throws Exception
     */
    public abstract function onResponse($resp);
}
