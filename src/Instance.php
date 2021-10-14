<?php

namespace mon\util;

use Exception;

/**
 * 单例trait
 *
 * @author Mon 985558837@qq.com
 * @version 1.0.1   修正获取当前实例的方式为static而非self
 */
trait Instance
{
    /**
     * 单例实体
     *
     * @var null
     */
    protected static $instance = null;

    /**
     * 获取单例
     *
     * @param array $options 初始化参数
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($options);
        }
        return static::$instance;
    }

    /**
     * 静态调用支持，以"_"开头加方法名调用非静态方法
     * 
     * @see 例子： className::_methodName()
     * @param  string $method 方法名
     * @param  array  $params 参数
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        $call = mb_substr($method, 1);
        if (0 === mb_strpos($method, '_') && is_callable([static::$instance, $call])) {
            return call_user_func_array([static::$instance, $call], (array) $params);
        } else {
            throw new Exception("method not found => " . $method);
        }
    }
}
