<?php

namespace CoreOpenApi\VO;

use CoreOpenApi\Helper\Validate;

class BaseVO implements Arrayable
{
    protected $error;

    protected $rule = [];

    protected $data = [];

    public function toArray()
    {
        if (empty($this->data))
        {
            $return = get_object_vars($this);
            unset($return['rule'], $return['error'], $return['data']);
            foreach ($return as $key => $value)
            {
                if (is_array($value))
                {
                    foreach ($value as $idx => $it)
                    {
                        if (is_subclass_of($it, 'CoreOpenApi\VO\BaseVO'))
                        {
                            $data = $it->getData();
                            if (!$it->hasError())
                            {
                                $return[$key][$idx] = $data;
                            }
                            else
                            {
                                $this->error[] = ['error' => $it->getError(), 'data' => $it->toArray()];
                            }
                        }
                    }
                }
                else if (is_subclass_of($value, 'CoreOpenApi\VO\BaseVO'))
                {
                    $data = $value->getData();
                    if (!$value->hasError())
                    {
                        $return[$key] = $data;
                    }
                    else
                    {
                        $this->error[] = $value->getError();
                    }
                }
            }

            $this->data = $return;
        }

        return $this->data;

    }

    public function hasError()
    {
        return !!$this->error;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 再获取数据之前，检测规则是否合法
     * @return mixed
     */
    public function getData()
    {
        if (!$this->check())
        {
            return $this->error;
        }

        return $this->toArray();
    }

    /**
     * 检测规则是否正确
     * @return bool
     */
    public function check()
    {
        $validate = new Validate($this->rule);
        if (!$validate->batch()->check($this->toArray()))
        { //当前VO字段不符合条件
            $this->error = $validate->getError();

            return false;
        }
        else if ($this->error)
        {//嵌套的VO字段不符合条件
            return false;
        }

        return true;
    }
}