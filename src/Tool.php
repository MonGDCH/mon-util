<?php

declare(strict_types=1);

namespace mon\util;

use ZipArchive;
use RuntimeException;
use mon\util\Instance;
use InvalidArgumentException;

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
     * @param boolean   $echo   是否输出 默认为true 如果为false 则返回输出字符串
     * @param string    $label  标签 默认为空
     * @param integer   $flags  HTML过滤flag
     * @return void|string
     */
    public function dd($var, bool $echo = true, ?string $label = null, int $flags = \ENT_SUBSTITUTE)
    {
        $label = (null === $label) ? '' : rtrim($label) . ':';
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
        if (PHP_SAPI == 'cli') {
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
        }

        return $output;
    }

    /**
     * 构建生成URL
     *
     * @param string $url URL路径
     * @param array $vars 传参
     * @return string
     */
    public function buildURL(string $url, array $vars = []): string
    {
        // 判断是否包含域名,解析URL和传参
        if (strpos($url, '://') === false && strpos($url, '/') !== 0) {
            $info = parse_url($url);
            $url = $info['path'] ?: '';
            // 判断是否存在锚点, 解析请求串
            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];
                if (strpos($anchor, '?') !== false) {
                    // 解析参数
                    list($anchor, $info['query']) = explode('?', $anchor, 2);
                }
            }
        } elseif (strpos($url, '://') !== false) {
            // 存在协议头，自带domain
            $info = parse_url($url);
            $url = $info['host'];
            $scheme = isset($info['scheme']) ? $info['scheme'] : 'http';
            // 判断是否存在锚点,解析请求串
            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];
                if (strpos($anchor, '?') !== false) {
                    // 解析参数
                    list($anchor, $info['query']) = explode('?', $anchor, 2);
                }
            }
        }

        // 判断是否已传入URL,且URl中携带传参, 解析传参到$vars中
        if ($url && isset($info['query'])) {
            // 解析地址里面参数 合并到vars
            parse_str($info['query'], $params);
            $vars = array_merge($params, $vars);
            unset($info['query']);
        }

        // 还原锚点
        $anchor = !empty($anchor) ? '#' . $anchor : '';
        // 组装传参
        if (!empty($vars)) {
            $vars = http_build_query($vars);
            $url .= '?' . $vars;
        }
        $url .= $anchor;

        if (!isset($scheme)) {
            // 补全baseUrl
            $url = '/' . ltrim($url, '/');
        } else {
            $url = $scheme . '://' . $url;
        }

        return $url;
    }

    /**
     * 判断是否为微信浏览器发起的请求
     *
     * @param string $ua    请求user-agent
     * @return boolean
     */
    public function isWechat(string $ua = ''): bool
    {
        $ua = $ua ?: $_SERVER['HTTP_USER_AGENT'];

        return mb_strpos($ua, 'MicroMessenger') !== false;
    }

    /**
     * 判断是否为安卓发起的请求
     *
     * @param string $ua    请求user-agent
     * @return boolean
     */
    public function isAndroid(string $ua = ''): bool
    {
        $ua = $ua ?: $_SERVER['HTTP_USER_AGENT'];

        return mb_strpos($ua, 'Android') !== false;
    }

    /**
     * 判断是否为苹果发起的请求
     *
     * @param string $ua    请求user-agent
     * @return boolean 
     */
    public function isIOS(string $ua = ''): bool
    {
        $ua = $ua ?: $_SERVER['HTTP_USER_AGENT'];

        return (mb_strpos($ua, 'iPhone') !== false || mb_strpos($ua, 'iPad') !== false);
    }

    /**
     * 已否移动端
     *
     * @param array $server $_SERVER信息
     * @return boolean
     */
    public function isMobile(array $server = []): bool
    {
        $server = $server ?: $_SERVER;
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($server['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        //此条摘自TPM智能切换模板引擎，适合TPM开发
        if (isset($server['HTTP_CLIENT']) && 'PhoneClient' == $server['HTTP_CLIENT']) {
            return true;
        }
        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($server['HTTP_VIA'])) {
            //找不到为flase,否则为true
            return stristr($server['HTTP_VIA'], 'wap') ? true : false;
        }
        //判断手机发送的客户端标志,兼容性有待提高
        if (isset($server['HTTP_USER_AGENT'])) {
            $clientkeywords = [
                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic',
                'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb',
                'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
            ];
            //从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($server['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset($server['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($server['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) &&
                (strpos($server['HTTP_ACCEPT'], 'text/html') === false || (strpos($server['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($server['HTTP_ACCEPT'], 'text/html')))
            ) {
                return true;
            }
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
    public function createTicket(string $ticket, string $salt = 'mon-util', int $expire = 3600, string $tokenName = '_token_', string $tokenTimeName = '_tokenTime_'): array
    {
        $now = time();
        $token = md5($salt . $now . $ticket);

        $_COOKIE[$tokenName] = $token;
        $_COOKIE[$tokenTimeName] = $now;
        setcookie($tokenName, $token, ($now + $expire), '/');
        setcookie($tokenTimeName, (string)$now, ($now + $expire), '/');

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
    public function checkTicket(string $ticket, ?string $token = null, ?int $tokenTime = null, string $salt = 'mon-util', bool $destroy = true, int $expire = 3600, string $tokenName = '_token_', string $tokenTimeName = '_tokenTime_'): bool
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
            setcookie($tokenName, '', $now - $expire, '/');
            setcookie($tokenTimeName, '', $now - $expire, '/');
        }

        return $result;
    }

    /**
     * 导出CSV格式文件
     *
     * @param  string  $filename  导出文件名
     * @param  array   $data      导出数据
     * @param  array   $title     表格标题列表，key=>value，value为列标题，key为列名对应data中的索引
     * @param  boolean $output    是否输出
     * @return array
     */
    public function exportCsv(string $filename, array $data, array $title = [], bool $output = true): array
    {
        $str = '';
        if (!empty($title)) {
            // 处理标题
            $values = array_values($title);
            $str = @iconv('utf-8', 'gbk', implode(",", $values)) . "\n";
        }

        // 遍历获取生成的数据
        $keys = array_keys($title);
        foreach ($data as $value) {
            $line = '';
            // 遍历键列表获取对应数据中的键值
            if (!empty($keys)) {
                foreach ($keys as $k) {
                    $line .= $value[$k] . ',';
                }
            } else {
                foreach ($value as $v) {
                    $line .= $v . ',';
                }
            }

            $str .= @iconv('utf-8', 'gbk', trim($line, ',')) . "\n";
        }

        // 响应头信息
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment;filename=' . $filename . '.csv',
            'Cache-Control' => 'must-revalidate,post-check=0,pre-check=0',
            'Expires' => 0,
            'Pragma' => 'public',
            'Content-Length' => strlen($str),
            'Content-Transfer-Encoding' => 'binary'
        ];

        if ($output) {
            // 清空之前的输出
            ob_get_contents() && ob_end_clean();
            // 输出头信息
            foreach ($headers as $k => $v) {
                $header = $k . ':' . $v;
                header($header);
            }
            // 输出文件
            echo $str;
        }

        return ['header' => $headers, 'content' => $str];
    }

    /**
     * 导出Excel，支持图片URL地址导出
     *
     * @param string $filename  文件名
     * @param array $data       表格数据
     * @param array $title      表格头
     * @param boolean $border   是否带边框
     * @param string $sheetName sheet名称
     * @param boolean $output   是否直接输出
     * @return array
     */
    public function exportExcel(string $filename, array $data, array $title = [], bool $border = true, string $sheetName = 'sheet1', bool $output = true): array
    {
        $thead = '';
        $tbody = '';
        // 处理表头
        if (!empty($title)) {
            $ths = [];
            foreach ($title as $th) {
                if (is_array($th) && Common::instance()->isAssoc($th)) {
                    // 关联数组，支持style样式设置
                    $style = isset($th['style']) ? $th['style'] : '';
                    $rowspan = isset($th['rowspan']) ? $th['rowspan'] : '';
                    $colspan = isset($th['colspan']) ? $th['colspan'] : '';
                    $ths[] = '<th style="' . $style . '" rowspan="' . $rowspan . '" colspan="' . $colspan . '">' . $th['text'] . '</th>';
                } elseif (is_string($th) || is_numeric($th)) {
                    $ths[] = '<th>' . $th . '</th>';
                } else {
                    throw new RuntimeException('Excel表头数据参数错误，只支持字符串或数组类型');
                }
            }
            $thead = '<thead><tr>' . implode('', $ths) . '</tr></thead>';
        }

        // 处理表格内容
        $trs = [];
        $td_keys = array_keys($title);
        foreach ($data as $line) {
            if (is_string($line) || is_numeric(($line))) {
                // 非数组，直接添加行
                $trs[] = $line;
            } elseif (is_array($line)) {
                $tds = [];
                if (!empty($td_keys)) {
                    // 指定了key值
                    foreach ($td_keys as $key) {
                        $td = $line[$key];
                        $tds[] = $this->getExcelTD($td);
                    }
                } else {
                    // 未指定标题对应key值，直接按数据源排序
                    foreach ($line as $td) {
                        $tds[] = $this->getExcelTD($td);
                    }
                }

                $trs[] = '<tr>' . implode('', $tds) . '</tr>';
            } else {
                throw new RuntimeException('Excel表格行内容参数错误，只支持字符串或数组类型');
            }
        }

        $tbody = implode('', $trs);
        $showBorder = $border ? 1 : 0;
        $table = '<table border="' . $showBorder . '" cellpadding="10" cellspacing="0">' . $thead . $tbody . '</table>';
        $style = '<style>td{mso-style-parent:style0;mso-number-format:"\@";}</style>';
        $xls = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' . $sheetName . '</x:Name>
                <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->' . $style . '</head>
                <body>' . $table . '</body></html>';

        // 响应头信息
        $headers = [
            'Content-type' => 'application/vnd.ms-excel;',
            'Content-Disposition' => 'attachment;filename=' . $filename . '.xls',
            'Cache-Control' => 'must-revalidate,post-check=0,pre-check=0',
            'Expires' => 0,
            'Pragma' => 'public',
            'Content-Length' => strlen($xls),
            'Content-Transfer-Encoding' => 'binary'
        ];

        if ($output) {
            // 清空之前的输出
            ob_get_contents() && ob_end_clean();
            // 输出头信息
            foreach ($headers as $k => $v) {
                $header = $k . ':' . $v;
                header($header);
            }
            // 输出文件
            echo $xls;
        }

        return ['header' => $headers, 'content' => $xls, 'table' => $table];
    }

    /**
     * 解析Excel表格单元格数据
     *
     * @param mixed $td
     * @return string
     */
    protected function getExcelTD($td): string
    {
        $ret = '';
        if (is_array($td) && Common::instance()->isAssoc($td)) {
            if (!isset($td['text']) && !isset($td['img']) && (isset($td['img']) && !is_array($td['img']))) {
                throw new RuntimeException('Excel表格【单元格】参数错误');
            }
            // 关联数组，支持style样式设置
            $style = isset($td['style']) ? $td['style'] : '';
            $rowspan = isset($td['rowspan']) ? $td['rowspan'] : '';
            $colspan = isset($td['colspan']) ? $td['colspan'] : '';
            if (isset($td['img']) && is_array($td['img'])) {
                $text = '<img width="' . $td['img']['width'] . '" height="' . $td['img']['height'] . '" src="' . $td['img']['url'] . '">';
            } else {
                $text = $td['text'];
            }
            $ret = '<td style="' . $style . '" rowspan="' . $rowspan . '" colspan="' . $colspan . '">' . $text . '</td>';
        } elseif (is_string($td) || is_numeric($td)) {
            $ret = '<td>' . $td . '</td>';
        }

        return $ret;
    }

    /**
     * 导出XML
     *
     * @param  array   $data     输出的数据
     * @param  string  $root     根节点
     * @param  string  $encoding 编码
     * @param  boolean $output   是否输出
     * @return array
     */
    public function exportXML(array $data, string $root = 'mon', string $encoding = 'UTF-8', bool $output = true): array
    {
        $xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml .= "<{$root}>";
        $xml .= Common::instance()->arrToXML($data);
        $xml .= "</{$root}>";

        $headers = ['Content-type' => 'text/xml'];
        if ($output) {
            // 清空之前的输出
            ob_get_contents() && ob_end_clean();
            // 输出头信息
            foreach ($headers as $k => $v) {
                $header = $k . ':' . $v;
                header($header);
            }
            // 输出
            echo $xml;
        }

        return ['header' => $headers, 'content' => $xml];
    }

    /**
     * 隐藏银行卡号
     *
     * @param  string $card 银行卡号
     * @return string
     */
    public function hideBankcard(string $card): string
    {
        if (empty($card)) {
            return '';
        }
        //截取银行卡号前4位
        $prefix = mb_substr($card, 0, 4, 'UTF-8');
        //截取银行卡号后4位
        $suffix = mb_substr($card, -4, 4, 'UTF-8');
        return $prefix . " **** **** **** " . $suffix;
    }

    /**
     * 隐藏手机号
     *
     * @param  string $mobile 手机号
     * @return string
     */
    public function hideMoble(string $mobile): string
    {
        if (empty($mobile)) {
            return '';
        }
        return substr_replace($mobile, '****', 3, 4);
    }

    /**
     * 获取客户端的IP地址
     *
     * @param array $header 头信息，默认 $_SERVER
     * @return string
     */
    public function ip(array $header = []): string
    {
        $header = $header ?: $_SERVER;
        foreach (['X_FORWARDED_FOR', 'HTTP_X_FORWARDED_FOR', 'CLIENT_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (isset($header[$key])) {
                return $header[$key];
            }
        }

        return '';
    }

    /**
     * 安全IP检测，支持IP段检测
     *
     * @param array $ips  白名单IP或者黑名单IP，例如白名单IP：['192.168.1.13', '123.23.23.44', '193.134.*.*']
     * @param string $ip 要检测的IP
     * @return boolean true 在白名单或者黑名单中，否则不在
     */
    public function checkSafeIP(array $ips, string $ip = ''): bool
    {
        $ip = $ip ?: $this->ip();
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
     * 获取本机mac地址
     *
     * @return string
     */
    public function getMacAddress(): string
    {
        $data = [];
        switch (strtolower(PHP_OS)) {
            case 'darwin':
            case 'linux':
                @exec('ifconfig -a', $data);
                break;
            case 'unix':
            case 'aix':
            case 'solaris':
                break;
            default:
                @exec('ipconfig /all', $data);
                if (!$data) {
                    $ipconfig = $_SERVER['WINDIR'] . '\system32\ipconfig.exe';
                    if (is_file($ipconfig)) {
                        @exec($ipconfig . ' /all', $data);
                    } else {
                        @exec($_SERVER['WINDIR'] . '\system\ipconfig.exe /all', $data);
                    }
                }
                break;
        }

        $mac = '';
        $tmp = [];
        foreach ($data as $value) {
            if (preg_match('/[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f]/i', $value, $tmp)) {
                $mac = $tmp[0];
                break;
            }
        }
        unset($tmp);
        return $mac;
    }

    /**
     * 获取两坐标距离
     *
     * @param float $lng1 经度1
     * @param float $lat1 纬度1
     * @param float $lng2 经度2
     * @param float $lat2 纬度2
     * @return float
     */
    public function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        $radLat1 = deg2rad((float)$lat1);
        $radLat2 = deg2rad((float)$lat2);
        $radLng1 = deg2rad((float)$lng1);
        $radLng2 = deg2rad((float)$lng2);
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
    public function getSquarePoint($lng, $lat, $distance = 0.5): array
    {
        if (empty($lng) || empty($lat)) {
            return [];
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
     * @param string    $downloadZip    打包后保存的文件名
     * @param array     $list           打包文件列表
     * @param string    $fileName       下载文件名，默认为打包后的文件名
     * @param boolean   $output         是否输出
     * @throws RuntimeException
     * @return array
     */
    public function exportZip(string $downloadZip, array $list, ?string $fileName = null, bool $output = true): array
    {
        // 初始化Zip并打开
        $zip = new ZipArchive();
        // 初始化
        $bool = $zip->open($downloadZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        // 打开文件
        if ($bool !== true) {
            throw new RuntimeException('PHP-ZipArchive扩展打开文件失败, Code：' . $bool);
        }
        foreach ($list as $key => $val) {
            // 把文件追加到Zip包
            $name = (is_string($key) && !empty($key)) ? $key : basename($val);
            $zip->addFile($val, $name);
        }
        // 关闭Zip对象
        $zip->close();
        // 下载Zip包名
        $fileName = $fileName ?: basename($downloadZip);
        // 响应头
        $headers = [
            'Cache-Control' => 'max-age=0',
            'Content-Description' => 'File Transfer',
            'Content-disposition' => 'attachment; filename=' . $fileName,
            'Content-Type' => 'application/zip',
            'Content-Transfer-Encoding' => 'binary',
            'Content-Length' => filesize($downloadZip)
        ];

        if ($output) {
            // 清空文件的头部信息，解决文件下载无法打开问题
            ob_clean();
            flush();
            // 输出头信息
            foreach ($headers as $k => $v) {
                $header = $k . ':' . $v;
                header($header);
            }
            // 输出文件
            readfile($downloadZip);
            return [];
        }

        return ['header' => $headers, 'content' => file_get_contents($downloadZip)];
    }

    /**
     * 目录打包下载
     *
     * @param string    $downloadZip    打包后保存的文件名
     * @param string    $dirPath        打包的目录
     * @param string    $fileName       下载文件名，默认为打包后的文件名
     * @param boolean   $output         是否输出
     * @throws InvalidArgumentException
     * @return array
     */
    public function exportZipForDir(string $downloadZip, string $dirPath, ?string $fileName = null, bool $output = true): array
    {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException('打包目录不存在!');
        }
        // 初始化Zip并打开
        $zip = new ZipArchive();
        // 初始化
        $bool = $zip->open($downloadZip, ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE);
        if ($bool !== true) {
            throw new RuntimeException('PHP-ZipArchive扩展打开文件失败, Code：' . $bool);
        }
        // 打开目录，压缩文件
        $this->compressZip($zip, opendir($dirPath), $dirPath);
        // 关闭Zip对象
        $zip->close();
        // 下载Zip包
        $fileName = $fileName ? $fileName : basename($downloadZip);
        // 响应头
        $headers = [
            'Cache-Control' => 'max-age=0',
            'Content-Description' => 'File Transfer',
            'Content-disposition' => 'attachment; filename=' . $fileName,
            'Content-Type' => 'application/zip',
            'Content-Transfer-Encoding' => 'binary',
            'Content-Length' => filesize($downloadZip)
        ];

        if ($output) {
            // 清空文件的头部信息，解决文件下载无法打开问题
            ob_clean();
            flush();
            // 输出头信息
            foreach ($headers as $k => $v) {
                $header = $k . ':' . $v;
                header($header);
            }
            // 输出文件
            readfile($downloadZip);
            return [];
        }

        return ['header' => $headers, 'content' => file_get_contents($downloadZip)];
    }

    /**
     * 压缩添加目录文件到zip压缩包中
     *
     * @param ZipArchive $zip zip句柄
     * @param mixed $fileResource 文件列表句柄
     * @param string $sourcePath 资源路径
     * @param string $compressPath 添加zip句柄中的文件路径
     * @return void
     */
    protected function compressZip(ZipArchive $zip, $fileResource, string $sourcePath, string $compressPath = '')
    {
        while (($file = readdir($fileResource)) != false) {
            if ($file == "." || $file == "..") {
                continue;
            }

            $sourceTemp = $sourcePath . '/' . $file;
            $newTemp = $compressPath == '' ? $file : $compressPath . '/' . $file;
            if (is_dir($sourceTemp)) {
                $zip->addEmptyDir($newTemp);
                $this->compressZip($zip, opendir($sourceTemp), $sourceTemp, $newTemp);
            }
            if (is_file($sourceTemp)) {
                $zip->addFile($sourceTemp, $newTemp);
            }
        }
    }

    /**
     * 解压压缩包
     *
     * @param string $zipName 要解压的压缩包
     * @param string $dest 解压到指定目录
     * @return boolean
     */
    public function unZip(string $zipName, string $dest): bool
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
        $zip = new ZipArchive();
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
     * @param boolean $output  是否输出
     * @throws InvalidArgumentException
     * @return array
     */
    public function exportFile(string $filename, string $showname = '', int $expire = 3600, bool $output = true): array
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('[' . $filename . ']下载文件不存在!');
        }
        // 下载文件名
        $showname = $showname ?: File::instance()->getBaseName($filename);
        // 文件大小
        $length = filesize($filename);
        // 文件mimetype
        $mimeType = File::instance()->getMimeType($filename);
        // 文件更新时间
        $mtime = filemtime($filename) ?: time();
        // 响应头信息
        $headers = [
            'Cache-Control: max-age=' . $expire,
            'Expires: ' . gmdate("D, d M Y H:i:s", time() + $expire) . 'GMT',
            'Last-Modified: ' . gmdate("D, d M Y H:i:s", $mtime) . 'GMT',
            'Content-Disposition: attachment; filename=' . $showname,
            'Content-Length: ' . $length,
            'Content-type: ' . $mimeType
        ];

        if ($output) {
            // 清空文件的头部信息，解决文件下载无法打开问题
            ob_clean();
            flush();
            // 输出头信息
            foreach ($headers as $header) {
                header($header);
            }
            // 输出文件
            readfile($filename);
            return [];
        }

        return ['header' => $headers, 'content' => file_get_contents($filename)];
    }

    /**
     * 二维码图片
     *
     * @param string  $text 生成二维码的内容
     * @param integer $level 压缩错误级别
     * @param integer $size 图片尺寸
     * @param integer $margin 图片边距
     * @param boolean $output 是否输出文件
     * @param string  $outfile 保存文件, 空则不保存，字符串路径则表示保存路径
     * @return string
     */
    public function qrcode(string $text, int $level = 0, int  $size = 8, int $margin = 1, bool $output = false, string $outfile = ''): string
    {
        $img = QRcode::png($text, $level, $size, $margin);
        if ($output) {
            ob_clean();
            header("Content-type: image/png");
            echo $img;
        }

        if (!empty($outfile)) {
            File::instance()->createFile($img, $outfile, false);
        }

        return $img;
    }

    /**
     * 下载保存文件
     *
     * @param string $url   下载的文件路径
     * @param string $savePath  保存的文件路径
     * @param string $filename  保存的文件名称
     * @param boolean $createDir    是否自动创建二级目录进行保存
     * @throws RuntimeException|InvalidArgumentException
     * @return string
     */
    public function download(string $url, string $savePath, string $filename = '', bool $createDir = true): string
    {
        $path = $createDir ? ($savePath . '/' . date('Ym') . '/') : ($savePath . '/');
        if (!is_dir($path)) {
            $create = mkdir($path, 0777, true);
            if (!$create) {
                throw new RuntimeException('创建下载文件保存目录失败!');
            }
        } else if (!is_writable($path)) {
            throw new InvalidArgumentException('下载文件保存路径不可写入!');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        // 判断是否为https请求
        $ssl = strtolower(mb_substr($url, 0, 8, 'UTF-8')) == "https://" ? true : false;
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
    public function rgbToHex($rgb): string
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
     * @return array
     */
    public function hexToRgb(string $hex_color): array
    {
        $color = str_replace('#', '', $hex_color);
        if (strlen($color) > 3) {
            $rgb = [
                'r' => hexdec(mb_substr($color, 0, 2, 'UTF-8')),
                'g' => hexdec(mb_substr($color, 2, 2, 'UTF-8')),
                'b' => hexdec(mb_substr($color, 4, 2, 'UTF-8'))
            ];
        } else {
            $color = $hex_color;
            $r = mb_substr($color, 0, 1, 'UTF-8') . mb_substr($color, 0, 1, 'UTF-8');
            $g = mb_substr($color, 1, 1, 'UTF-8') . mb_substr($color, 1, 1, 'UTF-8');
            $b = mb_substr($color, 2, 1, 'UTF-8') . mb_substr($color, 2, 1, 'UTF-8');
            $rgb = [
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            ];
        }
        return $rgb;
    }

    /**
     * base64转图片
     *
     * @param string $base64 图片base64
     * @param string $path 保存路径
     * @return boolean
     */
    public function base64ToImg(string $base64, string $path): bool
    {
        $base64Info = explode(',', $base64);
        $content = base64_decode($base64Info[1]);
        return (bool)File::instance()->createFile($content, $path, false);
    }

    /**
     * 图片转base64
     *
     * @param string $path 图片路径
     * @return string
     */
    public function imgToBase64(string $path): string
    {
        if (!file_exists($path)) {
            throw new RuntimeException('Img file not extsis! path: ' . $path);
        }
        $img = getimagesize($path);
        $content = chunk_split(base64_encode(file_get_contents($path)));
        $base64 = 'data:' . $img['mime'] . ';base64,' . $content;
        return $base64;
    }

    /**
     * require_once 优化版本
     *
     * @param string $file  文件地址
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function requireCache(string $file)
    {
        static $import_files = [];
        if (!isset($import_files[$file])) {
            if (!file_exists($file)) {
                throw new RuntimeException('Require file not extsis! file: ' . $file);
            }

            $import_files[$file] = require_once($file);
        }

        return $import_files[$file];
    }

    /**
     * include_once 优化版本
     *
     * @param string $file  文件地址
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function includeCache(string $file)
    {
        static $import_files = [];
        if (!isset($import_files[$file])) {
            if (!file_exists($file)) {
                throw new RuntimeException('Include file not extsis! file: ' . $file);
            }

            $import_files[$file] = include_once($file);
        }

        return $import_files[$file];
    }
}
