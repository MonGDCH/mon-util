<?php

declare(strict_types=1);

namespace mon\util;

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
     * @var mixed
     */
    protected static $instance = null;

    /**
     * 获取单例
     *
     * @param mixed $options 初始化参数
     * @return static
     */
    public static function instance($options = null)
    {
        if (is_null(static::$instance)) {
            if (!is_null($options)) {
                static::$instance = new static($options);
            } else {
                static::$instance = new static();
            }
        }
        return static::$instance;
    }
}
