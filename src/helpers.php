<?php

/*
|--------------------------------------------------------------------------
| 工具类函数支持
|--------------------------------------------------------------------------
| 工具类函数定义文件
|
*/

use mon\util\Tool;
use mon\util\Common;
use mon\util\Validate;
use mon\util\Collection;

if (!function_exists('buildURL')) {
    /**
     * 构建生成URL
     *
     * @param string $url URL路径
     * @param array $vars 传参
     * @return string
     */
    function buildURL($url, array $vars = [])
    {
        return Tool::instance()->buildURL($url, $vars);
    }
}

if (!function_exists('check')) {
    /**
     * 验证格式
     *
     * @param string $type  格式类型，支持validate类的默认的所有方式
     * @param array $args   可变参数
     * @throws \ErrorException
     * @return boolean
     */
    function check($type, ...$args)
    {
        static $validate = null;
        if (is_null($validate)) {
            $validate = new Validate();
        }
        if (method_exists($validate, $type)) {
            return call_user_func_array([$validate, $type], (array) $args);
        }
        throw new \ErrorException('不支持的验证类型[' . $type . ']');
    }
}

if (!function_exists('dd')) {
    /**
     * 调试方法(浏览器友好处理)
     *
     * @param mixed     $var    变量
     * @param boolean   $echo   是否输出 默认为true 如果为false 则返回输出字符串
     * @param string    $label  标签 默认为空
     * @param boolean   $strict 是否严谨 默认为true
     * @return void|string
     */
    function dd($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
    {
        return Tool::instance()->dd($var, $echo, $label, $flags);
    }
}

if (!function_exists('collection')) {
    /**
     * 数组集合
     *
     * @param array $data   操作数组
     * @return Collection
     */
    function collection(array $data)
    {
        return new Collection($data);
    }
}

if (!function_exists('is_wx')) {
    /**
     * 判断是否为微信浏览器发起的请求
     *
     * @return boolean
     */
    function is_wx()
    {
        return Tool::instance()->is_wx();
    }
}

if (!function_exists('is_android')) {
    /**
     * 判断是否为安卓发起的请求
     *
     * @return boolean
     */
    function is_android()
    {
        return Tool::instance()->is_android();
    }
}

if (!function_exists('is_ios')) {
    /**
     * 判断是否为苹果发起的请求
     *
     * @return boolean
     */
    function is_ios()
    {
        return Tool::instance()->is_ios();
    }
}

if (!function_exists('ip')) {
    /**
     * 获取客户端的IP地址
     *
     * @return string
     */
    function ip()
    {
        return Tool::instance()->ip();
    }
}

if (!function_exists('randString')) {
    /**
     * 产生随机字串，可用来自动生成密码
     * 默认长度6位 字母和数字混合 支持中文
     *
     * @param string $len       长度
     * @param string $type      字串类型，0:字母;1:数字;2:大写字母;3:小写字母;4:中文;5:字母数字混合;othor:过滤掉混淆字符的字母数字组合
     * @param string $addChars  额外字符
     * @return string
     */
    function randString($len = 6, $type = '', $addChars = '')
    {
        return Common::instance()->randString($len, $type, $addChars);
    }
}
