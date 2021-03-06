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
use mon\util\IdCode;
use mon\util\Validate;

if (!function_exists('check')) {
    /**
     * 验证格式
     *
     * @param string $type  格式类型，支持validate类的默认的所有方式
     * @param array $args   可变参数
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
        throw new \Exception('不支持的验证类型[' . $type . ']');
    }
}


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
        return Common::instance()->trimall($str);
    }
}

if (!function_exists('hidestr')) {
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
        return Common::instance()->hidestr($string, $start, $length, $re);
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

if (!function_exists('safe_ip')) {
    /**
     * 安全IP检测，支持IP段检测
     *
     * @param string $ip 要检测的IP，','分割
     * @param string|array $ips  白名单IP或者黑名单IP
     * @return boolean true 在白名单或者黑名单中，否则不在
     */
    function safe_ip($ip, $ips)
    {
        return Tool::instance()->safe_ip($ip, $ips);
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

if (!function_exists('mod')) {
    /**
     * 获取余数
     *
     * @param  integer $bn 被除数
     * @param  integer $sn 除数
     * @return integer 余
     */
    function mod($bn, $sn)
    {
        return Common::instance()->mod($bn, $sn);
    }
}

if (!function_exists('ip2long_positive')) {
    /**
     * 返回正数的ip2long值
     *
     * @param  string $ip ip
     * @return integer
     */
    function ip2long_positive($ip)
    {
        return Common::instance()->ip2long_positive($ip);
    }
}

if (!function_exists('ip2long_mon')) {
    /**
     * IP地址转为数字地址
     * php 的 ip2long 这个函数有问题
     * 133.205.0.0 ==>> 2244804608
     *
     * @param string $ip 要转换的 ip 地址
     * @return integer
     */
    function ip2long_mon($ip)
    {
        return Common::instance()->ip2long_mon($ip);
    }
}

if (!function_exists('strToMap')) {
    /**
     * URI字符串转数组
     *
     * @param  string $str 入参，待转换的字符串
     * @return array 字符数组
     */
    function strToMap($str)
    {
        return Common::instance()->strToMap($str);
    }
}

if (!function_exists('mapToStr')) {
    /**
     * 数组转字符串
     *
     * @param  array $map 入参，待转换的数组
     * @return string
     */
    function mapToStr($map)
    {
        return Common::instance()->mapToStr($map);
    }
}

if (!function_exists('array_2D_unique')) {
    /**
     * 二维数组去重(键&值不能完全相同)
     *
     * @param  array $arr    需要去重的数组
     * @return array
     */
    function array_2D_unique($arr)
    {
        return Common::instance()->array_2D_unique($arr);
    }
}

if (!function_exists('array_2D_value_unique')) {
    /**
     * 二维数组去重(值不能相同)
     *
     * @param  array $arr    需要去重的数组
     * @return array
     */
    function array_2D_value_unique($arr)
    {
        return Common::instance()->array_2D_value_unique($arr);
    }
}

if (!function_exists('isAssoc')) {
    /**
     * 是否为关联数组
     *
     * @param  array   $array 验证码的数组
     * @return boolean
     */
    function isAssoc($arr)
    {
        return Common::instance()->isAssoc($arr);
    }
}

if (!function_exists('array2DSort')) {
    /**
     * 二维数组排序
     *
     * @param array $array  排序的数组
     * @param string $keys  排序的键名
     * @param integer $sort 排序方式，默认值：SORT_DESC
     * @return array
     */
    function array2DSort($array, $keys, $sort = SORT_DESC)
    {
        return Common::instance()->array2DSort($array, $keys, $sort);
    }
}

if (!function_exists('getFirstChar')) {
    /**
     * php获取中文字符拼音首字母
     *
     * @param  string $str 中文字符串
     * @return string
     */
    function getFirstChar($str)
    {
        return Common::instance()->getFirstChar($str);
    }
}

if (!function_exists('uuid')) {
    /**
     * 生成UUID 单机使用
     *
     * @return string
     */
    function uuid()
    {
        return Common::instance()->uuid();
    }
}

if (!function_exists('keyGen')) {
    /**
     * 生成Guid主键
     *
     * @return string
     */
    function keyGen()
    {
        return Common::instance()->keyGen();
    }
}

if (!function_exists('mSubstr')) {
    /**
     * 字符串截取，支持中文和其他编码
     *
     * @param string $str       需要转换的字符串
     * @param string $start     开始位置
     * @param string $length    截取长度
     * @param string $charset   编码格式
     * @param string $suffix    截断显示字符
     * @return string
     */
    function mSubstr($str, $length, $start = 0, $charset = "utf-8", $suffix = true)
    {
        return Common::instance()->mSubstr($str, $start, $length, $charset, $suffix);
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

if (!function_exists('iconv_recursion')) {
    /**
     * 递归转换字符集
     *
     * @param  mixed  $data         要转换的数据
     * @param  string $out_charset  输出编码
     * @param  string $in_charset   输入编码
     * @return mixed
     */
    function iconv_recursion($data, $out_charset, $in_charset)
    {
        return Common::instance()->iconv_recursion($data, $out_charset, $in_charset);
    }
}

if (!function_exists('getDistance')) {
    /**
     * 获取两坐标距离
     *
     * @param float $lng1 经度1
     * @param float $lat1 纬度1
     * @param float $lng2 经度2
     * @param float $lat2 纬度2
     *
     * @return float
     */
    function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        return Tool::instance()->getDistance($lng1, $lat1, $lng2, $lat2);
    }
}

if (!function_exists('getSquarePoint')) {
    /**
     * 计算某个经纬度的周围某段距离的正方形的四个点
     * $lng = '116.655540';
     * $lat = '39.910980';
     * $squares = GetSquarePoint($lng, $lat);
     *
     * print_r($squares);
     * $info_sql = "select id,locateinfo,lat,lng from `lbs_info` where lat<>0 and lat>{$squares['right-bottom']['lat']} and lat<{$squares['left-top']['lat']} and lng>{$squares['left-top']['lng']} and lng<{$squares['right-bottom']['lng']} ";
     * 
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param float $distance 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
     * @return array 正方形的四个点的经纬度坐标
     */
    function getSquarePoint($lng, $lat, $distance = 0.5)
    {
        return Tool::instance()->getSquarePoint($lng, $lat, $distance);
    }
}

if (function_exists('getBaseName')) {
    /**
     * 获取文件的名称，兼容中文名
     *
     * @return string
     */
    function getBaseName($filename)
    {
        return Tool::instance()->getBaseName($filename);
    }
}

if (!function_exists('id2code')) {
    /**
     * id转code字符串
     * 
     * @param integer $id 要加密的id值
     * @return string
     */
    function id2code($id)
    {
        return IdCode::instance()->id2code($id);
    }
}

if (!function_exists('code2id')) {
    /**
     * code转ID
     *
     * @param string $code 加密生成的code
     * @return integer
     */
    function code2id($code)
    {
        return IdCode::instance()->code2id($code);
    }
}

if (!function_exists('specCartesian')) {
    /**
     * 笛卡尔积生成规格
     *
     * @example 
     *  $data = [["title" => "颜色","value" => ["黑色", "白色", "蓝色"]],["title" => "尺码","value" => ["S", "M", "L", "XL", "XXL"]],["title" => "长度","value" => ["5分裤", "7分裤", "9分裤", "长裤"]]];
     *  debug(specCartesian($data));
     *
     * @param array $arr   要进行笛卡尔积的二维数组
     * @return array
     */
    function specCartesian($arr)
    {
        return Common::instance()->specCartesian($arr);
    }
}

if (!function_exists('rgb2hex')) {
    /**
     * RGB颜色值转十六进制
     * 
     * @param string|array $reg reg颜色值
     * @return string
     */
    function rgb2hex($rgb)
    {
        return Tool::instance()->rgb2hex($rgb);
    }
}

if (!function_exists('hex2rgb')) {
    /**
     * 十六进制转RGB颜色
     * 
     * @param string $hex_color 十六进制颜色值
     * @return string
     */
    function hex2rgb($hex_color)
    {
        return Tool::instance()->hex2rgb($hex_color);
    }
}

if (!function_exists('str2ascii')) {
    /**
     * 字符串转Ascii码
     *
     * @param string $str 字符串  
     * @return string
     */
    function str2ascii($str)
    {
        return Common::instance()->str2ascii($str);
    }
}


if (!function_exists('ascii2str')) {
    /**
     * Ascii码转字符串
     *
     * @param string $ascii Ascii码
     * @return string
     */
    function ascii2str($str)
    {
        return Common::instance()->ascii2str($str);
    }
}
