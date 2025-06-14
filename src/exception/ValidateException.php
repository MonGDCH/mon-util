<?php

declare(strict_types=1);

namespace mon\util\exception;

use Exception;

/**
 * 验证器验证错误
 */
class ValidateException extends Exception
{
    /**
     * 验证的数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 错误字段名
     *
     * @var string
     */
    protected $key = '';

    /**
     * 错误规则
     *
     * @var string
     */
    protected $rule = '';

    /**
     * 构造方法
     *
     * @param string $messag    错误信息
     * @param integer $code     错误码
     * @param mixed $key        错误字段名
     * @param string $rule      错误验证规则
     */
    public function __construct(string $messag, int $code = 0, array $data = [], $key = '', $rule = '')
    {
        parent::__construct($messag, $code);
        $this->data = $data;
        $this->key = $key;
        $this->rule = $rule;
    }

    /**
     * 获取验证的数据
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 获取错误字段名
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * 获取错误规则
     *
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }
}
