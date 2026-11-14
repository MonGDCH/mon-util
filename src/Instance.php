<?php

declare(strict_types=1);

namespace mon\util;

use ReflectionClass;

/**
 * 单例trait
 *
 * @author Mon 985558837@qq.com
 * @version 1.0.1   修正获取当前实例的方式为static而非self
 * @version 2.0.0   重构逻辑，兼容Container容器
 * @version 2.0.1   改用反射判断构造方法可见性，不再依赖异常消息
 * @version 2.0.2   单例按类名分桶存储，避免父子类共用同一静态属性导致串用
 */
trait Instance
{
    /**
     * 单例实体，按类名分桶存储
     * 注意：trait 的静态属性会被子类共享，必须按 static::class 区分，
     * 否则 Child::instance() 会拿到父类的实例
     *
     * @var array
     */
    protected static $instance = [];

    /**
     * 获取当前对象实例
     *
     * @param mixed ...$args      构造方法参数
     * @return static
     */
    public static function instance(...$args)
    {
        return static::createInstance($args);
    }

    /**
     * 创建对象实例
     *
     * @param  array  $args     变量
     * @param  bool   $isNew    是否创建新的实例
     * @return static
     */
    public static function createInstance(array $args = [], bool $isNew = false)
    {
        $class = static::class;
        if (static::alwaysNewInstance()) {
            $isNew = true;
        }

        // 私有/保护构造方法无法通过容器反射实例化，直接走本地单例逻辑
        $constructor = (new ReflectionClass($class))->getConstructor();
        if ($constructor !== null && !$constructor->isPublic()) {
            return static::getInstance($args, $isNew);
        }

        return Container::instance()->get($class, $args, $isNew);
    }

    /**
     * 是否始终创建新的对象实例
     *
     * @return boolean
     */
    protected static function alwaysNewInstance(): bool
    {
        return false;
    }

    /**
     * 初始化实例，用于一些需要私有化构造方法的类做容器绑定使用
     * 
     * @param array $args 参数
     * @param boolean $isNew 是否创建新的实例
     * @return static  返回当前实例, 例: return new static()
     */
    protected static function getInstance(array $args, bool $isNew)
    {
        // 按当前类名分桶，保证父类与子类各自持有独立的单例
        $class = static::class;
        if (!isset(static::$instance[$class]) || $isNew) {
            static::$instance[$class] = new static(...$args);
        }
        return static::$instance[$class];
    }
}
