<?php

namespace CoreOpenApi\Protocol;

use Exception;
use CoreOpenApi\Api\RequestService;
use CoreOpenApi\Config\Config;

/**
 * Class CoreClient
 */
abstract class CoreClient
{
    protected $token;
    protected $requestUrl;

    /**
     * @var Config
     */
    protected $config;
    private $logger;

    protected $connectTimeout = 3000;
    protected $readTimeout = 60000;

    protected $charset = "UTF-8";
    protected $apiVersion = "2.0";
    protected $sdkVersion = "20180628";

    protected $requestService;

    protected $freshedToken = false;//是否刷新了token
    protected $autoFreshTokenResp;//刷新token相应
    protected $recallTimes = 0;// recall次数
    protected $recallMaxTimes = 3;//最多recall 3次

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

        if ($postFields)
        {
            if (!isset($curlOptions[CURLOPT_HTTPHEADER]))
            {
                $header = array('content-type: application/x-www-form-urlencoded; charset=UTF-8');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                if (is_array($postFields))
                {
                    $postFields = http_build_query($postFields, '', '&');
                }
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

    /**
     * 获得token
     * @TODO   这个需要根据不同的api 实现不同的构建方法
     *
     * @param $params
     *
     * @return ['token' => $res, 'response' => $res]
     */
    public abstract function getToken($params);

    /**
     * 刷新token
     * @TODO 这个需要根据不同的api 实现不同的构建方法
     *
     * @param $params
     *
     * @return mixed
     */
    public abstract function refreshToken($params);

    /**
     * @TODO 这个需要根据不同的api 实现不同的构建方法
     *
     * @param      $action
     * @param null $params
     *
     * @return string
     */
    public abstract function getRequestUri($action, $params = null);

    /**
     * 获得 curl option相关配置
     *
     * @param $method
     *
     * @return array
     */
    public function getCurlOption($method)
    {
        $curlOption = $this->config->getParamByKey('curlOption');
        if (isset($curlOption[$method]))
        {
            return $curlOption[$method];
        }
        else if (isset($curlOption['default']))
        {
            return $curlOption['default'];
        }

        return [];
    }

    /**
     * @param RequestService $request
     *
     * @return array
     */
    public function execute(RequestService $request)
    {
        $this->requestService = $request;
        try
        {
            $request->check();
        } catch (Exception $e)
        {
            return ['errCode' => $e->getCode(), 'errMsg' => $e->getMessage(), 'data' => get_object_vars($e)];
        }
        $requestParams = $this->buildRequestParams($request->getParams());
        try
        {
            $requestUrl  = $this->getRequestUri($request->getAction(), $requestParams);
            $curlOptions = $this->getCurlOption($request->getAction());
            $resp        = $this->doRequest($requestUrl, $requestParams, $curlOptions);
            if ($this->logger != null)
            {
                $this->logger->info("request url: " . $requestUrl);
                $this->logger->info("request data: " . json_encode($requestParams, JSON_UNESCAPED_UNICODE));
                $this->logger->info("response data: " . $resp);
            }
        } catch (Exception $e)
        {
            return ['errCode' => $e->getCode(), 'errMsg' => $e->getMessage(), 'data' => get_object_vars($e)];
        }

        $resp = $this->onResponse($resp);
        if ($this->isTokenExpired($resp))
        {
            return $this->recall($resp);
        }

        if ($this->freshedToken)
        {
            $resp['autoFreshTokenResp'] = $this->autoFreshTokenResp;
        }

        return $resp;
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

    /**
     * 是否为token过期。
     *
     * @param $resp
     *
     * @return mixed
     */
    public abstract function isTokenExpired($resp);

    /**
     * 重新请求
     *
     * @param $respObject
     *
     * @return mixed
     */
    public function recall($respObject)
    {
        if ($this->recallTimes < $this->recallMaxTimes)
        {
            $this->recallTimes++;
            $token = $this->refreshToken($this->config);
            if (isset($token['token']) && $token['token'])
            {
                $this->token              = $token['token'];
                $this->autoFreshTokenResp = $token['response'];
                $this->freshedToken       = true;
                return $this->execute($this->requestService);
            }
        }

        return $respObject;
    }

    /**
     * 根据alias获得真实的method
     *
     * @param $alias
     *
     * @return mixed
     */
    public function getMethodByAlias($alias)
    {
        return $this->config->getMethodByAlias($alias);
    }
}
