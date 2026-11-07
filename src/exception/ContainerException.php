<?php

declare(strict_types=1);

namespace mon\util\exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * 容器异常
 */
class ContainerException extends Exception implements ContainerExceptionInterface {}
