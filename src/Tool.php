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
        if (mb_strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
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
        if (mb_strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false) {
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
        if (mb_strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || mb_strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false) {
            return true;
        }

        return false;
    }

    /**
     * 创建基于cookies的Token
     *
     * @param string  $ticket     验证秘钥
     * @param string  $salt       加密盐
     * @param integer $expire     Cookie生存时间
     * @param string  $tokenName  Cookie创建token的名称
     * @param string  $tokenTimeName  cookie创建token创建时间的名称
     * @return array
     */
    public function createTicket($ticket, $salt = "MonUtil", $expire = 3600, $tokenName = '_token_', $tokenTimeName = '_tokenTime_')
    {
        $now = time();
        $token = md5($salt . $now . $ticket);

        $_COOKIE[$tokenName] = $token;
        $_COOKIE[$tokenTimeName] = $now;
        setcookie($tokenName, $token, $now + $expire, "/");
        setcookie($tokenTimeName, $now, $now + $expire, "/");

        return ['token' => $token, 'tokenTime' => $now];
    }

    /**
     * 校验基于cookies的Token
     *
     * @param string  $ticket      验证秘钥
     * @param string  $token       Token值
     * @param string  $tokenTime   Token创建时间
     * @param string  $salt        加密盐
     * @param boolean $destroy     是否清除Cookie
     * @param integer $expire      Cookie生存时间
     * @param string  $tokenName   Cookie创建token的名称
     * @param string  $tokenTimeName  Cookie创建token创建时间的名称
     * @return boolean
     */
    public function checkTicket($ticket, $token = null, $tokenTime = null, $salt = "MonUtil", $destroy = true, $expire = 3600, $tokenName = '_token_', $tokenTimeName = '_tokenTime_')
    {
        $token = empty($token) ? (isset($_COOKIE[$tokenName]) ? $_COOKIE[$tokenName] : '') : $token;
        $tokenTime = empty($tokenTime) ? (isset($_COOKIE[$tokenTimeName]) ? $_COOKIE[$tokenTimeName] : 0) : $tokenTime;
        $now = time();
        $result = false;

        if (empty($token) || empty($tokenTime)) {
            return $result;
        }

        // 校验
        $check = md5($salt . $tokenTime . $ticket);
        $timeGap = $now - $tokenTime;
        if ($check == $token && $timeGap <= $expire) {
            $result = true;
        }

        // 判断是否需要清空Cookie
        if ($destroy) {
            setcookie($tokenName, "", $now - $expire, "/");
            setcookie($tokenTimeName, "", $now - $expire, "/");
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
        header("Content-Length: " . mb_strlen($str));
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
        $prefix = mb_substr($id, 0, 4);
        //截取银行卡号后4位
        $suffix = mb_substr($id, -4, 4);
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
     * 获取客户端的IP地址
     *
     * @return string
     */
    public function ip()
    {
        foreach (['X_FORWARDED_FOR', 'HTTP_X_FORWARDED_FOR', 'CLIENT_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (isset($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        return '';
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
        $ipregexp = implode('|', str_replace(['*', '.'], ['\d+', '\.'], $ips));
        $rs = preg_match("/^(" . $ipregexp . ")$/", $ip);
        if ($rs) {
            return true;
        }

        return false;
    }

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
    public function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        return 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
    }

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
    public function getSquarePoint($lng, $lat, $distance = 0.5)
    {
        if (empty($lng) || empty($lat)) {
            return '';
        };

        // 地球半径，平均半径为6371km
        $radius = 6371;
        $d_lng =  2 * asin(sin($distance / (2 * $radius)) / cos(deg2rad($lat)));
        $d_lng = rad2deg($d_lng);
        $d_lat = $distance / $radius;
        $d_lat = rad2deg($d_lat);

        return [
            'left-top'      => ['lat' => $lat + $d_lat, 'lng' => $lng - $d_lng],
            'right-top'     => ['lat' => $lat + $d_lat, 'lng' => $lng + $d_lng],
            'left-bottom'   => ['lat' => $lat - $d_lat, 'lng' => $lng - $d_lng],
            'right-bottom'  => ['lat' => $lat - $d_lat, 'lng' => $lng + $d_lng]
        ];
    }

    /**
     * 文件打包下载
     *
     * @param string $downloadZip 打包后下载的文件名
     * @param array $list 打包文件组
     * @return void
     */
    public function exportZip($downloadZip, array $list)
    {
        // 初始化Zip并打开
        $zip = new \ZipArchive();
        // 初始化
        $bool = $zip->open($downloadZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        // 打开文件
        if ($bool === TRUE) {
            foreach ($list as $key => $val) {
                // 把文件追加到Zip包
                $zip->addFile($val, basename($val));
            }
        } else {
            throw new \Exception('PHP-ZipArchive扩展打开文件失败, Code：' . $bool);
        }
        // 关闭Zip对象
        $zip->close();
        // 下载Zip包
        header('Cache-Control: max-age=0');
        header('Content-Description: File Transfer');
        header('Content-disposition: attachment; filename=' . basename($downloadZip));
        header('Content-Type: application/zip');                // zip格式的
        header('Content-Transfer-Encoding: binary');            // 二进制文件
        header('Content-Length: ' . filesize($downloadZip));    // 文件大小
        readfile($downloadZip);
    }

    /**
     * 解压压缩包
     *
     * @param string $zipName 要解压的压缩包
     * @param string $dest 解压到指定目录
     * @return boolean
     */
    public function unZip($zipName, $dest)
    {
        // 检测要解压压缩包是否存在
        if (!is_file($zipName)) {
            return false;
        }
        // 检测目标路径是否存在
        if (!is_dir($dest)) {
            mkdir($dest, 0777, true);
        }
        // 初始化Zip并打开
        $zip = new \ZipArchive();
        // 打开并解压
        if ($zip->open($zipName)) {
            $zip->extractTo($dest);
            $zip->close();
            return true;
        }
        return false;
    }

    /**
     * 输出下载文件
     * 可以指定下载显示的文件名，并自动发送相应的Header信息
     * 如果指定了content参数，则下载该参数的内容
     * 
     * @param string $filename 下载文件名
     * @param string $showname 下载显示的文件名
     * @param integer $expire  下载内容浏览器缓存时间
     * @return void
     */
    public function exportFile($filename, $showname = '', $expire = 180)
    {
        if (is_file($filename)) {
            $length = filesize($filename);
        } else {
            throw new \Exception($filename . '下载文件不存在!');
        }
        if (empty($showname)) {
            $showname = $filename;
        }
        $showname = $this->getBaseName($showname);;
        if (!empty($filename)) {
            $finfo = new \finfo(FILEINFO_MIME);
            $type  = $finfo->file($filename);
        } else {
            $type = "application/octet-stream";
        }
        // 发送Http Header信息 开始下载
        header("Pragma: public");
        header("Cache-Control: max-age=" . $expire);
        // header('Cache-Control: no-store, no-cache, must-revalidate');
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expire) . "GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . "GMT");
        header("Content-Disposition: attachment; filename=" . $showname);
        header("Content-Length: " . $length);
        header("Content-type: " . $type);
        header('Content-Encoding: none');
        header("Content-Transfer-Encoding: binary");
        // 清空文件的头部信息，解决文件下载无法打开问题
        ob_clean();
        flush();
        readfile($filename);
        exit();
    }

    /**
     * 获取文件的名称，兼容中文名
     *
     * @return string
     */
    public function getBaseName($filename)
    {
        return preg_replace('/^.+[\\\\\\/]/', '', $filename);
    }

    /**
     * 二维码图片
     *
     * @param string  $text 生成二维码的内容
     * @param boolean|string $outfile 保存文件, false则不保存，字符串路径则表示保存路径
     * @param integer $level 压缩错误级别
     * @param integer $size 图片尺寸 0-3
     * @param integer $margin 图片边距
     * @param boolean $saveandprint 是否输出图片及保存文件
     * @return void
     */
    public function qrcode($text, $outfile = false, $level = 0, $size = 8, $margin = 1, $saveandprint = false)
    {
        return QRcode::png($text, $outfile, $level, $size, $margin, $saveandprint);
    }

    /**
     * 下载保存文件
     *
     * @param string $url   下载的文件路径
     * @param string $savePath  保存的文件路径
     * @param string $filename  保存的文件名称
     * @param boolean $createDir    是否自动创建二级目录进行保存
     * @throws Exception
     * @return string
     */
    public function download($url, $savePath, $filename = '', $createDir = true)
    {
        $path = $createDir ? ($savePath . '/' . date('Ym') . '/') : ($savePath . '/');
        if (!is_dir($path)) {
            $create = mkdir($path, 0777, true);
            if (!$create) {
                throw new \Exception('创建下载文件保存目录失败!');
            }
        } else if (!is_writable($path)) {
            throw new \Exception('下载文件保存路径不可写入!');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        // 判断是否为https请求
        $ssl = strtolower(mb_substr($url, 0, 8)) == "https://" ? true : false;
        if ($ssl) {
            curl_setopt($ch, CURLOPT_SSLVERSION, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $file = curl_exec($ch);
        curl_close($ch);
        $filename = empty($filename) ? pathinfo($url, PATHINFO_BASENAME) : $filename;
        $resource = fopen($path . $filename, 'a');

        fwrite($resource, $file);
        fclose($resource);
        return $path . $filename;
    }

    /**
     * RGB颜色值转十六进制
     * 
     * @param string|array $reg reg颜色值
     * @return string
     */
    public function rgb2hex($rgb)
    {
        if (is_array($rgb)) {
            $match = $rgb;
        } else if (mb_strpos($rgb, 'rgb(') === 0) {
            // 判断是否为rgb开头
            $regexp = "/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/";
            preg_match($regexp, $rgb, $match);
            $re = array_shift($match);
        } else {
            // 直接传入rgb的值
            $match = explode(',', $rgb);
        }

        $hex_color = "#";
        $hex = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];
        for ($i = 0; $i < 3; $i++) {
            $r = null;
            $c = $match[$i];
            $hex_array = [];
            while ($c > 16) {
                $r = $c % 16;
                $c = ($c / 16) >> 0;
                array_push($hex_array, $hex[$r]);
            }
            array_push($hex_array, $hex[$c]);
            $ret = array_reverse($hex_array);
            $item = implode('', $ret);
            $item = str_pad($item, 2, '0', STR_PAD_LEFT);
            $hex_color .= $item;
        }
        return $hex_color;
    }

    /**
     * 十六进制转RGB颜色
     * 
     * @param string $hex_color 十六进制颜色值
     * @return string
     */
    public function hex2rgb($hex_color)
    {
        $color = str_replace('#', '', $hex_color);
        if (mb_strlen($color) > 3) {
            $rgb = [
                'r' => hexdec(mb_substr($color, 0, 2)),
                'g' => hexdec(mb_substr($color, 2, 2)),
                'b' => hexdec(mb_substr($color, 4, 2))
            ];
        } else {
            $color = $hex_color;
            $r = mb_substr($color, 0, 1) . mb_substr($color, 0, 1);
            $g = mb_substr($color, 1, 1) . mb_substr($color, 1, 1);
            $b = mb_substr($color, 2, 1) . mb_substr($color, 2, 1);
            $rgb = [
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            ];
        }
        return $rgb;
    }
}
