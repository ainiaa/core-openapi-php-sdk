<?php

namespace CoreOpenApi\Api;


class DemoService extends RequestService
{
    /**
     * @return mixed
     */
    public function get_user($page)
    {
        $params = array(
                'pageNo'   => (string)$page,
                'pageSize' => $this->rows_num,
        );

        return $this->call('privilege/searchUser', $params);
    }

}