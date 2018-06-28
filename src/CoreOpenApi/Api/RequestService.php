<?php

namespace CoreOpenApi\Api;

use CoreOpenApi\Config\Config;
use CoreOpenApi\Protocol\CoreClient;

class RequestService
{
    /**
     * @var CoreClient
     */
    protected $client;
    protected $action = '';
    protected $params = array();
    protected $rows_num = "50";

    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * 检测数据
     * todo 这个需要根据具体的业务逻辑来实现
     * @return bool
     */
    public function check()
    {
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
     * @param array  $params
     *
     * @return mixed
     */
    public function call($action = '', $params = [])
    {
        $this->action = $action;
        $this->params = $params;

        return $this->formatResponse($this->client->execute($this));
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

}