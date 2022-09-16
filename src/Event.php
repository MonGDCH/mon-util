<?php

namespace mon\util;

use Closure;

/**
 * 事件监听
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1 优化业务代码 2022-09-16
 */
class Event
{
    use Instance;

    /**
     * 对象回调方法名
     *
     * @var string
     */
    protected $handler = 'handler';

    /**
     * 事件标签列表
     *
     * @var array
     */
    protected $tags = [];

    /**
     * 私有化构造方法
     */
    protected function __construct()
    {
    }

    /**
     * 批量注册事件
     *
     * @param array $tags 事件标识
     * @return Event
     */
    public function register(array $tags)
    {
        $this->tags = array_merge($this->tags, $tags);
        return $this;
    }

    /**
     * 获取事件信息
     *
     * @param  string $tag 事件名称
     * @return array
     */
    public function get($tag = '')
    {
        if (empty($tag)) {
            // 获取全部的插件信息
            return $this->tags;
        }

        return array_key_exists($tag, $this->tags) ? $this->tags[$tag] : [];
    }

    /**
     * 是否存在某个事件监听
     *
     * @param string $tag
     * @return boolean
     */
    public function has($tag)
    {
        return !empty($this->get($tag));
    }

    /**
     * 设置获取回调方法名
     *
     * @param string $name 回调方法名
     * @return string
     */
    public function handler($name = '')
    {
        if (!empty($name)) {
            $this->handler = $name;
        }

        return $this->handler;
    }

    /**
     * 监听事件
     *
     * @param string $tag    事件名称
     * @param mixed $callbak 事件回调
     * @return Event
     */
    public function listen($tag, $callbak)
    {
        isset($this->tags[$tag]) || $this->tags[$tag] = [];
        $this->tags[$tag][] = $callbak;

        return $this;
    }

    /**
     * 触发事件
     *
     * @param string $tag    事件名称
     * @param array ...$args 可变参数
     * @return array
     */
    public function trigger($tag, ...$args)
    {
        $tags = $this->get($tag);
        $results = [];
        foreach ($tags as $name => $handler) {
            $results[$name] = $this->execute($handler, ...$args);
            if ($results[$name] === false) {
                // 如果返回false 则中断行为执行
                break;
            }
        }

        return $results;
    }

    /**
     * 移除某个事件监听
     *
     * @param string|array $tag
     * @return Event
     */
    public function remove($tag)
    {
        if (is_array($tag)) {
            foreach ($tag as $name) {
                $this->tags[$name] = [];
            }
        } else {
            $this->tags[$tag] = [];
        }

        return $this;
    }

    /**
     * 清空所有事件监听
     *
     * @return Event
     */
    public function clear()
    {
        $this->tags = [];
        return $this;
    }

    /**
     * 执行行为
     *
     * @param  mixed  $class    行为回调
     * @param  array  $args     可变参数
     * @return mixed
     */
    protected function execute($class, ...$args)
    {
        if ($class instanceof Closure) {
            // 匿名回调
            return Container::instance()->invokeFunction($class, (array)$args);
        } elseif (is_string($class) && !empty($class)) {
            // 类方法回调
            return Container::instance()->invokeMethd([$class, $this->handler()], (array)$args);
        }
    }
}
