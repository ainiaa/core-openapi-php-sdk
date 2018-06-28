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

    public function check()
    {
        return true;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function call($action = '', $params = [])
    {
        $this->action = $action;
        $this->params = $params;

        return $this->formatResponse($this->client->execute($this));
    }

    public function formatResponse($response)
    {
        return $response;
    }

}