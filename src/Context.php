<?php

declare(strict_types=1);

namespace mon\util;

use stdClass;
use SplObjectStorage;

/**
 * 上下文内容管理类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
final class Context
{
    /**
     * 存储对象
     *
     * @var mixed
     */
    protected static $storage;

    /**
     * 数据对象
     *
     * @var stdClass
     */
    protected static $data;

    /**
     * 获取存储对象
     *
     * @return stdClass
     */
    protected static function getData(): stdClass
    {
        if (!static::$storage) {
            static::$storage = new SplObjectStorage();
            static::$data = new stdClass();
        }
        $key = static::getKey();
        if (!isset(static::$storage[$key])) {
            static::$storage[$key] = new stdClass();
        }

        return static::$storage[$key];
    }

    /**
     * 获取存储索引
     *
     * @return stdClass
     */
    protected static function getKey(): stdClass
    {
        return static::$data;
    }

    /**
     * 获取存储数据
     *
     * @param string $key       键名，空则获取所有
     * @param mixed $default    默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $data = static::getData();
        if ($key === '') {
            return $data;
        }

        return $data->$key ?? $default;
    }

    /**
     * 设置存储数据
     *
     * @param string $key   键名
     * @param mixed $value  值
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $data = static::getData();
        if ($key !== '') {
            $data->$key = $value;
        }
    }

    /**
     * 删除存储的数据
     *
     * @param string $key   键名
     * @return void
     */
    public static function delete(string $key): void
    {
        $data = static::getData();
        unset($data->$key);
    }

    /**
     * 是否存在某个存储键
     *
     * @param string $key   键名
     * @return boolean
     */
    public static function has(string $key): bool
    {
        $data = static::getData();
        return property_exists($data, $key);
    }

    /**
     * 清除存储对象
     *
     * @return void
     */
    public static function destroy(): void
    {
        unset(static::$storage[static::getKey()]);
    }
}
