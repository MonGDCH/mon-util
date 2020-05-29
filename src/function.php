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

if (!function_exists('debug')) {
    /**
     * 调试方法(浏览器友好处理)
     *
     * @param mixed     $var    变量
     * @param boolean   $echo   是否输出 默认为True 如果为false 则返回输出字符串
     * @param string    $label  标签 默认为空
     * @param boolean   $strict 是否严谨 默认为true
     * @return void|string
     */
    function debug($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
    {
        return Tool::instance()->debug($var, $echo, $label, $flags);
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

if (!function_exists('createTicket')) {
    /**
     * 创建基于cookies的Token
     *
     * @param  string  $ticket  验证秘钥
     * @param  string  $salt    加密盐
     * @param  integer $expire  Cookie生存时间
     * @return array
     */
    function createTicket($ticket, $salt = "MonUtil", $expire = 3600)
    {
        return Tool::instance()->createTicket($ticket, $salt, $expire);
    }
}

if (!function_exists('checkTicket')) {
    /**
     * 校验基于cookies的Token
     *
     * @param  string  $ticket      验证秘钥
     * @param  string  $token       Token值
     * @param  string  $tokenTime   Token创建时间
     * @param  string  $salt        加密盐
     * @param  boolean $destroy     是否清除Cookie
     * @param  integer $expire      Cookie生存时间
     * @return boolean
     */
    function checkTicket($ticket, $token = null, $tokenTime = null, $salt = "MonUtil", $destroy = true, $expire = 3600)
    {
        return Tool::instance()->checkTicket($ticket, $token, $tokenTime, $salt, $destroy, $expire);
    }
}

if (!function_exists('exportCsv')) {
    /**
     * 导出CSV格式文件
     *
     * @param  string $filename  导出文件名
     * @param  array  $title     表格标题列表(生成："序号,姓名,性别,年龄\n")
     * @param  array  $titleKey  表格标题列表对应键名(注意：对应title排序)
     * @param  array  $data      导出数据
     * @return void
     */
    function exportCsv($filename, $title, $titleKey = [], $data = [])
    {
        return Tool::instance()->exportCsv($filename, $title, $titleKey, $data);
    }
}

if (!function_exists('exportCsv')) {
    /**
     * 导出XML
     *
     * @param  array  $data     输出的数据
     * @param  string $root     根节点
     * @param  string $encoding 编码
     * @return string
     */
    function exportXML(array $data, $root = "Mon", $encoding = 'UTF-8')
    {
        return Tool::instance()->exportXML($data, $root, $encoding);
    }
}

if (!function_exists('hideBankcard')) {
    /**
     * 隐藏银行卡号
     *
     * @param  string $id 银行卡号
     * @return string
     */
    function hideBankcard($id)
    {
        return Tool::instance()->hideBankcard($id);
    }
}

if (!function_exists('hideMoble')) {
    /**
     * 隐藏手机号
     *
     * @param  string $id 手机号
     * @return string
     */
    function hideMoble($id)
    {
        return Tool::instance()->hideMoble($id);
    }
}

if (!function_exists('trimall')) {
    /**
     * 删除字符串中的空格
     *
     * @param $str 要删除空格的字符串
     * @return $str 返回删除空格后的字符串
     */
    function trimall($str)
    {
        return Tool::instance()->trimall($str);
    }
}

if (!function_exists('trimall')) {
    /**
     * 将一个字符串部分字符用$re替代隐藏
     *
     * @param string    $string   待处理的字符串
     * @param integer   $start    规定在字符串的何处开始，
     *                            正数 - 在字符串的指定位置开始
     *                            负数 - 在从字符串结尾的指定位置开始
     *                            0 - 在字符串中的第一个字符处开始
     * @param integer   $length   可选。规定要隐藏的字符串长度。默认是直到字符串的结尾。
     *                            正数 - 从 start 参数所在的位置隐藏
     *                            负数 - 从字符串末端隐藏
     * @param string    $re       替代符
     * @return string   处理后的字符串
     */
    function hidestr($string, $start = 0, $length = 0, $re = '*')
    {
        return Tool::instance()->hidestr($string, $start, $length, $re);
    }
}

if (!function_exists('encodeEX')) {
    /**
     * 字符串编码过滤（中文、英文、数字不过滤，只过滤特殊字符）
     *
     * @param  string $src 安全转码的字符串
     * @return string
     */
    function encodeEX($src)
    {
        return Common::instance()->encodeEX($src);
    }
}

if (!function_exists('decodeEX')) {
    /**
     * 字符串编码过滤（中文、英文、数字不过滤，只过滤特殊字符）
     *
     * @param  string $src 安全转码的字符串
     * @return string
     */
    function decodeEX($src)
    {
        return Common::instance()->decodeEX($src);
    }
}

if (!function_exists('encryption')) {
    /**
     * 字符串加密方法
     *
     * @param  string $str  加密的字符串
     * @param  string $salt 加密盐
     * @return string
     */
    function encryption($str, $salt)
    {
        return Common::instance()->encryption($str, $salt);
    }
}

if (!function_exists('decryption')) {
    /**
     * 字符串解密方法
     *
     * @param  string $str  解密的字符串
     * @param  string $salt 解密的盐
     * @return string
     */
    function decryption($str, $salt)
    {
        return Common::instance()->decryption($str, $salt);
    }
}

if (!function_exists('isXDigit')) {
    /**
     * 判断是否为16进制，由于PHP没有相关的API，所以折中处理
     *
     * @param  string  $src 验证的字符串
     * @return boolean
     */
    function isXDigit($src)
    {
        return Common::instance()->isXDigit($src);
    }
}

if (!function_exists('isUtf8')) {
    /**
     * 检查字符串是否是UTF8编码
     *
     * @param string $string 验证的字符串
     * @return boolean
     */
    function isUtf8($str)
    {
        return Common::instance()->isXDigit($str);
    }
}

if (!function_exists('Kmod')) {
    /**
     * 获取余数
     *
     * @param  integer $bn 被除数
     * @param  integer $sn 除数
     * @return integer 余
     */
    function Kmod($bn, $sn)
    {
        return Common::instance()->Kmod($bn, $sn);
    }
}
