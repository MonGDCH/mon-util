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
     * 获取错误信息
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'code' => $this->getCode(),
            'msg'  => $this->getMessage()
        ];
    }
}
