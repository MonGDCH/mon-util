<?php

namespace mon\util\exception;

use Exception;

/**
 * SQL解析异常
 */
class SqlException extends Exception
{
    /**
     * SQL文件未找到
     */
    const ERROR_SQL_FILE_NOT_FOUND = 0;
}
