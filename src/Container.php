<?php

declare(strict_types=1);

namespace mon\util;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionException;
use InvalidArgumentException;
use mon\util\exception\ContainerException;
use mon\util\exception\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * 服务容器类
 *
 * @author Mon <985558837@qq.com>
 * @version 1.3.3   优化参数绑定 2022-09-19
 * @version 1.3.4   优化PHP7支持 2022-10-25
 */
class Container implements ContainerInterface
{
    /**
     * 单例实体
     *
     * @var mixed
     */
    protected static $instance = null;

    /**
     * 容器中对象的标识符
     *
     * @var array
     */
    protected array $bind = [];

    /**
     * 实例容器
     *
     * @var array
     */
    protected array $service = [];

    /**
     * 实例容器最大条目数，超出后不再缓存新的实例（显式绑定的标识不受限制），
     * 避免常驻进程（如 workerman）中内存持续增长
     *
     * @var integer
     */
    protected int $maxService = 1024;

    /**
     * 私有化构造方法
     */
    protected function __construct() {}

    /**
     * 获取单例
     *
     * @return static
     */
    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * get方法别名，静态调用
     *
     * @param  string  $id  对象名称或标识
     * @param  array   $val 入参
     * @param  boolean $new 是否获取新的实例
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @return mixed
     */
    public static function gen(string $id, array $val = [], bool $new = false)
    {
        return static::instance()->make($id, $val, $new);
    }

    /**
     * get方法别名，重新获取实例
     *
     * @param  string  $id  对象名称或标识
     * @param  array   $val 入参
     * @param  boolean $new 是否获取新的实例
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function make(string $id, array $val = [], bool $new = true)
    {
        return $this->get($id, $val, $new);
    }

    /**
     * 创建获取对象的实例
     *
     * @param  string  $id  类名称或标识符
     * @param  array   $val 绑定的参数
     * @param  boolean $new 是否保存实例
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function get(string $id, array $val = [], bool $new = false)
    {
        if (isset($this->service[$id]) && !$new) {
            $object = $this->service[$id];
        } else {
            if (isset($this->bind[$id])) {
                // 存在标识
                $service = $this->bind[$id];

                if ($service instanceof Closure) {
                    // 匿名函数，绑定参数
                    $object = $this->invokeFunction($service, $val);
                } elseif (is_object($service)) {
                    // 已实例化的对象
                    $object = $service;
                } else {
                    // 类对象，回调获取实例
                    $object = $this->get($service, $val, $new);
                }
            } else {
                // 不存在，判断为直接写入的类对象, 获取实例
                $object = $this->invokeClass($id, $val);
            }

            // 保存实例，超过上限后不再缓存新的标识，防止常驻进程内存无限增长
            if (!$new && (isset($this->bind[$id]) || count($this->service) < $this->maxService)) {
                $this->service[$id] = $object;
            }
        }

        return $object;
    }

    /**
     * 绑定类、闭包、实例、接口实现到容器
     *
     * @param  string|array  $id     类名称或标识符，或批量绑定的关联数组
     * @param  mixed  $server   要绑定的实例
     * @return Container
     */
    public function set(string|array $id, $server = null): Container
    {
        // 传入数组，批量注册
        if (is_array($id)) {
            foreach ($id as $name => $service) {
                $this->bindServer((string)$name, $service);
            }
        } else {
            $this->bindServer($id, $server);
        }

        return $this;
    }

    /**
     * 判断容器中是否存在某个类或标识
     *
     * @param string $id 类名称或标识符
     * @return boolean
     */
    public function has(string $id): bool
    {
        return isset($this->bind[$id]) || isset($this->service[$id]);
    }

    /**
     * 移除容器中已缓存的实例（保留绑定关系），用于常驻进程清理请求级服务
     *
     * @param string $id 类名称或标识符
     * @return boolean 是否存在并移除成功
     */
    public function remove(string $id): bool
    {
        if (!isset($this->service[$id])) {
            return false;
        }
        unset($this->service[$id]);

        return true;
    }

    /**
     * 清理容器，用于常驻进程在请求结束后重置容器状态
     *
     * @param boolean $bind 是否同时清理绑定关系，默认只清理已缓存的实例
     * @return Container
     */
    public function clear(bool $bind = false): Container
    {
        $this->service = [];
        if ($bind) {
            $this->bind = [];
        }

        return $this;
    }

    /**
     * 获取实例容器最大条目数
     *
     * @return integer
     */
    public function getMaxService(): int
    {
        return $this->maxService;
    }

    /**
     * 设置实例容器最大条目数
     *
     * @param integer $max 最大条目数
     * @return Container
     */
    public function setMaxService(int $max): Container
    {
        $this->maxService = $max > 0 ? $max : 1;

        return $this;
    }

    /**
     * 绑定参数，执行函数或者闭包
     *
     * @param  Closure|string $function 函数或者闭包
     * @param  array $vars     绑定参数
     * @throws ReflectionException
     * @return mixed
     */
    public function invokeFunction(Closure|string $function, array $vars = []): mixed
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
     * @param  string|array $method 类方法, 字符串时用@分割, 如: Test@say
     * @param  array        $vars   绑定参数
     * @throws NotFoundException      类不存在时抛出
     * @throws InvalidArgumentException 方法格式错误时抛出
     * @return mixed
     */
    public function invokeMethod(string|array $method, array $vars = [])
    {
        // 字符串转数组
        if (is_string($method)) {
            $method = explode('@', $method);
            if (count($method) !== 2) {
                throw new InvalidArgumentException('方法定义格式错误，应为 [类名@方法名]，如: Test@say');
            }
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
     * @param  object|string $class 对象名称
     * @param  array  $vars  绑定构造方法参数
     * @throws NotFoundException     类不存在时抛出
     * @throws ContainerException    反射实例化失败时抛出
     * @return mixed
     */
    public function invokeClass(object|string $class, array $vars = [])
    {
        if (is_string($class) && !class_exists($class) && !interface_exists($class)) {
            throw new NotFoundException("class not exists: {$class}", 404);
        }

        try {
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
        } catch (\ReflectionException | \Error $e) {
            // 反射实例化失败（如抽象类、私有构造方法等），归一为容器异常
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 反射执行回调方法
     *
     * @param  Closure|string  $callback 回调方法
     * @param  array  $vars     参数
     * @throws ReflectionException
     * @return mixed
     */
    public function invoke(Closure|string $callback, array $vars = []): mixed
    {
        if ($callback instanceof Closure) {
            $result = $this->invokeFunction($callback, $vars);
        } else {
            $result = $this->invokeMethod($callback, $vars);
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
    protected function bindServer(string $name, $server): Container
    {
        if ($server instanceof Closure) {
            // 闭包，绑定闭包
            $this->bind[$name] = $server;
        } elseif (is_object($server)) {
            // 实例化后的对象, 保存到实例容器中
            $this->service[$name] = $server;
        } else {
            // 对象类名称，先保存，不实例化
            $this->bind[$name] = $server;
        }

        return $this;
    }

    /**
     * 为反射对象绑定参数
     *
     * @param  \ReflectionFunctionAbstract  $reflact 反射对象
     * @param  array  $vars    参数
     * @throws InvalidArgumentException
     * @return array
     */
    protected function bindParams(\ReflectionFunctionAbstract $reflact, array $vars = []): array
    {
        // 参数结果集
        $args = [];
        if ($reflact->getNumberOfParameters() > 0) {
            // 判断数组类型 数字数组时按顺序绑定参数
            reset($vars);
            $type = key($vars) === 0 ? 1 : 0;
            // 获取类方法需要的参数
            $params = $reflact->getParameters();
            // 绑定参数
            foreach ($params as $param) {
                // 变量名
                $name = $param->getName();
                $class = $param->getType();
                // 仅处理命名类型（联合/交叉类型直接走参数匹配），非内置类型视为类类型参数
                $className = ($class instanceof ReflectionNamedType && !$class->isBuiltin()) ? $class->getName() : null;

                if ($className !== null) {
                    // 类类型参数：优先使用传入的参数，否则由容器注入
                    if (1 == $type && !empty($vars)) {
                        $args[] = array_shift($vars);
                    } elseif (0 == $type && array_key_exists($name, $vars)) {
                        $args[] = $vars[$name];
                    } else {
                        $args[] = $this->get($className);
                    }
                } elseif (1 == $type && !empty($vars)) {
                    // 参数为索引数组
                    $args[] = array_shift($vars);
                } elseif (0 == $type && array_key_exists($name, $vars)) {
                    // 参数为关联数组
                    $args[] = $vars[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    // 获取默认值
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
     * 仅允许访问已绑定的标识，禁止通过魔术方法实例化任意类（防止用户输入驱动的对象构造）
     *
     * @param  string $name 对象名称或标识
     * @throws NotFoundException
     * @return mixed
     */
    public function __get(string $name)
    {
        if (!$this->has($name)) {
            throw new NotFoundException('service not bound: ' . $name, 404);
        }

        return $this->get($name);
    }

    /**
     * 魔术方法获取实例
     * 仅允许调用已绑定的标识，需要实例化类时请显式调用 get()
     *
     * @param  string $id 对象名称或标识
     * @param  array  $args 参数
     * @throws NotFoundException
     * @return mixed
     */
    public function __call(string $id, array $args)
    {
        if (!$this->has($id)) {
            throw new NotFoundException('service not bound: ' . $id, 404);
        }

        return $this->get($id, $args);
    }
}
