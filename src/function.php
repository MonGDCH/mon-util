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
     * @return void
     */
    function array2DSort($array, $keys, $sort = SORT_DESC)
    {
        return Common::instance()->array2DSort($array, $keys, $sort);
    }
}

if (!function_exists('get_first_char')) {
    /**
     * php获取中文字符拼音首字母
     *
     * @param  string $str 中文字符串
     * @return string
     */
    function get_first_char($str)
    {
        return Common::instance()->get_first_char($str);
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

if (!function_exists('msubstr')) {
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
    function msubstr($str, $length, $start = 0, $charset = "utf-8", $suffix = true)
    {
        return Common::instance()->msubstr($str, $start, $length, $charset, $suffix);
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
        $validate = Validate::instance();
        if (method_exists($validate, $type)) {
            return call_user_func_array([$validate, $type], (array) $args);
        }
        throw \Exception('不支持的验证类型[' . $type . ']');
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

if (!function_exists('exportZip')) {
    /**
     * 文件打包下载
     *
     * @param string $downloadZip 打包后下载的文件名
     * @param array $list 打包文件组
     * @return void
     */
    function exportZip($downloadZip, array $list)
    {
        return Tool::instance()->exportZip($downloadZip, $list);
    }
}

if (!function_exists('unZip')) {
    /**
     * 解压压缩包
     *
     * @param string $zipName 要解压的压缩包
     * @param string $dest 解压到指定目录
     * @return boolean
     */
    function unZip($zipName, $dest)
    {
        return Tool::instance()->unZip($zipName, $dest);
    }
}

if (!function_exists('qrcode')) {
    /**
     * 输入图片二维码
     *
     * @param string  $text 生成二维码的内容
     * @param boolean|string $outfile 输入文件, false则不输入，字符串路径则表示保存路径
     * @param integer $level 压缩错误级别
     * @param integer $size 图片尺寸 0-3
     * @param integer $margin 图片边距
     * @param boolean $saveandprint 是否输入图片及保存文件
     * @return void
     */
    function qrcode($text, $outfile = false, $level = 0, $size = 8, $margin = 1, $saveandprint = false)
    {
        return Tool::instance()->qrcode($text, $outfile, $level, $size, $margin, $saveandprint);
    }
}
