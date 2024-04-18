<?php

declare(strict_types=1);

namespace mon\util;

/**
 * Htttp请求体解析，由workerman框框中提取优化
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class HttpRequest
{
    /**
     * 请求数据
     *
     * @var array
     */
    protected $_data = [];

    /**
     * http请求体内容
     *
     * @var string
     */
    protected $_buffer = '';

    /**
     * 最大文件上传数量
     *
     * @var integer
     */
    protected $maxFileUploads = 1024;

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
     * 获取get数据
     *
     * @param string $name  参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $name = '', $default = null)
    {
        if (!isset($this->_data['get'])) {
            $this->parseGet();
        }
        if ($name === '') {
            return $this->_data['get'];
        }
        return $this->_data['get'][$name] ?? $default;
    }

    /**
     * 获取post数据
     *
     * @param string $name  参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function post($name = '', $default = null)
    {
        if (!isset($this->_data['post'])) {
            $this->parsePost();
        }
        if ($name === '') {
            return $this->_data['post'];
        }
        return $this->_data['post'][$name] ?? $default;
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
     * 获取cookie数据
     *
     * @param string $name  参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function cookie(string $name = '', $default = null)
    {
        if (!isset($this->_data['cookie'])) {
            $this->_data['cookie'] = [];
            \parse_str(\preg_replace('/; ?/', '&', $this->header('cookie', '')), $this->_data['cookie']);
        }
        if ($name === '') {
            return $this->_data['cookie'];
        }
        return $this->_data['cookie'][$name] ?? $default;
    }

    /**
     * 获取上传文件
     *
     * @param string $name  参数名
     * @return mixed
     */
    public function file(string $name = '')
    {
        if (!isset($this->_data['files'])) {
            $this->parsePost();
        }
        if ($name === '') {
            return $this->_data['files'];
        }
        return $this->_data['files'][$name] ?? null;
    }

    /**
     * 获取请求类型
     *
     * @return string
     */
    public function method(): string
    {
        if (!isset($this->_data['method'])) {
            $this->parseHeadFirstLine();
        }
        return $this->_data['method'];
    }

    /**
     * 获取HTTP协议版本
     *
     * @return string
     */
    public function protocolVersion(): string
    {
        if (!isset($this->_data['protocolVersion'])) {
            $this->parseProtocolVersion();
        }
        return $this->_data['protocolVersion'];
    }

    /**
     * 获取host
     *
     * @param boolean $without_port 是否不包含端口号
     * @return string
     */
    public function host(bool $without_port = false): string
    {
        $host = $this->header('host');
        if ($host && $without_port) {
            return preg_replace('/:\d{1,5}$/', '', $host);
        }
        return $host;
    }

    /**
     * 获取uri
     *
     * @return string
     */
    public function uri(): string
    {
        if (!isset($this->_data['uri'])) {
            $this->parseHeadFirstLine();
        }
        return $this->_data['uri'];
    }

    /**
     * 获取请求路径
     *
     * @return string
     */
    public function path(): string
    {
        if (!isset($this->_data['path'])) {
            $this->_data['path'] = (string)\parse_url($this->uri(), \PHP_URL_PATH);
        }
        return $this->_data['path'];
    }

    /**
     * 获取uri请求串
     *
     * @return string
     */
    public function queryString(): string
    {
        if (!isset($this->_data['query_string'])) {
            $this->_data['query_string'] = (string)\parse_url($this->uri(), \PHP_URL_QUERY);
        }
        return $this->_data['query_string'];
    }

    /**
     * 获取原始请求头信息
     *
     * @return string
     */
    public function rawHead(): string
    {
        if (!isset($this->_data['head'])) {
            $this->_data['head'] = \strstr($this->_buffer, "\r\n\r\n", true);
        }
        return $this->_data['head'];
    }

    /**
     * 获取原始请求body内容
     *
     * @return string
     */
    public function rawBody(): string
    {
        return \substr($this->_buffer, \strpos($this->_buffer, "\r\n\r\n") + 4);
    }

    /**
     * 获取http请求体内容
     *
     * @return string
     */
    public function rawBuffer(): string
    {
        return $this->_buffer;
    }

    /**
     * 解析获取请求uri及请求类型
     *
     * @return void
     */
    protected function parseHeadFirstLine()
    {
        $first_line = \strstr($this->_buffer, "\r\n", true);
        $tmp = \explode(' ', $first_line, 3);
        $this->_data['method'] = $tmp[0];
        $this->_data['uri'] = $tmp[1] ?? '/';
    }

    /**
     * 解析http协议版本
     *
     * @return void
     */
    protected function parseProtocolVersion()
    {
        $first_line = \strstr($this->_buffer, "\r\n", true);
        $protoco_version = \substr(\strstr($first_line, 'HTTP/'), 5);
        $this->_data['protocolVersion'] = $protoco_version ? $protoco_version : '1.0';
    }

    /**
     * 解析请求头
     *
     * @return void
     */
    protected function parseHeaders()
    {
        $this->_data['headers'] = [];
        $raw_head = $this->rawHead();
        $end_line_position = \strpos($raw_head, "\r\n");
        if ($end_line_position === false) {
            return;
        }
        $head_buffer = \substr($raw_head, $end_line_position + 2);
        $head_data = \explode("\r\n", $head_buffer);
        foreach ($head_data as $content) {
            if (false !== \strpos($content, ':')) {
                list($key, $value) = \explode(':', $content, 2);
                $key = \strtolower($key);
                $value = \ltrim($value);
            } else {
                $key = \strtolower($content);
                $value = '';
            }
            if (isset($this->_data['headers'][$key])) {
                $this->_data['headers'][$key] = "{$this->_data['headers'][$key]},$value";
            } else {
                $this->_data['headers'][$key] = $value;
            }
        }
    }

    /**
     * 解析get数据
     *
     * @return void
     */
    protected function parseGet()
    {
        $query_string = $this->queryString();
        $this->_data['get'] = [];
        if ($query_string === '') {
            return;
        }
        \parse_str($query_string, $this->_data['get']);
    }

    /**
     * 解析post数据
     *
     * @return void
     */
    protected function parsePost()
    {
        $this->_data['post'] = [];
        $this->_data['files'] = [];
        $content_type = $this->header('content-type', '');
        if (\preg_match('/boundary="?(\S+)"?/', $content_type, $match)) {
            $http_post_boundary = '--' . $match[1];
            $this->parseUploadFiles($http_post_boundary);
            return;
        }
        $body_buffer = $this->rawBody();
        if ($body_buffer === '') {
            return;
        }

        if (\preg_match('/\bjson\b/i', $content_type)) {
            $this->_data['post'] = (array) \json_decode($body_buffer, true);
        } else {
            \parse_str($body_buffer, $this->_data['post']);
        }
    }

    /**
     * 解析上传文件列表
     *
     * @param string $http_post_boundary    post数据
     * @return void
     */
    protected function parseUploadFiles(string $http_post_boundary)
    {
        $http_post_boundary = \trim($http_post_boundary, '"');
        $buffer = $this->_buffer;
        $post_encode_string = '';
        $files_encode_string = '';
        $files = [];
        $boday_position = \strpos($buffer, "\r\n\r\n") + 4;
        $offset = $boday_position + \strlen($http_post_boundary) + 2;
        $max_count = $this->maxFileUploads;
        while ($max_count-- > 0 && $offset) {
            $offset = $this->parseUploadFile($http_post_boundary, $offset, $post_encode_string, $files_encode_string, $files);
        }
        if ($post_encode_string) {
            \parse_str($post_encode_string, $this->_data['post']);
        }

        if ($files_encode_string) {
            \parse_str($files_encode_string, $this->_data['files']);
            \array_walk_recursive($this->_data['files'], function (&$value) use ($files) {
                $value = $files[$value];
            });
        }
    }

    /**
     * 解析上传文件
     *
     * @param string $boundary              post数据
     * @param integer $section_start_offset 起始偏移值
     * @param string $post_encode_string    解析后post的内容
     * @param string $files_encode_str      解析后的文件流内容
     * @param array $files                  上传文件列表数组
     * @return integer                      偏移值
     */
    protected function parseUploadFile(string $boundary, int $section_start_offset, string &$post_encode_string, string &$files_encode_str, array &$files): int
    {
        $file = [];
        $boundary = "\r\n$boundary";
        if (\strlen($this->_buffer) < $section_start_offset) {
            return 0;
        }
        $section_end_offset = \strpos($this->_buffer, $boundary, $section_start_offset);
        if (!$section_end_offset) {
            return 0;
        }
        $content_lines_end_offset = \strpos($this->_buffer, "\r\n\r\n", $section_start_offset);
        if (!$content_lines_end_offset || $content_lines_end_offset + 4 > $section_end_offset) {
            return 0;
        }
        $content_lines_str = \substr($this->_buffer, $section_start_offset, $content_lines_end_offset - $section_start_offset);
        $content_lines = \explode("\r\n", trim($content_lines_str . "\r\n"));
        $boundary_value = \substr($this->_buffer, $content_lines_end_offset + 4, $section_end_offset - $content_lines_end_offset - 4);
        $upload_key = false;
        foreach ($content_lines as $content_line) {
            if (!\strpos($content_line, ': ')) {
                return 0;
            }
            list($key, $value) = \explode(': ', $content_line);
            switch (strtolower($key)) {
                case "content-disposition":
                    // 文件流
                    if (\preg_match('/name="(.*?)"; filename="(.*?)"/i', $value, $match)) {
                        $error = 0;
                        $tmp_file = '';
                        $file_name = $match[2];
                        $size = \strlen($boundary_value);
                        $tmp_upload_dir = $this->uploadTmpDir();
                        if (!$tmp_upload_dir) {
                            $error = \UPLOAD_ERR_NO_TMP_DIR;
                        } else if ($boundary_value === '' && $file_name === '') {
                            $error = \UPLOAD_ERR_NO_FILE;
                        } else {
                            $tmp_file = \tempnam($tmp_upload_dir, 'mon.upload.');
                            if ($tmp_file === false || false === \file_put_contents($tmp_file, $boundary_value)) {
                                $error = \UPLOAD_ERR_CANT_WRITE;
                            }
                        }
                        $upload_key = $match[1];
                        // 解析上传文件
                        $file = [
                            'name' => $file_name,
                            'tmp_name' => $tmp_file,
                            'size' => $size,
                            'error' => $error,
                            'type' => '',
                        ];
                        break;
                    } else {
                        // 解析 $_POST
                        if (\preg_match('/name="(.*?)"$/', $value, $match)) {
                            $k = $match[1];
                            $post_encode_string .= \urlencode($k) . "=" . \urlencode($boundary_value) . '&';
                        }
                        return $section_end_offset + \strlen($boundary) + 2;
                    }
                    break;
                case "content-type":
                    $file['type'] = \trim($value);
                    break;
            }
        }
        if ($upload_key === false) {
            return 0;
        }
        $files_encode_str .= \urlencode($upload_key) . '=' . \count($files) . '&';
        $files[] = $file;

        return $section_end_offset + \strlen($boundary) + 2;
    }

    /**
     * 获取上传文件保存目录
     *
     * @return string
     */
    protected function uploadTmpDir(): string
    {
        static $_uploadTmpDir = '';
        if ($_uploadTmpDir === '') {
            if ($upload_tmp_dir = \ini_get('upload_tmp_dir')) {
                $_uploadTmpDir = $upload_tmp_dir;
            } else if ($upload_tmp_dir = \sys_get_temp_dir()) {
                $_uploadTmpDir = $upload_tmp_dir;
            }
        }

        return $_uploadTmpDir;
    }
}
