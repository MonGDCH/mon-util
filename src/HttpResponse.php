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
    protected string $_buffer = '';

    /**
     * 响应数据
     *
     * @var array
     */
    protected array $_data = [];

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
        // 先给默认值，非HTTP报文（代理返回的垃圾数据、裸TCP响应）不再抛 TypeError
        $this->_data['status'] = 0;
        $this->_data['statusMessage'] = '';
        $this->_data['protocolVersion'] = '1.0';

        $first_line = \strstr($this->_buffer, "\r\n", true);
        if ($first_line === false || \stripos($first_line, 'HTTP/') === false) {
            return;
        }
        $data = \substr((string)\strstr($first_line, 'HTTP/'), 5);
        $parts = \explode(' ', \trim($data), 3);
        if (\count($parts) < 2) {
            return;
        }
        $this->_data['protocolVersion'] = $parts[0] ?: '1.0';
        $this->_data['status'] = (int)$parts[1];
        $this->_data['statusMessage'] = $parts[2] ?? '';
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
        $this->_data['body'] = '';
        // 获取主体内容，不存在分隔符时视为无响应体
        $pos = \strpos($this->_buffer, "\r\n\r\n");
        if ($pos === false) {
            return;
        }
        $this->_data['body'] = \substr($this->_buffer, $pos + 4);
    }
}
