<?php

namespace CoreOpenApi\Api;

use CoreOpenApi\Protocol\CoreClient;
use CoreOpenApi\VO\BaseVO;

class RequestService
{
    /**
     * @var CoreClient
     */
    protected $client;
    protected $action = '';
    protected $params;
    protected $rows_num = "50";
    protected $error;

    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * 检测数据
     * @return bool
     */
    public function check(BaseVO $vo)
    {
        if (!$vo->check())
        {
            $this->error = $vo->getError();

            return false;
        }

        return true;
    }

    /**
     * 获得当前的aciton
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 获得请求参数
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 调用远程api
     *
     * @param string $action
     * @param BaseVO $vo
     *
     * @return mixed
     */
    public function call($action = '', BaseVO $vo)
    {
        $this->action = $action;
        if ($this->check($vo))
        {
            $this->params = $vo->getData();

            return $this->formatResponse($this->client->execute($this));
        }
        else
        {
            return ['errorCode' => '-999', 'errMsg' => 'invalid data', 'data' => $this->getError()];
        }
    }


    /**
     * 格式化response
     *
     * @param $response
     *
     * @return mixed
     */
    public function formatResponse($response)
    {
        return $response;
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
        return $this->client->getMethodByAlias($alias);
    }

    /**
     * 获得token
     * @return mixed
     */
    public function getToken($params)
    {
        return $this->client->getToken($params);
    }

    /**
     * 刷新token
     * @return mixed
     */
    public function refreshToken($params)
    {
        return $this->client->refreshToken($params);
    }
}