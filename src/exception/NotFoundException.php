<?php

declare(strict_types=1);

namespace mon\util\exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 容器服务未找到异常
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface {}
