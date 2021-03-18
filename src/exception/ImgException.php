<?php

namespace mon\util\exception;

use Exception;

/**
 * 图片操作异常
 */
class ImgException extends Exception
{
    /**
     * 解码GIF图片出错
     */
    const ERROR_GIT_PARSE = -1;

    /**
     * 不存在的图像文件
     */
    const ERROR_IMG_NOT_FOUND = 1;

    /**
     * 非法图像文件
     */
    const ERROR_IMG_FAILD  = 2;

    /**
     * 没有可以被保存的图像资源
     */
    const ERROR_IMG_SAVE = 3;

    /**
     * 没有指定图像资源
     */
    const ERROR_IMG_NOT_SPECIFIED = 4;

    /**
     * 不支持的缩略图裁剪类型
     */
    const ERROR_IMG_NOT_SUPPORT = 5;

    /**
     * 水印图像不存在
     */
    const ERROR_IMG_NOT_FOUND_WATER = 6;

    /**
     * 非法水印文件
     */
    const ERROR_IMG_FAILD_WATER = 7;

    /**
     * 不支持的水印位置类型
     */
    const ERROR_IMG_NOT_SUPPORT_WATER = 8;

    /**
     * 不存在的字体文件
     */
    const ERROR_IMG_NOT_FOUND_FONT = 9;

    /**
     * 不支持的文字位置类型
     */
    const ERROR_IMG_NOT_SUPPORT_FONT = 10;

    /**
     * 错误的颜色值
     */
    const ERROR_IMG_FAILD_COLOR = 11;
}
