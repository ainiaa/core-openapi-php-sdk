<?php

namespace CoreOpenApi\Config;

use Exception;

class Config
{
    private $appKey = '';
    private $appSecret = '';
    private $extParams = []; //扩展参数
    private $requestUrl = '';
    private $methodList = [];

    private $logger;

    /**
     * Config constructor.
     *
     * @param array $config
     *            $extParams = [
     *            'appKey' => 'required',//appkey  $this->getAppKey() //来获取
     *            'appSecret' => 'required',//app secret $this->getAppSecret() //来获取
     *            'methodList' => [ //required
     *            'alias1' => 'real api method1',
     *            'alias2' => 'real api method2',
     *            ...
     *            ],
     *            'extParams' => [ //optional   通过 $this->getParamByKey($key) //来获取
     *            'key' => 'value',
     *            'key2' => 'value',
     *            ],
     *            ]
     *
     * @throws Exception
     */
    public function __construct($config)
    {
        if (!isset($config['appKey']) || empty($config['appKey']))
        {
            throw new Exception('appKey is required');
        }

        if (!isset($config['appSecret']) || empty($config['appSecret']))
        {
            throw new Exception('appSecret is required');
        }

        if (!isset($config['methodList']) || empty($config['methodList']))
        {
            throw new Exception('methodList is required');
        }

        $this->appKey     = $config['appKey'];
        $this->appSecret  = $config['appSecret'];
        $this->methodList = $config['methodList'];
        if (isset($config['extParams']) && is_array($config['extParams']))
        {
            $this->extParams = $config['extParams'];
        }
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
     * @param $key
     *
     * @return mixed
     * @throws Exception
     */
    public function getMethodByAlias($key)
    {
        if (isset($this->methodList[$key]))
        {
            return $this->methodList[$key];
        }
        else
        {
            throw new Exception($key . 'method not found.');
        }
    }

    /**
     * 获取method列表
     * @return array
     */
    public function getMethodList()
    {
        return $this->methodList;
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