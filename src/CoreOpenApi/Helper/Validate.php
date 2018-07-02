<?php

namespace CoreOpenApi\Helper;

class Validate
{
    // 实例
    protected static $instance;

    // 自定义的验证类型
    protected static $type = [];

    // 验证类型别名
    protected $alias = [
        '>'    => 'gt',
        '>='   => 'egt',
        '<'    => 'lt',
        '<='   => 'elt',
        '='    => 'eq',
        'same' => 'eq',
    ];

    // 当前验证的规则
    protected $rule = [];

    // 验证提示信息
    protected $message = [];
    // 验证字段描述
    protected $field = [];

    // 验证规则默认提示信息
    protected static $typeMsg = [
        'require'     => ':attribute require',
        'number'      => ':attribute must be numeric',
        'integer'     => ':attribute must be integer',
        'float'       => ':attribute must be float',
        'boolean'     => ':attribute must be bool',
        'email'       => ':attribute not a valid email address',
        'array'       => ':attribute must be a array',
        'accepted'    => ':attribute must be yes,on or 1',
        'date'        => ':attribute not a valid datetime',
        'alpha'       => ':attribute must be alpha',
        'alphaNum'    => ':attribute must be alpha-numeric',
        'alphaNumUS'  => ':attribute must be alpha-numeric,underscore',
        'alphaDash'   => ':attribute must be alpha-numeric, dash, underscore',
        'activeUrl'   => ':attribute not a valid domain or ip',
        'chs'         => ':attribute must be chinese',
        'chsAlpha'    => ':attribute must be chinese or alpha',
        'chsAlphaNum' => ':attribute must be chinese,alpha-numeric',
        'chsAlphaUS'  => ':attribute must be chinese,alpha-numeric,underscore',
        'chsDash'     => ':attribute must be chinese,alpha-numeric,underscore, dash',
        'url'         => ':attribute not a valid url',
        'ip'          => ':attribute not a valid ip',
        'dateFormat'  => ':attribute must be dateFormat of :rule',
        'in'          => ':attribute must be in :rule',
        'notIn'       => ':attribute be notin :rule',
        'between'     => ':attribute must between :1 - :2',
        'notBetween'  => ':attribute not between :1 - :2',
        'length'      => 'size of :attribute must be :rule',
        'max'         => 'max size of :attribute must be :rule',
        'min'         => 'min size of :attribute must be :rule',
        'after'       => ':attribute cannot be less than :rule',
        'before'      => ':attribute cannot exceed :rule',
        'expire'      => ':attribute not within :rule',
        'allowIp'     => 'access IP is not allowed',
        'denyIp'      => 'access IP denied',
        'confirm'     => ':attribute out of accord with :2',
        'different'   => ':attribute cannot be same with :2',
        'egt'         => ':attribute must greater than or equal :rule',
        'gt'          => ':attribute must greater than :rule',
        'elt'         => ':attribute must less than or equal :rule',
        'lt'          => ':attribute must less than :rule',
        'eq'          => ':attribute must equal :rule',
        'regex'       => ':attribute not conform to the rules',
    ];

    // 当前验证场景
    protected $currentScene = null;

    // 正则表达式 regex = ['zip'=>'\d{6}',...]
    protected $regex = [];

    // 验证场景 scene = ['edit'=>'name1,name2,...']
    protected $scene = [];

    // 验证失败错误信息
    protected $error = [];

    // 批量验证
    protected $batch = false;

    /**
     * 构造函数
     * @access public
     *
     * @param array $rules   验证规则
     * @param array $message 验证提示信息
     * @param array $field   验证字段描述信息
     */
    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->rule    = array_merge($this->rule, $rules);
        $this->message = array_merge($this->message, $message);
        $this->field   = array_merge($this->field, $field);
    }

    /**
     * 实例化验证
     * @access public
     *
     * @param array $rules   验证规则
     * @param array $message 验证提示信息
     * @param array $field   验证字段描述信息
     *
     * @return Validate
     */
    public static function make($rules = [], $message = [], $field = [])
    {
        if (is_null(self::$instance))
        {
            self::$instance = new self($rules, $message, $field);
        }

        return self::$instance;
    }

    /**
     * 添加字段验证规则
     * @access protected
     *
     * @param string|array $name 字段名称或者规则数组
     * @param mixed        $rule 验证规则
     *
     * @return Validate
     */
    public function rule($name, $rule = '')
    {
        if (is_array($name))
        {
            $this->rule = array_merge($this->rule, $name);
        }
        else
        {
            $this->rule[$name] = $rule;
        }

        return $this;
    }

    /**
     * 注册验证（类型）规则
     * @access public
     *
     * @param string $type     验证规则类型
     * @param mixed  $callback callback方法(或闭包)
     *
     * @return void
     */
    public static function extend($type, $callback = null)
    {
        if (is_array($type))
        {
            self::$type = array_merge(self::$type, $type);
        }
        else
        {
            self::$type[$type] = $callback;
        }
    }

    /**
     * 设置验证规则的默认提示信息
     * @access protected
     *
     * @param string|array $type 验证规则类型名称或者数组
     * @param string       $msg  验证提示信息
     *
     * @return void
     */
    public static function setTypeMsg($type, $msg = null)
    {
        if (is_array($type))
        {
            self::$typeMsg = array_merge(self::$typeMsg, $type);
        }
        else
        {
            self::$typeMsg[$type] = $msg;
        }
    }

    /**
     * 设置提示信息
     * @access public
     *
     * @param string|array $name    字段名称
     * @param string       $message 提示信息
     *
     * @return Validate
     */
    public function message($name, $message = '')
    {
        if (is_array($name))
        {
            $this->message = array_merge($this->message, $name);
        }
        else
        {
            $this->message[$name] = $message;
        }

        return $this;
    }

    /**
     * 设置批量验证
     * @access public
     *
     * @param bool $batch 是否批量验证
     *
     * @return Validate
     */
    public function batch($batch = true)
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * 数据自动验证
     * @access public
     *
     * @param array $data  数据
     * @param mixed $rules 验证规则
     *
     * @return bool
     */
    public function check($data, $rules = [])
    {
        $this->error = [];

        if (empty($rules))
        {
            // 读取验证规则
            $rules = $this->rule;
        }

        foreach ($rules as $key => $item)
        {
            // field => rule1|rule2... field=>['rule1','rule2',...]
            if (is_numeric($key))
            {
                // [field,rule1|rule2,msg1|msg2]
                $key  = $item[0];
                $rule = $item[1];
                if (isset($item[2]))
                {
                    $msg = is_string($item[2]) ? explode('|', $item[2]) : $item[2];
                }
                else
                {
                    $msg = [];
                }
            }
            else
            {
                $rule = $item;
                $msg  = [];
            }
            if (strpos($key, '|'))
            {
                // 字段|描述 用于指定属性名称
                list($key, $title) = explode('|', $key);
            }
            else
            {
                $title = isset($this->field[$key]) ? $this->field[$key] : $key;
            }

            // 获取数据 支持二维数组
            $value = $this->getDataValue($data, $key);

            // 字段验证
            if ($rule instanceof \Closure)
            {
                // 匿名函数验证 支持传入当前字段和所有字段两个数据
                $result = call_user_func_array($rule, [$value, $data]);
            }
            else
            {
                $result = $this->checkItem($key, $value, $rule, $data, $title, $msg);
            }

            if (true !== $result)
            {
                // 没有返回true 则表示验证失败
                if (!empty($this->batch))
                {
                    // 批量验证
                    if (is_array($result))
                    {
                        $this->error = array_merge($this->error, $result);
                    }
                    else
                    {
                        $this->error[$key] = $result;
                    }
                }
                else
                {
                    $this->error = $result;

                    return false;
                }
            }
        }

        return !empty($this->error) ? false : true;
    }

    /**
     * 根据验证规则验证数据
     * @access protected
     *
     * @param  mixed $value 字段值
     * @param  mixed $rules 验证规则
     *
     * @return bool
     */
    protected function checkRule($value, $rules)
    {
        if ($rules instanceof \Closure)
        {
            return call_user_func_array($rules, [$value]);
        }
        else if (is_string($rules))
        {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $key => $rule)
        {
            if ($rule instanceof \Closure)
            {
                $result = call_user_func_array($rule, [$value]);
            }
            else
            {
                // 判断验证类型
                list($type, $rule) = $this->getValidateType($key, $rule);

                $callback = isset(self::$type[$type]) ? self::$type[$type] : [$this, $type];

                $result = call_user_func_array($callback, [$value, $rule]);
            }

            if (true !== $result)
            {
                return $result;
            }
        }

        return true;
    }

    /**
     * 验证单个字段规则
     * @access protected
     *
     * @param string $field 字段名
     * @param mixed  $value 字段值
     * @param mixed  $rules 验证规则
     * @param array  $data  数据
     * @param string $title 字段描述
     * @param array  $msg   提示信息
     *
     * @return mixed
     */
    protected function checkItem($field, $value, $rules, $data, $title = '', $msg = [])
    {
        // 支持多规则验证 require|in:a,b,c|... 或者 ['require','in'=>'a,b,c',...]
        if (is_string($rules))
        {
            $rules = explode('|', $rules);
        }
        $i = 0;
        foreach ($rules as $key => $rule)
        {
            if ($rule instanceof \Closure)
            {
                $result = call_user_func_array($rule, [$value, $data]);
                $info   = is_numeric($key) ? '' : $key;
            }
            else
            {
                // 判断验证类型
                list($type, $rule, $info) = $this->getValidateType($key, $rule);

                // 如果不是require 有数据才会行验证
                if (0 === strpos($info, 'require') || (!is_null($value) && '' !== $value))
                {
                    // 验证类型
                    $callback = isset(self::$type[$type]) ? self::$type[$type] : [$this, $type];
                    // 验证数据
                    $result = call_user_func_array($callback, [$value, $rule, $data, $field, $title]);
                }
                else
                {
                    $result = true;
                }
            }

            if (false === $result)
            {
                // 验证失败 返回错误信息
                if (isset($msg[$i]))
                {
                    $message = $msg[$i];
                }
                else
                {
                    $message = $this->getRuleMsg($field, $title, $info, $rule);
                }

                return $message;
            }
            else if (true !== $result)
            {
                // 返回自定义错误信息
                if (is_string($result) && false !== strpos($result, ':'))
                {
                    $result = str_replace([':attribute', ':rule'], [$title, (string)$rule], $result);
                }

                return $result;
            }
            $i++;
        }

        return $result;
    }

    /**
     * 获取当前验证类型及规则
     * @access public
     *
     * @param  mixed $key
     * @param  mixed $rule
     *
     * @return array
     */
    protected function getValidateType($key, $rule)
    {
        // 判断验证类型
        if (!is_numeric($key))
        {
            return [$key, $rule, $key];
        }

        if (strpos($rule, ':'))
        {
            list($type, $rule) = explode(':', $rule, 2);
            if (isset($this->alias[$type]))
            {
                // 判断别名
                $type = $this->alias[$type];
            }
            $info = $type;
        }
        else if (method_exists($this, $rule))
        {
            $type = $rule;
            $info = $rule;
            $rule = '';
        }
        else
        {
            $type = 'is';
            $info = $rule;
        }

        return [$type, $rule, $info];
    }

    /**
     * 验证是否和某个字段的值一致
     * @access protected
     *
     * @param mixed  $value 字段值
     * @param mixed  $rule  验证规则
     * @param array  $data  数据
     * @param string $field 字段名
     *
     * @return bool
     */
    protected function confirm($value, $rule, $data, $field = '')
    {
        if ('' == $rule)
        {
            if (strpos($field, '_confirm'))
            {
                $rule = strstr($field, '_confirm', true);
            }
            else
            {
                $rule = $field . '_confirm';
            }
        }

        return $this->getDataValue($data, $rule) === $value;
    }

    /**
     * 验证是否和某个字段的值是否不同
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     *
     * @return bool
     */
    protected function different($value, $rule, $data)
    {
        return $this->getDataValue($data, $rule) != $value;
    }

    /**
     * 验证是否大于等于某个值
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     *
     * @return bool
     */
    protected function egt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);

        return !is_null($val) && $value >= $val;
    }

    /**
     * 验证是否大于某个值
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     *
     * @return bool
     */
    protected function gt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);

        return !is_null($val) && $value > $val;
    }

    /**
     * 验证是否小于等于某个值
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     *
     * @return bool
     */
    protected function elt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);

        return !is_null($val) && $value <= $val;
    }

    /**
     * 验证是否小于某个值
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     *
     * @return bool
     */
    protected function lt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);

        return !is_null($val) && $value < $val;
    }

    /**
     * 验证是否等于某个值
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function eq($value, $rule)
    {
        return $value == $rule;
    }

    /**
     * 验证字段值是否为有效格式
     * @access protected
     *
     * @param mixed  $value 字段值
     * @param string $rule  验证规则
     * @param array  $data  验证数据
     *
     * @return bool
     */
    protected function is($value, $rule, $data = [])
    {
        switch ($rule)
        {
            case 'require':
                // 必须
                $result = !empty($value) || '0' == $value;
                break;
            case 'accepted':
                // 接受
                $result = in_array($value, ['1', 'on', 'yes']);
                break;
            case 'date':
                // 是否是一个有效日期
                $result = false !== strtotime($value);
                break;
            case 'alpha':
                // 只允许字母
                $result = $this->regex($value, '/^[A-Za-z]+$/');
                break;
            case 'alphaNum':
                // 只允许字母和数字
                $result = $this->regex($value, '/^[A-Za-z0-9]+$/');
                break;
            case 'alphaNumUS':
                // 只允许字母,数字和下划线
                $result = $this->regex($value, '/^[A-Za-z0-9\_]+$/');
                break;
            case 'alphaDash':
                // 只允许字母、数字和下划线 破折号
                $result = $this->regex($value, '/^[A-Za-z0-9\-\_]+$/');
                break;
            case 'chs':
                // 只允许汉字
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}]+$/u');
                break;
            case 'chsAlpha':
                // 只允许汉字、字母
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u');
                break;
            case 'chsAlphaNum':
                // 只允许汉字、字母和数字
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u');
                break;
            case 'chsAlphaUS':
                // 只允许汉字、字母,数字和下划线
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_]+$/u');
                break;
            case 'chsDash':
                // 只允许汉字、字母、数字和下划线_及破折号-
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u');
                break;
            case 'activeUrl':
                // 是否为有效的网址
                $result = checkdnsrr($value);
                break;
            case 'ip':
                // 是否为IP地址
                $result = $this->filter($value, [FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6]);
                break;
            case 'url':
                // 是否为一个URL地址
                $result = $this->filter($value, FILTER_VALIDATE_URL);
                break;
            case 'float':
                // 是否为float
                $result = $this->filter($value, FILTER_VALIDATE_FLOAT);
                break;
            case 'number':
                $result = is_numeric($value);
                break;
            case 'integer':
                // 是否为整型
                $result = $this->filter($value, FILTER_VALIDATE_INT);
                break;
            case 'email':
                // 是否为邮箱地址
                $result = $this->filter($value, FILTER_VALIDATE_EMAIL);
                break;
            case 'boolean':
                // 是否为布尔值
                $result = in_array($value, [true, false, 0, 1, '0', '1'], true);
                break;
            case 'array':
                // 是否为数组
                $result = is_array($value);
                break;
            default:
                if (isset(self::$type[$rule]))
                {
                    // 注册的验证规则
                    $result = call_user_func_array(self::$type[$rule], [$value]);
                }
                else
                {
                    // 正则验证
                    $result = $this->regex($value, $rule);
                }
        }

        return $result;
    }

    /**
     * 验证是否为合格的域名或者IP 支持A，MX，NS，SOA，PTR，CNAME，AAAA，A6， SRV，NAPTR，TXT 或者 ANY类型
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function activeUrl($value, $rule)
    {
        if (!in_array($rule, ['A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY']))
        {
            $rule = 'MX';
        }

        return checkdnsrr($value, $rule);
    }

    /**
     * 验证是否有效IP
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则 ipv4 ipv6
     *
     * @return bool
     */
    protected function ip($value, $rule)
    {
        if (!in_array($rule, ['ipv4', 'ipv6']))
        {
            $rule = 'ipv4';
        }

        return $this->filter($value, [FILTER_VALIDATE_IP, 'ipv6' == $rule ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4]);
    }

    /**
     * 验证时间和日期是否符合指定格式
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function dateFormat($value, $rule)
    {
        $info = date_parse_from_format($rule, $value);

        return 0 == $info['warning_count'] && 0 == $info['error_count'];
    }

    /**
     * 使用filter_var方式验证
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function filter($value, $rule)
    {
        if (is_string($rule) && strpos($rule, ','))
        {
            list($rule, $param) = explode(',', $rule);
        }
        else if (is_array($rule))
        {
            $param = isset($rule[1]) ? $rule[1] : null;
            $rule  = $rule[0];
        }
        else
        {
            $param = null;
        }

        return false !== filter_var($value, is_int($rule) ? $rule : filter_id($rule), $param);
    }

    /**
     * 验证某个字段等于某个值的时候必须
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     *
     * @return bool
     */
    protected function requireIf($value, $rule, $data)
    {
        list($field, $val) = explode(',', $rule);
        if ($this->getDataValue($data, $field) == $val)
        {
            return !empty($value) || '0' == $value;
        }
        else
        {
            return true;
        }
    }

    /**
     * 通过回调方法验证某个字段是否必须
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     *
     * @return bool
     */
    protected function requireCallback($value, $rule, $data)
    {
        $result = call_user_func_array($rule, [$value, $data]);
        if ($result)
        {
            return !empty($value) || '0' == $value;
        }
        else
        {
            return true;
        }
    }

    /**
     * 验证某个字段有值的情况下必须
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     *
     * @return bool
     */
    protected function requireWith($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        if (!empty($val))
        {
            return !empty($value) || '0' == $value;
        }
        else
        {
            return true;
        }
    }

    /**
     * 验证是否在范围内
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function in($value, $rule)
    {
        return in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * 验证是否不在某个范围
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function notIn($value, $rule)
    {
        return !in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * between验证数据
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function between($value, $rule)
    {
        if (is_string($rule))
        {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;

        return $value >= $min && $value <= $max;
    }

    /**
     * 使用notbetween验证数据
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function notBetween($value, $rule)
    {
        if (is_string($rule))
        {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;

        return $value < $min || $value > $max;
    }

    /**
     * 验证数据长度
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function length($value, $rule)
    {
        if (is_array($value))
        {
            $length = count($value);
        }
        else
        {
            $length = mb_strlen((string)$value);
        }

        if (strpos($rule, ','))
        {
            // 长度区间
            list($min, $max) = explode(',', $rule);

            return $length >= $min && $length <= $max;
        }
        else
        {
            // 指定长度
            return $length == $rule;
        }
    }

    /**
     * 验证数据最大长度
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function max($value, $rule)
    {
        if (is_array($value))
        {
            $length = count($value);
        }
        else
        {
            $length = mb_strlen((string)$value);
        }

        return $length <= $rule;
    }

    /**
     * 验证数据最小长度
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function min($value, $rule)
    {
        if (is_array($value))
        {
            $length = count($value);
        }
        else
        {
            $length = mb_strlen((string)$value);
        }

        return $length >= $rule;
    }

    /**
     * 验证日期
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function after($value, $rule)
    {
        return strtotime($value) >= strtotime($rule);
    }

    /**
     * 验证日期
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function before($value, $rule)
    {
        return strtotime($value) <= strtotime($rule);
    }

    /**
     * 使用正则验证数据
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则 正则规则或者预定义正则名
     *
     * @return mixed
     */
    protected function regex($value, $rule)
    {
        if (isset($this->regex[$rule]))
        {
            $rule = $this->regex[$rule];
        }
        if (0 !== strpos($rule, '/') && !preg_match('/\/[imsU]{0,4}$/', $rule))
        {
            // 不是正则表达式则两端补上/
            $rule = '/^' . $rule . '$/';
        }

        return is_scalar($value) && 1 === preg_match($rule, (string)$value);
    }

    // 获取错误信息
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取数据值
     * @access protected
     *
     * @param array  $data 数据
     * @param string $key  数据标识 支持二维
     *
     * @return mixed
     */
    protected function getDataValue($data, $key)
    {
        if (is_numeric($key))
        {
            $value = $key;
        }
        else if (strpos($key, '.'))
        {
            // 支持二维数组验证
            list($name1, $name2) = explode('.', $key);
            $value = isset($data[$name1][$name2]) ? $data[$name1][$name2] : null;
        }
        else
        {
            $value = isset($data[$key]) ? $data[$key] : null;
        }

        return $value;
    }

    /**
     * 获取验证规则的错误提示信息
     * @access protected
     *
     * @param string $attribute 字段英文名
     * @param string $title     字段描述名
     * @param string $type      验证规则名称
     * @param mixed  $rule      验证规则数据
     *
     * @return string
     */
    protected function getRuleMsg($attribute, $title, $type, $rule)
    {
        if (isset($this->message[$attribute . '.' . $type]))
        {
            $msg = $this->message[$attribute . '.' . $type];
        }
        else if (isset($this->message[$attribute][$type]))
        {
            $msg = $this->message[$attribute][$type];
        }
        else if (isset($this->message[$attribute]))
        {
            $msg = $this->message[$attribute];
        }
        else if (isset(self::$typeMsg[$type]))
        {
            $msg = self::$typeMsg[$type];
        }
        else if (0 === strpos($type, 'require'))
        {
            $msg = self::$typeMsg['require'];
        }
        else
        {
            $msg = $title . 'not conform to the rules';
        }

        if (is_string($msg) && is_scalar($rule) && false !== strpos($msg, ':'))
        {
            // 变量替换
            if (is_string($rule) && strpos($rule, ','))
            {
                $array = array_pad(explode(',', $rule), 3, '');
            }
            else
            {
                $array = array_pad([], 3, '');
            }
            $msg = str_replace([':attribute', ':rule', ':1', ':2', ':3'],
                [$title, (string)$rule, $array[0], $array[1], $array[2]], $msg);
        }

        return $msg;
    }

    public static function __callStatic($method, $params)
    {
        $class = self::make();
        if (method_exists($class, $method))
        {
            return call_user_func_array([$class, $method], $params);
        }
        else
        {
            throw new \BadMethodCallException('method not exists:' . __CLASS__ . '->' . $method);
        }
    }
}
