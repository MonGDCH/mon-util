<?php

namespace mon\util;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * 服务容器类
 *
 * @see 注意：该类由[mongdch/mon-container]包迁移，后续将不再维护[mongdch/mon-container]包。
 * @author Mon 985558837@qq.com
 * @version 1.2  2018-07-05
 * @version 1.3.0   优化代码，增强注解  2021-03-01
 * @version 1.3.1   移除静态支持，接入psr标准 2022-07-26
 */
class Container implements ContainerInterface
{
    use Instance;

    /**
     * 容器中对象的标识符
     *
     * @var array
     */
    protected $bind = [];

    /**
     * 实例容器
     *
     * @var array
     */
    protected $service = [];

    /**
     * 私有化构造方法
     */
    protected function __construct()
    {
    }

    /**
     * get方法别名
     *
     * @param  string  $abstract    对象名称或标识
     * @param  array   $vars        入参
     * @param  boolean $newInstance 是否获取新的实例
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function make($abstract, $vars = [], $newInstance = false)
    {
        return $this->get($abstract, $vars, $newInstance);
    }

    /**
     * set方法别名
     *
     * @param string $abstract 类名称或标识符
     * @param mixed  $server   要绑定的实例
     * @return Container
     */
    public function bind($abstract, $server = null)
    {
        return $this->bind($abstract, $server);
    }

    /**
     * 创建获取对象的实例
     *
     * @param  string  $name     类名称或标识符
     * @param  array   $vars     绑定的参数
     * @param  boolean $new      是否保存实例
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function get($name, $vars = [], $new = false)
    {
        if (isset($this->service[$name]) && !$new) {
            $object = $this->service[$name];
        } else {
            if (isset($this->bind[$name])) {
                // 存在标识
                $service = $this->bind[$name];

                if ($service instanceof Closure) {
                    // 匿名函数，绑定参数
                    $object = $this->invokeFunction($service, $vars);
                } elseif (is_object($service)) {
                    // 已实例化的对象
                    $object = $service;
                } else {
                    // 类对象，回调获取实例
                    $object = $this->get($service, $vars, $new);
                }
            } else {
                // 不存在，判断为直接写入的类对象, 获取实例
                $object = $this->invokeClass($name, $vars);
            }

            // 保存实例
            if (!$new) {
                $this->service[$name] = $object;
            }
        }

        return $object;
    }

    /**
     * 绑定类、闭包、实例、接口实现到容器
     *
     * @param  mixed  $abstract 类名称或标识符或者数组
     * @param  mixed  $server   要绑定的实例
     * @return Container
     */
    public function set($abstract, $server = null)
    {
        // 传入数组，批量注册
        if (is_array($abstract)) {
            foreach ($abstract as $prefix => $service) {
                // 数组，定义前缀并绑定服务
                if (is_array($service)) {
                    foreach ($service as $k => $v) {
                        $name = $prefix . '_' . $k;
                        $this->register($name, $v);
                    }
                } else {
                    $this->register($prefix, $service);
                }
            }
        } else {
            $this->register($abstract, $server);
        }

        return $this;
    }

    /**
     * 判断容器中是否存在某个类或标识
     *
     * @param  string  $name     类名称或标识符
     * @return boolean           [description]
     */
    public function has($name)
    {
        return isset($this->bind[$name]) || isset($this->service[$name]);
    }

    /**
     * 绑定参数，执行函数或者闭包
     *
     * @param  mixed $function 函数或者闭包
     * @param  array $vars     绑定参数
     * @return mixed
     */
    public function invokeFunction($function, $vars = [])
    {
        // 创建反射对象
        $reflact = new ReflectionFunction($function);
        // 获取参数
        $args = $this->bindParams($reflact, $vars);

        return $reflact->invokeArgs($args);
    }

    /**
     * 执行类方法， 绑定参数
     *
     * @param  string|array $method 类方法, 用@分割, 如: Test@say
     * @param  array        $vars   绑定参数
     * @return mixed
     */
    public function invokeMethd($method, $vars = [])
    {
        // 字符串转数组
        if (is_string($method)) {
            $method = explode('@', $method);
        }

        // 反射绑定类方法
        $class = is_object($method[0]) ? $method[0] : $this->invokeClass($method[0]);
        $reflact = new ReflectionMethod($class, $method[1]);

        // 绑定参数
        $args = $this->bindParams($reflact, $vars);
        return $reflact->invokeArgs($class, $args);
    }

    /**
     * 反射执行对象实例化，支持构造方法依赖注入
     *
     * @param  string $class 对象名称
     * @param  array  $vars  绑定构造方法参数
     * @return mixed
     */
    public function invokeClass($class, $vars = [])
    {
        $reflect = new ReflectionClass($class);
        // 获取构造方法
        $constructor = $reflect->getConstructor();

        if ($constructor) {
            // 存在构造方法
            $args = $this->bindParams($constructor, $vars);
        } else {
            $args = [];
        }

        return $reflect->newInstanceArgs($args);
    }

    /**
     * 反射执行回调方法
     *
     * @param  mixed  $callback 回调方法
     * @param  array  $vars     参数
     * @return mixed
     */
    public function invoke($callback, $vars = [])
    {
        if ($callback instanceof Closure) {
            $result = $this->invokeFunction($callback, $vars);
        } else {
            $result = $this->invokeMethd($callback, $vars);
        }

        return $result;
    }

    /**
     * 注册服务容器
     *
     * @param string $name     名称
     * @param mixed  $server   要绑定的实例
     * @return Container
     */
    protected function register($name, $server)
    {
        // 闭包，绑定闭包
        if ($server instanceof Closure) {
            $this->bind[$name] = $server;
        }
        // 实例化后的对象, 保存到实例容器中
        elseif (is_object($server)) {
            $this->service[$name] = $server;
        }
        // 对象类名称，先保存，不实例化
        else {
            $this->bind[$name] = $server;
        }

        return $this;
    }

    /**
     * 为反射对象绑定参数
     *
     * @param  mixed  $reflact 反射对象
     * @param  array  $vars    参数
     * @throws InvalidArgumentException
     * @return array
     */
    protected function bindParams($reflact, $vars = [])
    {
        $args = [];
        if ($reflact->getNumberOfParameters() > 0) {
            // 判断数组类型 数字数组时按顺序绑定参数
            reset($vars);
            $type = key($vars) === 0 ? 1 : 0;

            // 获取类方法需要的参数
            $params = $reflact->getParameters();

            // 获取参数类型, 绑定参数
            foreach ($params as $param) {
                $name  = $param->getName();
                $class = $param->getClass();

                if ($class) {
                    $className = $class->getName();
                    $args[] = $this->get($className);
                } elseif (1 == $type && !empty($vars)) {
                    $args[] = array_shift($vars);
                } elseif (0 == $type && isset($vars[$name])) {
                    $args[] = $vars[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new InvalidArgumentException('bind parameters were not found![' . $name . ']', 500);
                }
            }
        }

        return $args;
    }

    /**
     * 魔术方法获取实例
     *
     * @param  string $name 对象名称或标识
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * 魔术方法获取实例
     *
     * @param  string $name 对象名称或标识
     * @param  array  $args 参数
     * @return mixed
     */
    public function __call($name, $args)
    {
        return $this->get($name, $args);
    }
}
