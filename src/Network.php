<?php

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
    use Instance;

    /**
     * 默认的user-agent
     *
     * @var string
     */
    protected $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.99 Safari/537.36';

    /**
     * HTTP以URL的形式发送请求
     *
     * @param   string  $url     请求地址
     * @param   array   $data    传递数据
     * @param   string  $type    请求类型
     * @param   array   $header  请求头
     * @param   boolean $toJson  解析json返回数组
     * @param   integer $timeOut 请求超时时间
     * @param   string  $agent   请求user-agent
     * @return  mixed 结果集
     */
    public function sendHTTP($url, $data = [], $type = 'GET', array $header = [], $toJson = false, $timeOut = 2, $agent = '')
    {
        $method = strtoupper($type);
        $queryData = $data;
        // get请求
        if ($method == 'GET' && is_array($data) && count($data) > 0) {
            $uri = Common::instance()->mapToStr($data);
            $url = $url . (strpos($url, '?') === false ? '?' : '') . $uri;
            $queryData = [];
        }

        $ch = $this->getRequest($url, $queryData, $method, $timeOut, $header, $agent);
        // 发起请求
        $html = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($html === false || $status != 200) {
            throw new NetWorkException('发起HTTP请求失败!', 0, null, $ch);
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
     * @return array 成功结果集与失败结果集
     */
    public function sendMultiHTTP($queryList, $rolling = 5, $header = [], $timeOut = 2, $agent = '')
    {
        $result = [];
        $errors = [];
        $curls = [];
        $master = curl_multi_init();
        // 确保滚动窗口不大于网址数量
        $rolling = (count($queryList) < $rolling) ? count($queryList) : $rolling;
        for ($i = 0; $i < $rolling; $i++) {
            $item = $queryList[$i];
            // 获取curl
            $ch = $this->getCh($item, $timeOut, $header, $agent);
            // 写入批量请求
            curl_multi_add_handle($master, $ch);
            // 记录队列
            $key = (string)$ch;
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
                $info = curl_getinfo($done['handle']);
                // 请求成功
                if ($info['http_code'] == 200) {
                    // 获取返回内容
                    $output = curl_multi_getcontent($done['handle']);
                    // 请求成功，存在回调函数，执行回调函数
                    $key = (string) $done['handle'];
                    if (isset($curls[$key]) && isset($curls[$key]['data']['callback']) && !empty($curls[$key]['data']['callback'])) {
                        $output = Container::instance()->invoke($curls[$key]['data']['callback'], [$output, $curls[$key], $done['handle']]);
                    }
                    $result[] = $output;
                } else {
                    // 请求失败，执行错误处理
                    $key = (string) $done['handle'];
                    $errors[] = [
                        'ch'    => $done['handle'],
                        'item'  => $curls[$key]
                    ];
                }

                // 发起新请求（在删除旧请求之前，请务必先执行此操作）, 当$i等于$urls数组大小时不用再增加了
                if ($i < count($queryList)) {
                    $ch = $this->getCh($queryList[$i++]);
                    curl_multi_add_handle($master, $ch);
                    // 记录队列
                    $key = (string)$ch;
                    $curls[$key] = [
                        'index' => $i,
                        'data'  => $item,
                    ];
                }
                // 执行下一个句柄
                curl_multi_remove_handle($master, $done['handle']);
            }
        } while ($running);

        return [
            'success'   => $result,
            'error'     => $errors
        ];
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
     * @param string $agent 请求user-agent
     * @param integer $timeout 上传超时时间
     * @return mixed
     */
    public function sendFile($url, $path, $data = [], $filename = '', $name = 'file', $header = [], $toJson = false, $agent = '', $timeout = 300)
    {
        // 处理文件上传数据集
        $filename = empty($filename) ? basename($path) : $filename;
        $sendData = array_merge($data, [$name => new \CURLFile(realpath($path), File::instance()->getMimeType($path), $filename)]);
        $header = array_merge(['Content-Type: multipart/form-data'], $header);
        $ch = $this->getRequest($url, $sendData, 'post', $timeout, $header, $agent);
        // 发起请求
        $html = curl_exec($ch);
        if ($html === false) {
            throw new NetWorkException('发起文件上传请求失败!', 0, null, $ch);
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
     * @param integer $timeOut  超时时间
     * @param boolean $toJson   是否转换JSON数组为数组
     * @param integer $readLen  最大能够读取的字节数，默认102400
     * @param boolean $close    是否关闭链接
     * @return mixed 结果集
     */
    public function sendTCP($ip, $port, $cmd, $timeOut = 2, $toJson = false, $readLen = 102400, $close = true)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            throw new NetWorkException('创建TCP-Socket失败');
        }
        $timeouter = ['sec' => $timeOut, 'usec' => 0];
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeouter);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeouter);
        if (socket_connect($socket, $ip, $port) == false) {
            throw new NetWorkException('链接TCP-Socket失败');
        }
        $send_len = strlen($cmd);
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
            return $result;
        }

        // 不关闭链接，返回链接对象及结果集
        return ['socket' => $socket, 'result' => $result];
    }

    /**
     * 发送UDP请求
     *
     * @param string  $ip       IP
     * @param integer $port     端口
     * @param string  $cmd      请求命令套接字
     * @param integer $timeOut  超时时间
     * @param boolean $toJson   是否转换JSON数组为数组
     * @param integer $readLen  最大能够读取的字节数，默认102400
     * @param boolean $close    是否关闭链接
     * @return mixed 结果集
     */
    public function sendUDP($ip, $port, $cmd, $timeOut = 2, $toJson = false, $readLen = 102400, $close = true)
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
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
        $send_len = strlen($cmd);
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
            return $result;
        }

        // 不关闭链接，返回链接对象及结果集
        return ['socket' => $socket, 'result' => $result];
    }

    /**
     * 生成CURL请求
     *
     * @param  string  $url     请求的URL
     * @param  array   $data    请求的数据
     * @param  string  $type    请求方式
     * @param  integer $timeOut 超时时间
     * @param  array   $header  请求头
     * @param  string  $agent   请求user-agent
     * @return mixed cURL句柄
     */
    public function getRequest($url, $data = [], $type = 'GET', $timeOut = 2, array $header = [], $agent = '')
    {
        // 判断是否为https请求
        $ssl = strtolower(substr($url, 0, 8)) == 'https://' ? true : false;
        $ch = curl_init();
        // 设置请求URL
        curl_setopt($ch, CURLOPT_URL, $url);
        // 判断请求类型
        switch (strtoupper($type)) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case 'PUT':
            case 'DELETE':
            case 'PATCH':
                $data = empty($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
                $header[] = 'Content-Type:application/json';
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
                break;
            default:
                throw new NetWorkException("不支持的HTTP请求类型({$type})");
        }
        // 判断是否需要传递数据
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        // 设置超时时间
        if ($timeOut > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        }
        // 设置内容以文本形式返回，而不直接返回
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 防止无限重定向
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
        // 设置user-agent
        $userAgent = $agent ? $agent : $this->userAgent;
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        // 设置请求头
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        if (!empty($header)) {
            // 优化关联数组请求头处理
            $headers = $header;
            if (Common::instance()->isAssoc($header)) {
                $headers = [];
                foreach ($header as $k => $v) {
                    $headers[] = "{$k}: {$v}";
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($ssl) {
            // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        return $ch;
    }

    /**
     * 解析请求列表项，获取curl
     *
     * @param array $item 请求配置信息
     * @param integer $timeOut 超时时间
     * @param array $header 请求头
     * @param string $agent 请求user-agent
     * @return mixed cURL句柄
     */
    protected function getCh($item, $timeOut = 2, $header = [], $agent = '')
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
                $uri = Common::instance()->mapToStr($data);
                $url = $url . (strpos($url, '?') === false ? '?' : '') . $uri;
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
        $ch = $this->getRequest($url, $data, $method, $timeOut, $header, $agent);

        return $ch;
    }
}
