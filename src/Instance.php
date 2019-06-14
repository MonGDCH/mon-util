<?php
namespace mon\util;

use Exception;

/**
 * 单例trait
 *
 * @author Mon 985558837@qq.com
 * @version 1.0
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
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($options);
        }
        return self::$instance;
    }

    /**
     * 静态调用支持
     *
     * @param  string $method [description]
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public static function __callStatic($method, $params)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        $call = substr($method, 1);
        if (0 === strpos($method, '_') && is_callable([self::$instance, $call])) {
            return call_user_func_array([self::$instance, $call], (array)$params);
        } else {
            throw new Exception("方法不存在 => " . $method);
        }
    }
}
