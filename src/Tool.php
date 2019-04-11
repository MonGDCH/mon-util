<?php
namespace mon\util;

/**
 * 常用工具类(数据渲染)
 *
 * @author Mon <98558837@qq.com>
 * @version v1.0
 */
class Tool
{
    /**
     * 本类单例
     * 
     * @var [type]
     */
    protected static $instance;

    /**
     * 单例初始化
     *
     * @return Auth
     */
    public static function instance()
    {
        if(is_null(self::$instance)){
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 调试方法(浏览器友好处理)
     *
     * @param mixed $var 变量
     * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
     * @param string $label 标签 默认为空
     * @param boolean $strict 是否严谨 默认为true
     * @return void|string
     */
    public function debug($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
    {
        $label = (null === $label) ? '' : rtrim($label) . ':';
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
        if( PHP_SAPI == 'cli' || PHP_SAPI == 'cli-server' ){
            // CLI模式
            $output = PHP_EOL . $label . $output . PHP_EOL;
        }
        else{
            if(!extension_loaded('xdebug')){
                $output = htmlspecialchars($output, $flags);
            }
            $output = '<pre>' . $label . $output . '</pre>';
        }
        if($echo){
            echo($output);
            return;
        }
        else{
            return $output;
        }
    }

    /**
     * 创建基于cookies的Token
     *
     * @param  String  $ticket 验证秘钥
     * @param  integer $expire Cookie生存时间
     * @return cookie&array
     */
    public function createTicket($ticket, $ticket_title = "Mon", $expire = 3600)
    {
        // 自定义Token头
        $ticket_title = 'LAF_' . $ticket_header;
        $now = time();
        $token = md5($ticket_title . $now . $ticket);

        $_COOKIE['_token_']     = $token;
        $_COOKIE['_tokenTime_'] = $now;
        setcookie("_token_", $token, $now + $expire, "/");
        setcookie("_tokenTime_", $now, $now + $expire, "/");

        return array('token' => $token, 'tokenTime' => $now);
    }

    /**
     * 校验基于cookies的Token
     *
     * @param  String  $ticket      验证秘钥
     * @param  String  $token       Token值
     * @param  String  $tokenTime   Token创建时间
     * @param  boolean $destroy     是否清除Cookie
     * @param  integer $expire      Cookie生存时间
     * @return bool
     */
    public function checkTicket($ticket, $token = null, $tokenTime = null, $ticket_title = "Mon", $destroy = true, $expire = 3600)
    {
        // 自定义Token头
        $ticket_title = 'LAF_' . $ticket_header;
        $token        = empty($token) ? $_COOKIE['_token_'] : $token;
        $tokenTime    = empty($tokenTime) ? $_COOKIE['_tokenTime_'] : $tokenTime;
        $now          = time();
        $result       = false;

        if(empty($token) || empty($tokenTime)){
            return $result;
        }

        //校验
        $check = md5($ticket_title.$tokenTime.$ticket);
        $timeGap = $now - $tokenTime;
        if($check == $token && $timeGap <= $expire){
            $result = true;
        }

        // 判断是否需要清空Cookie
        if($destroy){
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
     * @return file
     */
    public function exportCsv($filename, $title, $titleKey = array(), $data = array())
    {
        // 清空之前的输出
        ob_get_contents() && ob_end_clean();

        // 获取标题
        $title  = implode(",", $title) . "\n";
        $str    = @iconv('utf-8','gbk',$title); // 中文转码GBK
        $len    = count($titleKey);

        // 遍历二维数组获取需要生成的数据
        foreach($data as $key => $value)
        {
            // 遍历键列表获取对应数据中的键值
            for($i = 0; $i < $len; $i++)
            {
                $val = @iconv('utf-8','gbk',$value[$titleKey[$i]]);

                // 判断是否为最后一列数据
                if($i == ($len - 1)){
                    $str .= $val . "\n";
                }
                else{
                    $str .= $val . ",";
                }
            }
        }

        // 输出头信息
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$filename . ".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        header("Content-Length: " . strlen($str));
        header("Content-Transfer-Encoding: binary");
        // 输出文件
        echo $str;
    }

    /**
     * 隐藏银行卡号
     *
     * @param  string $id 银行卡号
     * @return [type]     [description]
     */
    public function hideBankcard($id)
    {
        if(empty($id)){
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
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function hideMoble($id)
    {
        if(empty($id)){
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
     * @param int       $start    规定在字符串的何处开始，
     *                            正数 - 在字符串的指定位置开始
     *                            负数 - 在从字符串结尾的指定位置开始
     *                            0 - 在字符串中的第一个字符处开始
     * @param int       $length   可选。规定要隐藏的字符串长度。默认是直到字符串的结尾。
     *                            正数 - 从 start 参数所在的位置隐藏
     *                            负数 - 从字符串末端隐藏
     * @param string    $re       替代符
     * @return string   处理后的字符串
     */
    public function hidestr($string, $start = 0, $length = 0, $re = '*')
    {
        if(empty($string)){
            return false;
        }
        $strarr = array();
        $mb_strlen = mb_strlen($string);
        while($mb_strlen)
        {
            $strarr[] = mb_substr($string, 0, 1, 'utf8');
            $string = mb_substr($string, 1, $mb_strlen, 'utf8');
            $mb_strlen = mb_strlen($string);
        }
        $strlen = count($strarr);
        $begin  = $start >= 0 ? $start : ($strlen - abs($start));
        $end    = $last   = $strlen - 1;
        if($length > 0){
            $end  = $begin + $length - 1;
        }
        elseif($length < 0){
            $end -= abs($length);
        }

        for($i=$begin; $i<=$end; $i++)
        {
            $strarr[$i] = $re;
        }
        if($begin >= $end || $begin >= $last || $end > $last){
            return false;
        }

        return implode('', $strarr);
    }

}