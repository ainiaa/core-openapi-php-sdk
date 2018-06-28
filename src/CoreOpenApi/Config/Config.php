<?php

namespace CoreOpenApi\Config;

use Exception;

class Config
{
    private $appKey = '';
    private $appSecret = '';
    private $extParams = []; //扩展参数
    private $requestUrl = '';

    private $logger;

    /**
     * Config constructor.
     *
     * @param       $appKey
     * @param       $appSecret
     * @param array $extParams
     *
     * @throws Exception
     */
    public function __construct($appKey, $appSecret, $extParams = [])
    {
        if (empty($appKey))
        {
            throw new Exception('app_key is required');
        }

        if (empty($appSecret))
        {
            throw new Exception('app_secret is required');
        }

        $this->appKey    = $appKey;
        $this->appSecret = $appSecret;
        $this->extParams = $extParams;
    }

    public function getAppKey()
    {
        return $this->appKey;
    }

    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * 获得所有的配置信息
     * @return array
     */
    public function getExtParams()
    {
        return $this->extParams;
    }

    /**
     * 根据key获得对应的配置信息
     *
     * @param $key
     *
     * @return mixed|null
     */
    public function getParamByKey($key)
    {
        if (isset($this->extParams[$key]))
        {
            return $this->extParams[$key];
        }

        return null;
    }

    /**
     * 获得请求地址
     * @return mixed|null|string
     */
    public function getRequestUrl()
    {
        if (empty($this->requestUrl))
        {
            return $this->getParamByKey('requestUrl');
        }

        return $this->requestUrl;
    }

    /**
     * 设置请求地址
     *
     * @param $requestUrl
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
    }

    /**
     * 获得logger对象
     * @return mixed
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * 设置 logger 对象
     *
     * @param $logger
     *
     * @throws Exception
     */
    public function setLogger($logger)
    {
        if (!method_exists($logger, 'info'))
        {
            throw new Exception('logger need have method "info($message)"');
        }
        if (!method_exists($logger, 'error'))
        {
            throw new Exception('logger need have method "error($message)"');
        }
        $this->logger = $logger;
    }
}