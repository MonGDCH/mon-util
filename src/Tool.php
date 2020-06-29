<?php

namespace mon\util;

use mon\util\Instance;

/**
 * 常用工具类(数据渲染)
 *
 * @author Mon <98558837@qq.com>
 * @version v1.0.0
 */
class Tool
{
    use Instance;

    /**
     * 调试方法(浏览器友好处理)
     *
     * @param mixed     $var    变量
     * @param boolean   $echo   是否输出 默认为True 如果为false 则返回输出字符串
     * @param string    $label  标签 默认为空
     * @param boolean   $strict 是否严谨 默认为true
     * @return void|string
     */
    public function debug($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
    {
        $label = (null === $label) ? '' : rtrim($label) . ':';
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
        if (PHP_SAPI == 'cli' || PHP_SAPI == 'cli-server') {
            // CLI模式
            $output = PHP_EOL . $label . $output . PHP_EOL;
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, $flags);
            }
            $output = '<pre>' . $label . $output . '</pre>';
        }
        if ($echo) {
            echo ($output);
            return;
        } else {
            return $output;
        }
    }

    /**
     * 判断是否为微信浏览器发起的请求
     *
     * @return boolean
     */
    public function is_wx()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }

        return false;
    }

    /**
     * 判断是否为安卓发起的请求
     *
     * @return boolean
     */
    public function is_android()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false) {
            return true;
        }

        return false;
    }

    /**
     * 判断是否为苹果发起的请求
     *
     * @return boolean 
     */
    public function is_ios()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false) {
            return true;
        }

        return false;
    }

    /**
     * 创建基于cookies的Token
     *
     * @param  string  $ticket  验证秘钥
     * @param  string  $salt    加密盐
     * @param  integer $expire  Cookie生存时间
     * @return array
     */
    public function createTicket($ticket, $salt = "MonUtil", $expire = 3600)
    {
        $now = time();
        $token = md5($salt . $now . $ticket);

        $_COOKIE['_token_']     = $token;
        $_COOKIE['_tokenTime_'] = $now;
        setcookie("_token_", $token, $now + $expire, "/");
        setcookie("_tokenTime_", $now, $now + $expire, "/");

        return array('token' => $token, 'tokenTime' => $now);
    }

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
    public function checkTicket($ticket, $token = null, $tokenTime = null, $salt = "MonUtil", $destroy = true, $expire = 3600)
    {
        $token        = empty($token) ? $_COOKIE['_token_'] : $token;
        $tokenTime    = empty($tokenTime) ? $_COOKIE['_tokenTime_'] : $tokenTime;
        $now          = time();
        $result       = false;

        if (empty($token) || empty($tokenTime)) {
            return $result;
        }

        //校验
        $check = md5($salt . $tokenTime . $ticket);
        $timeGap = $now - $tokenTime;
        if ($check == $token && $timeGap <= $expire) {
            $result = true;
        }

        // 判断是否需要清空Cookie
        if ($destroy) {
            setcookie("_token_", "", $now - $expire, "/");
            setcookie("_tokenTime_", "", $now - $expire, "/");
        }

        return $result;
    }

    /**
     * 导出CSV格式文件
     *
     * @param  string $filename  导出文件名
     * @param  array  $title     表格标题列表(生成："序号,姓名,性别,年龄\n")
     * @param  array  $titleKey  表格标题列表对应键名(注意：对应title排序)
     * @param  array  $data      导出数据
     * @return void
     */
    public function exportCsv($filename, $title, $titleKey = [], $data = [])
    {
        // 清空之前的输出
        ob_get_contents() && ob_end_clean();

        // 获取标题
        $title  = implode(",", $title) . "\n";
        $str    = @iconv('utf-8', 'gbk', $title); // 中文转码GBK
        $len    = count($titleKey);

        // 遍历二维数组获取需要生成的数据
        foreach ($data as $key => $value) {
            // 遍历键列表获取对应数据中的键值
            for ($i = 0; $i < $len; $i++) {
                $val = @iconv('utf-8', 'gbk', $value[$titleKey[$i]]);
                // 判断是否为最后一列数据
                if ($i == ($len - 1)) {
                    $str .= $val . "\n";
                } else {
                    $str .= $val . ",";
                }
            }
        }

        // 输出头信息
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename . ".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        header("Content-Length: " . strlen($str));
        header("Content-Transfer-Encoding: binary");
        // 输出文件
        echo $str;
    }

    /**
     * 导出XML
     *
     * @param  array  $data     输出的数据
     * @param  string $root     根节点
     * @param  string $encoding 编码
     * @return string
     */
    public function exportXML(array $data, $root = "Mon", $encoding = 'UTF-8')
    {
        // 清空之前的输出
        ob_get_contents() && ob_end_clean();
        header("Content-type:text/xml");
        $xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml .= "<{$root}>";
        $xml .= $this->dataToXML($data);
        $xml .= "</{$root}>";

        return $xml;
    }

    /**
     * 递归转换数组数据为XML，只作为exportXML的辅助方法使用
     *
     * @param  array  $data 输出的数据
     * @return string
     */
    public function dataToXML(array $data)
    {
        $xml = '';
        foreach ($data as $key => $val) {
            $xml .= "<{$key}>";
            $xml .= (is_array($val) || is_object($val)) ? $this->dataToXML($val) : $val;
            $xml .= "</{$key}>";
        }

        return $xml;
    }

    /**
     * 隐藏银行卡号
     *
     * @param  string $id 银行卡号
     * @return string
     */
    public function hideBankcard($id)
    {
        if (empty($id)) {
            return '';
        }
        //截取银行卡号前4位
        $prefix = substr($id, 0, 4);
        //截取银行卡号后4位
        $suffix = substr($id, -4, 4);
        return $prefix . " **** **** **** " . $suffix;
    }

    /**
     * 隐藏手机号
     *
     * @param  string $id 手机号
     * @return string
     */
    public function hideMoble($id)
    {
        if (empty($id)) {
            return '';
        }
        return substr_replace($id, '****', 3, 4);
    }

    /**
     * 删除字符串中的空格
     *
     * @param $str 要删除空格的字符串
     * @return $str 返回删除空格后的字符串
     */
    public function trimall($str)
    {
        $str = str_replace(" ", '', $str);
        $str = str_ireplace(array("\r", "\n", '\r', '\n'), '', $str);

        return $str;
    }

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
    public function hidestr($string, $start = 0, $length = 0, $re = '*')
    {
        if (empty($string)) {
            return false;
        }
        $strarr = [];
        $mb_strlen = mb_strlen($string);
        while ($mb_strlen) {
            $strarr[] = mb_substr($string, 0, 1, 'utf8');
            $string = mb_substr($string, 1, $mb_strlen, 'utf8');
            $mb_strlen = mb_strlen($string);
        }
        $strlen = count($strarr);
        $begin  = $start >= 0 ? $start : ($strlen - abs($start));
        $end    = $last   = $strlen - 1;
        if ($length > 0) {
            $end  = $begin + $length - 1;
        } elseif ($length < 0) {
            $end -= abs($length);
        }

        for ($i = $begin; $i <= $end; $i++) {
            $strarr[$i] = $re;
        }
        if ($begin >= $end || $begin >= $last || $end > $last) {
            return false;
        }

        return implode('', $strarr);
    }

    /**
     * 安全IP检测，支持IP段检测
     *
     * @param string $ip 要检测的IP，','分割
     * @param string|array $ips  白名单IP或者黑名单IP
     * @return boolean true 在白名单或者黑名单中，否则不在
     */
    public function safe_ip($ip, $ips)
    {
        // IP用"," 例如白名单IP：192.168.1.13,123.23.23.44,193.134.*.*
        if (is_string($ips)) {
            $ips = explode(",", $ips);
        }
        if (in_array($ip, $ips)) {
            return true;
        }
        // IP段验证
        $ipregexp = implode('|', str_replace(array('*', '.'), array('\d+', '\.'), $ips));
        $rs = preg_match("/^(" . $ipregexp . ")$/", $ip);
        if ($rs) {
            return true;
        }

        return false;
    }

    /**
     * 发送TCP请求
     *
     * @param  string   $ip     IP地址
     * @param  integer  $port   端口
     * @param  string   $cmd    发送的内容套接字
     * @param  string   &$iRecv 引用返回的响应内容
     * @return void
     */
    public function sendCmdTCP($ip, $port, $cmd, &$iRecv)
    {
        $iRecv = "";
        $wbuff = $cmd;
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        @socket_connect($socket, $ip, $port);
        @socket_send($socket, $wbuff, strlen($wbuff), 0);
        socket_recv($socket, $iRecv, 16384, 0);
        socket_close($socket);
    }

    /**
     * 发送UDP请求
     *
     * @param  string   $ip      IP地址
     * @param  integer  $port    端口
     * @param  string   $cmd     发送的内容套接字
     * @param  string   &$result 引用返回的响应内容
     * @return boolean
     */
    public static function sendCmdUDP($ip, $port, $cmd, &$result)
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            return false;
        }
        $result = @socket_connect($socket, $ip, $port);
        if ($result < 0) {
            return false;
        }
        @socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);

        $iSent = @socket_write($socket, $cmd, strlen($cmd));
        if ($iSent === false) {
            if (socket_last_error() != SOCKET_EWOULDBLOCK) {
                return false;
            }
        }
        return true;
    }
}
