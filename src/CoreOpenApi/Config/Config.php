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
            throw new Exception("app_key is required");
        }

        if (empty($appSecret))
        {
            throw new Exception("app_secret is required");
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

    public function getExtParams()
    {
        return $this->extParams;
    }

    public function getParamByKey($key)
    {
        if (isset($this->extParams[$key]))
        {
            return $this->extParams[$key];
        }

        return null;
    }

    public function getRequestUrl()
    {
        if (empty($this->requestUrl))
        {
            return $this->getParamByKey('requestUrl');
        }

        return $this->requestUrl;
    }

    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
    }

    public function getLogger()
    {
        return $this->logger;
    }

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