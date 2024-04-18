<?php

declare(strict_types=1);

namespace mon\util;

/**
 * Htttp响应解析
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class HttpResponse
{
    /**
     * http响应内容
     *
     * @var string
     */
    protected $_buffer = '';

    /**
     * 响应数据
     *
     * @var array
     */
    protected $_data = [];

    /**
     * 构造方法
     *
     * @param string $buff  请求体内容
     */
    public function __construct(string $buff)
    {
        $this->_buffer = $buff;
    }

    /**
     * 获取状态码
     *
     * @return integer
     */
    public function status(): int
    {
        if (!isset($this->_data['status'])) {
            $this->parseFristLine();
        }
        return $this->_data['status'];
    }

    /**
     * 获取状态描述
     *
     * @return string
     */
    public function statusMessage(): string
    {
        if (!isset($this->_data['statusMessage'])) {
            $this->parseFristLine();
        }
        return $this->_data['statusMessage'];
    }

    /**
     * 获取HTTP协议版本
     *
     * @return string
     */
    public function protocolVersion(): string
    {
        if (!isset($this->_data['protocolVersion'])) {
            $this->parseFristLine();
        }
        return $this->_data['protocolVersion'];
    }

    /**
     * 获取header数据
     *
     * @param string $name  参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function header(string $name = '', $default = null)
    {
        if (!isset($this->_data['headers'])) {
            $this->parseHeaders();
        }
        if ($name === '') {
            return $this->_data['headers'];
        }
        $name = \strtolower($name);
        return $this->_data['headers'][$name] ?? $default;
    }

    /**
     * 获取body数据
     *
     * @return string
     */
    public function body(): string
    {
        if (!isset($this->_data['body'])) {
            $this->parseBody();
        }
        return $this->_data['body'];
    }

    /**
     * 解析http协议版本
     *
     * @return void
     */
    protected function parseFristLine()
    {
        $first_line = \strstr($this->_buffer, "\r\n", true);
        $data = \substr(\strstr($first_line, 'HTTP/'), 5);
        list($protoco_version, $status_code, $status_message) = \explode(' ', $data, 3);
        $this->_data['status'] = $status_code ?: 0;
        $this->_data['statusMessage'] = $status_message ?: '';
        $this->_data['protocolVersion'] = $protoco_version ?: '1.0';
    }

    /**
     * 解析响应头
     *
     * @return void
     */
    protected function parseHeaders()
    {
        // 提取头部信息
        $headerLines = [];
        $headerStart = \strpos($this->_buffer, "\r\n\r\n") + 4;
        if ($headerStart) {
            $headerLines = \explode("\r\n", \substr($this->_buffer, 0, $headerStart - 4));
        }
        // 解析头部信息为关联数组
        $headers = [];
        foreach ($headerLines as $k => $line) {
            // 过滤第一行
            if ($k < 1) {
                continue;
            }

            if (false !== \strpos($line, ':')) {
                list($key, $value) = \explode(':', $line, 2);
                $key = \strtolower($key);
                $value = \ltrim($value);
            } else {
                $key = \strtolower($line);
                $value = '';
            }
            $headers[$key] = $value;
        }
        $this->_data['headers'] = $headers;
    }

    /**
     * 解析响应内容
     *
     * @return void
     */
    protected function parseBody()
    {
        // 获取主体内容
        $bodyStart = \strpos($this->_buffer, "\r\n\r\n") + 4;
        $this->_data['body'] = \substr($this->_buffer, $bodyStart);
    }
}
