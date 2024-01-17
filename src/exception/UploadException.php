<?php

declare(strict_types=1);

namespace mon\util\exception;

use Exception;

/**
 * 文件上传异常
 */
class UploadException extends Exception
{
    /**
     * 上传资源未找到
     */
    const ERROR_UPLOAD_FAILD = 1;

    /**
     * 未上传
     */
    const ERROR_UPLOAD_NOT_FOUND = 2;

    /**
     * 上传文件已重复
     */
    const ERROR_UPLOAD_EXISTS = 3;

    /**
     * 保存失败
     */
    const ERROR_UPLOAD_SAVE_FAILD = 4;

    /**
     * 上传目录不存在或不可写入
     */
    const ERROR_UPLOAD_DIR_NOT_FOUND = 5;

    /**
     * 校验未通过
     */
    const ERROR_UPLOAD_CHECK_FAILD = 6;

    /**
     * 未知上传错误
     */
    const ERROR_UPLOAD_NOT_MESSAGE = 7;

    /**
     * 非法上传文件 
     */
    const ERROR_UPLOAD_ILLEGAL = 8;

    /**
     * 上传文件大小不符
     */
    const ERROR_UPLOAD_SIZE_FAILD = 9;

    /**
     * 上传文件MIME类型不允许
     */
    const ERROR_UPLOAD_MINI_FAILD = 10;

    /**
     * 上传文件后缀不允许
     */
    const ERROR_UPLOAD_EXT_FAILD = 11;

    /**
     * 非法图像文件
     */
    const ERROR_UPLOAD_NOT_IMG = 12;

    /**
     * 分片文件不完整
     */
    const ERROR_CHUNK_FAILD = 13;
}
