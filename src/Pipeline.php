<?php

declare(strict_types=1);

namespace mon\util;

use Closure;
use Throwable;

/**
 * 洋葱模型管道模式（AOP切面处理回调）
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Pipeline
{
    /**
     * 中间件
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * 执行回调，绑定入参
     *
     * @var mixed
     */
    protected $passable = null;

    /**
     * 异常处理回调
     *
     * @var callable
     */
    protected $exceptionHandler = null;

    /**
     * 构造方法
     *
     * @param mixed $passable    执行回调传入参数
     * @param array $middlewares    中间件列表
     * @param callable|null $exceptionHandler    异常处理回调
     */
    public function __construct($passable = null, array $middlewares = [], ?callable $exceptionHandler = null)
    {
        $this->passable = $passable;
        $this->middlewares = $middlewares;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * 绑定执行回调参数
     *
     * @param mixed $passable
     * @return Pipeline
     */
    public function send($passable): Pipeline
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * 添加中间件
     *
     * @param callable $callback
     * @return Pipeline
     */
    public function then(callable $callback): Pipeline
    {
        $this->middlewares[] = $callback;
        return $this;
    }

    /**
     * 绑定中间件
     *
     * @param array $middlewares
     * @return Pipeline
     */
    public function withMiddlewares(array $middlewares): Pipeline
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * 绑定错误处理回调
     *
     * @param callable $callback
     * @return Pipeline
     */
    public function withExceptionHandler(callable $callback): Pipeline
    {
        $this->exceptionHandler = $callback;
        return $this;
    }

    /**
     * 执行管道
     *
     * @param Closure $callback
     * @return mixed
     */
    public function run(Closure $callback)
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            $this->carry(),
            function ($passable) use ($callback) {
                try {
                    return $callback($passable);
                } catch (Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            }
        );

        return $pipeline($this->passable);
    }

    /**
     * 获取中间件回调实现
     *
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($carry, $pipe) {
            return function ($passable) use ($carry, $pipe) {
                try {
                    return $pipe($passable, $carry);
                } catch (Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    /**
     * 异常处理
     *
     * @param mixed $passable   参数
     * @param Throwable $e      异常对象
     * @return mixed
     */
    protected function handleException($passable, Throwable $e)
    {
        if ($this->exceptionHandler) {
            return call_user_func($this->exceptionHandler, $passable, $e);
        }

        throw $e;
    }
}
