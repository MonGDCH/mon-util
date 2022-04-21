<?php

use mon\util\exception\UploadException;
use mon\util\UploadSlice;
use mon\util\Validate;

require __DIR__ . '/../vendor/autoload.php';

class App
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 允许上传的文件后缀
        'exts'      => [],
        // 分片文件大小限制
        'sliceSize' => 1024 * 1024 * 2,
        // 保存根路径
        'rootPath'  => __DIR__ . DIRECTORY_SEPARATOR . 'upload',
        // 临时文件存储路径，基于rootPath
        'tmpPath'   => 'tmp'
    ];

    /**
     * 文件上传
     *
     * @return void
     */
    public function upload()
    {
        if (empty($_POST)) {
            return $this->result(0, 'query faild');
        }
        // 验证数据
        $validate = new Validate();
        $check = $validate->data($_POST)->rule([
            'action'        => ['in:slice,merge'],
            'filename'      => ['required', 'str'],
            'chunk'         => ['int', 'min:0'],
            'chunkLength'   => ['required', 'int', 'min:0'],
            'uuid'          => ['required', 'str']
        ])->message([
            'action'        => 'action faild',
            'filename'      => 'filename faild',
            'chunk'         => 'chunk faild',
            'chunkLength'   => 'chunkLength faild',
            'uuid'          => 'uuid faild'
        ])->check();
        if (!$check) {
            return $this->result(0, $validate->getError());
        }
        if ($_POST['action'] == 'slice' && !isset($_POST['chunk'])) {
            return $this->result(0, 'chunk required');
        }
        if ($_POST['action'] == 'slice' && empty($_FILES)) {
            return $this->result(0, 'upload faild');
        }

        // 处理上传业务
        $action =  $this->post('action');
        $filename =  $this->post('filename');
        $chunk =  $this->post('chunk');
        $chunkLength =  $this->post('chunkLength');
        $uuid =  $this->post('uuid');
        $upload = new UploadSlice($this->config);
        try {
            if ($action == 'slice') {
                // 保存分片
                $save = $upload->upload($uuid, $chunk);
                return $this->result(1, 'ok', $save);
            }
            // 合并
            $merge = $upload->merge($uuid, $chunkLength, $filename);
            return $this->result(1, 'ok', $merge);
        } catch (UploadException $e) {
            return $this->result(0, $e->getMessage());
        }
    }

    /**
     * GET参数
     *
     * @param string $field
     * @param string $default
     * @return mixed
     */
    protected function get($field, $default = '')
    {
        return isset($_GET[$field]) ? $_GET[$field] : $default;
    }

    /**
     * POST参数
     *
     * @param string $field
     * @param string $default
     * @return mixed
     */
    protected function post($field, $default = '')
    {
        return isset($_POST[$field]) ? $_POST[$field] : $default;
    }

    /**
     * 返回json
     *
     * @param integer $code
     * @param string $msg
     * @param array $data
     * @return void
     */
    protected function result($code, $msg, $data = [])
    {
        $result = json_encode([
            'code'  => $code,
            'msg'   => $msg,
            // 'data'  => $this->m_mb_convert_encoding($data)
            'data'  => $data
        ], JSON_UNESCAPED_UNICODE);

        echo $result;
    }

    protected function m_mb_convert_encoding($string)
    {
        if (is_array($string)) {
            foreach ($string as $key => $value) {
                $string[$key] = $this->m_mb_convert_encoding($value);
            }

            return $string;
        }

        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }
}
(new App())->upload();
