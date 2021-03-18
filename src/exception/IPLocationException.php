<?php

namespace mon\util\exception;

use Exception;

/**
 * 图片操作异常
 */
class IPLocationException extends Exception
{
    /**
     * IP数据文件未找到
     */
    const ERROR_DATA_NOT_FOUND = 0;

    /**
     * 请先初始化
     */
    const ERROR_NOT_INIT = 1;

    /**
     * IPV4格式错误
     */
    const ERROR_IPV4_FAILD = 2;

    /**
     * 读取IP数据文件失败
     */
    const ERROR_DATA_READ_FAILD = 3;
}
