<?php

namespace mon\util\exception;

use Exception;

/**
 * 客服端操作异常
 */
class NetWorkException extends Exception
{
    /**
     * curl句柄
     *
     * @var mixed
     */
    private $ch = null;

    /**
     * 重载构造方法
     *
     * @param string $message
     * @param integer $code
     * @param \Throwable $previous
     * @param mixed $ch
     */
    public function __construct($message, $code =  0, $previous = null, $ch = null)
    {
        parent::__construct($message, $code, $previous);
        $this->ch = $ch;
    }

    /**
     * 获取异常的curl句柄
     *
     * @return mixed
     */
    public function getCh()
    {
        return $this->ch;
    }
}
