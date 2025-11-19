<?php

declare(strict_types=1);

namespace mon\util;

use mon\util\exception\NetWorkException;

/**
 * 网络客户端工具
 * 支持HTTP、批量发送HTTP、文件上传、TCP、UDP等请求
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.1.0 优化错误处理，增加模拟表单上传
 */
class Network
{
    /**
     * HTTP以URL的形式发送请求
     *
     * @param   string  $url     请求地址
     * @param   array   $data    传递数据
     * @param   string  $type    请求类型
     * @param   array   $header  请求头，关联数组
     * @param   boolean $toJson  解析json返回数组
     * @param   integer $timeOut 请求超时时间
     * @param   string  $agent   请求user-agent
     * @param   string  $ssl_cer SSL证书
     * @param   string  $ssl_key SSL密钥
     * @throws  NetWorkException
     * @return  mixed 结果集
     */
    public static function sendHTTP(string $url, array $data = [], string $type = 'GET', array $header = [], bool $toJson = true, int $timeOut = 2, string $agent = '', string $ssl_cer = '', string $ssl_key = '')
    {
        $ch = static::getRequest($url, $data, $type, $header, $timeOut, $agent, $ssl_cer, $ssl_key);
        // 发起请求
        $html = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($html === false || $status != 200) {
            $error = curl_error($ch);
            throw new NetWorkException('发起HTTP请求失败！' . $error, 0, null, $ch);
        }
        // 关闭请求句柄
        curl_close($ch);
        $result = ($toJson) ? json_decode($html, true) : $html;
        return $result;
    }

    /**
     * 批量发起HTTP请求
     *
     * @param array $queryList 请求列表
     * @param integer $rolling 默认最大滚动窗口数
     * @param array $header 默认请求头
     * @param integer $timeOut 默认超时时间
     * @param string $agent 默认user-agent
     * @param string $ssl_cer SSL证书
     * @param string $ssl_key SSL密钥
     * @return array 成功结果集与失败结果集
     */
    public static function sendMultiHTTP(array $queryList, int $rolling = 5, array $header = [], int $timeOut = 2, string $agent = '', string $ssl_cer = '', string $ssl_key = ''): array
    {
        $result = [];
        $errors = [];
        $curls = [];
        $master = curl_multi_init();
        // 确保滚动窗口不大于网址数量
        $queryListCount = count($queryList);
        $rolling = ($queryListCount < $rolling) ? $queryListCount : $rolling;
        for ($i = 0; $i < $rolling; $i++) {
            $item = $queryList[$i];
            // 获取curl
            $ch = static::getCh($item, $header, $timeOut, $agent, $ssl_cer, $ssl_key);
            // 写入批量请求
            curl_multi_add_handle($master, $ch);
            // 记录队列
            $key = is_object($ch) ? spl_object_hash($ch) : (string)$ch;
            $curls[$key] = [
                'index' => $i,
                'data'  => $item,
            ];
        }
        // 发起请求
        do {
            while (($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
            if ($execrun != CURLM_OK) {
                break;
            }

            while ($done = curl_multi_info_read($master)) {
                // 获取请求信息
                /** @var CurlHandle $done */
                $info = curl_getinfo($done['handle']);
                // 请求成功
                if ($info['http_code'] == 200) {
                    // 获取返回内容
                    $output = curl_multi_getcontent($done['handle']);
                    // 请求成功，存在回调函数，执行回调函数
                    $key = is_object($done['handle']) ? spl_object_hash($done['handle']) : (string)$done['handle'];
                    if (isset($curls[$key]) && isset($curls[$key]['data']['callback']) && !empty($curls[$key]['data']['callback'])) {
                        $output = Container::instance()->invoke($curls[$key]['data']['callback'], [$output, $curls[$key], $done['handle']]);
                    }
                    $result[] = $output;
                } else {
                    // 请求失败，执行错误处理
                    $key = is_object($done['handle']) ? spl_object_hash($done['handle']) : (string)$done['handle'];
                    $errors[] = [
                        'ch'    => $done['handle'],
                        'item'  => ($curls[$key] ?? null),
                        'http_code' => $info['http_code'],
                        'error' => curl_error($done['handle']),
                    ];
                }

                // 执行下一个句柄
                if ($i < $queryListCount) {
                    $nextItem = $queryList[$i++];
                    $ch = static::getCh($nextItem, $header, $timeOut, $agent, $ssl_cer, $ssl_key);
                    curl_multi_add_handle($master, $ch);
                    $key2 = is_object($ch) ? spl_object_hash($ch) : (string)$ch;
                    $curls[$key2] = [
                        'index' => $i - 1,
                        'data'  => $nextItem,
                    ];
                }
                // 移除并关闭已完成句柄
                curl_multi_remove_handle($master, $done['handle']);
                curl_close($done['handle']);
            }
        } while ($running);

        curl_multi_close($master);
        return ['success' => $result, 'error' => $errors];
    }

    /**
     * 文件上传
     *
     * @param string $url 上传路径
     * @param string $path 文件路径
     * @param array $data 额外的post参数
     * @param string $filename 模拟post表单input的name值
     * @param string $name 文件名
     * @param array $header 额外的请求头
     * @param boolean $toJson 是否解析json返回数据
     * @param integer $timeout 上传超时时间
     * @param string $agent 请求user-agent
     * @param string $ssl_cer 证书路径
     * @param string $ssl_key 证书密钥
     * @return mixed
     */
    public static function sendFile(string $url, string $path, array $data = [], string $filename = '', string $name = 'file', array $header = [], bool $toJson = true, int $timeout = 300, string $agent = '', string $ssl_cer = '', string $ssl_key = '')
    {
        // 处理文件上传数据集
        $filename = empty($filename) ? basename($path) : $filename;
        $sendData = array_merge($data, [$name => new \CURLFile(realpath($path), File::getMimeType($path), $filename)]);
        // $header['Content-Type'] = 'multipart/form-data';
        // 不要手动设置 Content-Type: multipart/form-data（cURL 会自动设置 boundary）
        $lowerHeaders = array_change_key_case($header, CASE_LOWER);
        if (isset($lowerHeaders['content-type']) && stripos($lowerHeaders['content-type'], 'multipart/form-data') !== false) {
            // 删除用户传入的 multipart content-type，避免 boundary 问题
            foreach ($header as $k => $v) {
                if (strtolower($k) === 'content-type') {
                    unset($header[$k]);
                }
            }
        }
        $ch = static::getRequest($url, $sendData, 'POST', $header, $timeout, $agent, $ssl_cer, $ssl_key);
        // 发起请求
        $html = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($html === false || $status != 200) {
            $error = curl_error($ch);
            throw new NetWorkException('发起文件上传请求失败！' . $error, 0, null, $ch);
        }
        // 关闭请求句柄
        curl_close($ch);
        $result = ($toJson) ? json_decode($html, true) : $html;
        return $result;
    }

    /**
     * 发送TCP请求
     *
     * @param string  $ip       IP
     * @param integer $port     端口
     * @param string  $cmd      请求命令套接字
     * @param boolean $toJson   是否转换JSON数组为数组
     * @param integer $timeOut  超时时间，默认2秒
     * @param integer $readLen  最大能够读取的字节数，默认102400
     * @param boolean $close    是否关闭链接
     * @return mixed 结果集
     */
    public static function sendTCP(string $ip, int $port, string $cmd, bool $toJson = true, int $timeOut = 2, int $readLen = 102400, bool $close = true)
    {
        static $sockets = [];
        $key = $ip . ':' . $port;
        $socket = $sockets[$key] ?? socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            throw new NetWorkException('创建TCP-Socket失败');
        }
        $timeouter = ['sec' => $timeOut, 'usec' => 0];
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeouter);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeouter);
        if (socket_connect($socket, $ip, $port) == false) {
            throw new NetWorkException('链接TCP-Socket失败');
        }
        // 缓存已连接 socket，便于复用
        $sockets[$key] = $socket;

        $send_len = mb_strlen($cmd, 'UTF-8');
        $sent = socket_write($socket, $cmd, $send_len);
        if ($sent != $send_len) {
            throw new NetWorkException('发送TCP套接字数据失败');
        }
        // 读取返回数据
        $data = socket_read($socket, $readLen);
        // 是否转换Json格式
        $result = $toJson ? json_decode($data, true) : $data;
        // 是否关闭链接
        if ($close) {
            // 关闭链接，返回结果集
            socket_close($socket);
            unset($sockets[$key]);
        }

        // 返回结果集
        return $result;
    }

    /**
     * 发送UDP请求
     *
     * @param string  $ip       IP
     * @param integer $port     端口
     * @param string  $cmd      请求命令套接字
     * @param boolean $toJson   是否转换JSON数组为数组
     * @param integer $timeOut  超时时间，默认2秒
     * @param integer $readLen  最大能够读取的字节数，默认102400
     * @param boolean $close    是否关闭链接
     * @return mixed 结果集
     */
    public static function sendUDP(string $ip, int $port, string $cmd, bool $toJson = true, int $timeOut = 2, int $readLen = 102400, bool $close = true)
    {
        static $sockets = [];
        $key = $ip . ':' . $port;
        $socket = $sockets[$key] ?? socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            throw new NetWorkException('创建UDP-Socket失败');
        }
        $timeouter = ['sec' => $timeOut, 'usec' => 0];
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeouter);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeouter);
        if (socket_connect($socket, $ip, $port) == false) {
            // 执行链接Socket失败钩子
            throw new NetWorkException('链接UDP-Socket失败');
        }
        $sockets[$key] = $socket;

        $send_len = mb_strlen($cmd, 'UTF-8');
        $sent = socket_write($socket, $cmd, $send_len);
        if ($sent != $send_len) {
            throw new NetWorkException('发送UDP套接字数据失败');
        }
        // 读取返回数据
        $data = socket_read($socket, $readLen);
        // 是否转换Json格式
        $result = $toJson ? json_decode($data, true) : $data;
        // 是否关闭链接
        if ($close) {
            // 关闭链接，返回结果集
            socket_close($socket);
            unset($sockets[$key]);
        }

        // 返回结果集
        return $result;
    }

    /**
     * 生成CURL请求
     *
     * @param  string  $url     请求的URL
     * @param  array   $data    请求的数据
     * @param  string  $type    请求方式
     * @param  array   $header  请求头
     * @param  integer $timeOut 超时时间
     * @param  string  $agent   请求user-agent
     * @param  string  $ssl_cer ssl证书
     * @param  string  $ssl_key ssl秘钥
     * @return \CurlHandle cURL句柄
     */
    public static function getRequest(string $url, array $data = [], string $type = 'GET', array $header = [], int $timeOut = 2, string $agent = '', string $ssl_cer = '', string $ssl_key = ''): \CurlHandle
    {
        $ch = curl_init();
        // 判断请求类型
        switch (strtoupper($type)) {
            case 'GET':
                if (!empty($data)) {
                    $uri = http_build_query($data);
                    $url = $url . (strpos($url, '?') === false ? '?' : '&') . $uri;
                    $data = [];
                }
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case 'PUT':
            case 'DELETE':
            case 'PATCH':
                $header['Content-Type'] = 'application/json';
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
                break;
            default:
                throw new NetWorkException("不支持的HTTP请求类型({$type})");
        }
        // 设置请求URL
        curl_setopt($ch, CURLOPT_URL, $url);
        // 设置ssl证书
        if (!empty($ssl_cer)) {
            if (!file_exists($ssl_cer)) {
                throw new NetWorkException('ssl_cer 文件不存在');
            }
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $ssl_cer);
        }
        if (!empty($ssl_key)) {
            if (!file_exists($ssl_key)) {
                throw new NetWorkException('ssl_key 文件不存在');
            }
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $ssl_key);
        }

        // 
        $isHttps = stripos($url, 'https://') === 0 || (parse_url($url, PHP_URL_SCHEME) === 'https');
        if ($isHttps) {
            // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        // 设置内容以文本形式返回，而不直接返回
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 防止无限重定向
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
        // 设置user-agent
        $defaultUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.99 Safari/537.36';
        $userAgent = $agent ? $agent : $defaultUserAgent;
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        // 设置请求头
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        if (!empty($header)) {
            $headers = [];
            foreach ($header as $k => $v) {
                $headers[] = "{$k}: {$v}";
                if (strtolower($k) == 'content-type') {
                    $v = strtolower($v);
                    if (strpos($v, 'application/x-www-form-urlencoded') !== false) {
                        $data = http_build_query($data);
                    } else if (strpos($v, 'application/json') !== false) {
                        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                    } else if (strpos($v, 'application/xml') !== false) {
                        $data = Common::arrToXML($data);
                    }
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        // 判断是否需要传递数据
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        // 设置超时时间
        if ($timeOut > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        }

        return $ch;
    }

    /**
     * 解析请求列表项，获取curl
     *
     * @param array $item 请求配置信息
     * @param array $header 请求头
     * @param integer $timeOut 超时时间
     * @param string $agent 请求user-agent
     * @param string $ssl_cer ssl证书
     * @param string $ssl_key ssl秘钥
     * @return \CurlHandle cURL句柄
     */
    protected static function getCh(array $item, array $header = [], int $timeOut = 2, string $agent = '', string $ssl_cer = '', string $ssl_key = ''): \CurlHandle
    {
        // 请求URL
        if (!isset($item['url']) || empty($item['url'])) {
            throw new NetWorkException('HTTP请求列表必须存在url参数');
        }
        $url = $item['url'];
        // 请求方式，默认使用get请求
        $method = (isset($item['method']) && !empty($item['method'])) ? strtoupper($item['method']) : 'GET';
        // 请求数据
        $data = [];
        if (isset($item['data']) && !empty($item['data'])) {
            $data = $item['data'];
            if ($method == 'GET') {
                $uri = http_build_query($data);
                $url = $url . (strpos($url, '?') === false ? '?' : '&') . $uri;
                $data = [];
            }
        }
        // 超时时间
        $timeOut = (isset($item['timeout']) && is_numeric($item['timeout'])) ? $item['timeout'] : $timeOut;
        // 请求头
        $header = (isset($item['header']) && !empty($item['header'])) ? $item['header'] : $header;
        // 请求user-agent
        $agent = (isset($item['agent']) && !empty($item['agent'])) ? $item['agent'] : $agent;
        // 获取curl请求
        $ch = static::getRequest($url, $data, $method, $header, $timeOut, $agent, $ssl_cer, $ssl_key);

        return $ch;
    }
}
