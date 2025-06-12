<?php

declare(strict_types=1);

namespace mon\util;

/**
 * 单例trait
 *
 * @author Mon 985558837@qq.com
 * @version 1.0.1   修正获取当前实例的方式为static而非self
 * @version 2.0.0   重构逻辑，兼容Container容器
 */
trait Instance
{
    /**
     * 始终创建新的对象实例
     *
     * @var bool
     */
    protected static $alwaysNewInstance;

    /**
     * 获取当前对象实例

     *
     * @param mixed ...$args      变量c桉树
     * @return static
     */
    public static function instance(...$args)
    {
        return static::createInstance($args);
    }

    /**
     * 创建对象实例
     *
     * @param  string $class       类名或标识
     * @param  array  $args        变量
     * @param  bool   $newInstance 是否创建新的实例
     * @return static
     */
    public static function createInstance(array $args = [], bool $newInstance = false)
    {
        if (static::$alwaysNewInstance) {
            $newInstance = true;
        }

        $class = static::class;
        return Container::instance()->get($class, $args, $newInstance);
    }
}
