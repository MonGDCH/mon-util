<?php

namespace mon\util;

use mon\util\exception\UploadException;

/**
 * 文件上传类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UploadFile
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 允许上传的文件MiMe类型
        'mimes'        => [],
        // 上传的文件大小限制，0不做限制
        'maxSize'      => 0,
        // 允许上传的文件后缀
        'exts'         => [],
        // 保存根路径
        'rootPath'     => '',
    ];

    /**
     * 上传文件的信息
     *
     * @var array
     */
    protected $file = [];

    /**
     * 构造方法
     *
     * @param array $config 自定义配置信息
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取配置信息
     *
     * @param  string $name 配置名称
     * @return mixed 配置值
     */
    public function __get($name)
    {
        return $this->config[$name];
    }

    /**
     * 设置配置信息
     *
     * @param string $name  配置名称
     * @param mixed $value 配置值
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 获取所有配置信息
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 获取上传文件信息
     *
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * 文件上传
     *
     * @param string $files 文件内容
     * @param string $name  内容索引
     * @throws UploadException
     * @return UploadFile
     */
    public function upload($files = null, $name = 'file')
    {
        if (is_null($files)) {
            $files = $_FILES;
        }
        if (empty($files) || !isset($files[$name])) {
            throw new UploadException('未上传文件', UploadException::ERROR_UPLOAD_FAILD);
        }
        // 检测上传保存路径
        if (!$this->checkRootPath()) {
            return false;
        }
        $file = $files[$name];
        // 安全过滤文件名
        $file['name'] = strip_tags($file['name']);
        // 获取上传文件后缀，允许上传无后缀文件
        $file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);
        // 检测文件
        if (!$this->checkFile($file) || !$this->checkImg($file)) {
            return false;
        }
        // 文件md5
        $file['md5']  = md5_file($file['tmp_name']);
        // 文件sha1
        $file['sha1'] = sha1_file($file['tmp_name']);

        $this->file = $file;
        return $this;
    }

    /**
     * 保存上传的文件
     *
     * @param string $fileName  保存文件名
     * @param boolean $replace  是否替换旧文件
     * @throws UploadException
     * @return UploadFile
     */
    public function save($fileName = '', $replace = true)
    {
        if (empty($this->file)) {
            throw new UploadException('未获取上传的文件', UploadException::ERROR_UPLOAD_NOT_FOUND);
        }
        $fileName = empty($fileName) ? uniqid(mt_rand()) . '.' . $this->file['ext'] : $fileName;
        $saveName = $this->config['rootPath'] . $fileName;
        if (!$replace && is_file($saveName)) {
            throw new UploadException('文件已存在', UploadException::ERROR_UPLOAD_EXISTS);
        }
        if (!move_uploaded_file($this->file['tmp_name'], $saveName)) {
            throw new UploadException('文件上传保存错误', UploadException::ERROR_UPLOAD_SAVE_FAILD);
        }
        $this->file['savePath'] = $saveName;
        $this->file['saveName'] = $fileName;

        return $this;
    }

    /**
     * 检测上传根目录
     *
     * @throws UploadException
     * @return boolean
     */
    protected function checkRootPath()
    {
        if (!(is_dir($this->config['rootPath']) && is_writable($this->config['rootPath']))) {
            throw new UploadException('上传目录不存在或不可写入！请尝试手动创建:' . $this->config['rootPath'], UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
        }
        return true;
    }

    /**
     * 检测文件
     *
     * @param string $file  文件路径
     * @throws UploadException
     * @return boolean
     */
    protected function checkFile($file)
    {
        if ($file['error']) {
            throw new UploadException($this->uploadErrorMsg($file['error']), UploadException::ERROR_UPLOAD_CHECK_FAILD);
        }
        // 无效上传
        if (empty($file['name'])) {
            throw new UploadException('未知上传错误', UploadException::ERROR_UPLOAD_NOT_MESSAGE);
        }
        // 检查是否合法上传
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new UploadException('非法上传文件', UploadException::ERROR_UPLOAD_ILLEGAL);
        }
        // 检查文件大小
        if (!$this->checkSize($file['size'])) {
            throw new UploadException('上传文件大小不符', UploadException::ERROR_UPLOAD_SIZE_FAILD);
        }
        // 检查文件Mime类型
        if (!$this->checkMime($file['type'])) {
            throw new UploadException('上传文件MIME类型不允许', UploadException::ERROR_UPLOAD_MINI_FAILD);
        }
        // 检查文件后缀
        if (!$this->checkExt($file['ext'])) {
            throw new UploadException('上传文件后缀不允许', UploadException::ERROR_UPLOAD_EXT_FAILD);
        }

        return true;
    }

    /**
     * 检测图片
     *
     * @param string $file 文件路径
     * @throws UploadException
     * @return void
     */
    protected function checkImg($file)
    {
        $ext = strtolower($file['ext']);
        if (in_array($ext, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])) {
            $imginfo = getimagesize($file['tmp_name']);
            if (empty($imginfo) || ('gif' == $ext && empty($imginfo['bits']))) {
                throw new UploadException('非法图像文件', UploadException::ERROR_UPLOAD_NOT_IMG);
            }
        }

        return true;
    }

    /**
     * 检查文件大小是否合法
     *
     * @param integer $size  文件大小
     * @return boolean
     */
    protected function checkSize($size)
    {
        return !($size > $this->config['maxSize']) || (0 == $this->config['maxSize']);
    }

    /**
     * 检查上传的文件MIME类型是否合法
     *
     * @param string $mime  文件类型
     * @return boolean
     */
    protected function checkMime($mime)
    {
        return empty($this->config['mimes']) ? true : in_array(strtolower($mime), $this->config['mimes']);
    }

    /**
     * 检查上传的文件后缀是否合法
     *
     * @param string $ext   文件后缀
     * @return boolean
     */
    private function checkExt($ext)
    {
        return empty($this->config['exts']) ? true : in_array(strtolower($ext), $this->config['exts']);
    }

    /**
     * 获取错误代码信息
     *
     * @param integer $errorNo  错误号
     * @return string
     */
    protected function uploadErrorMsg($errorNo)
    {
        switch ($errorNo) {
            case 1:
                return '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值！';
            case 2:
                return '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值！';
            case 3:
                return '文件只有部分被上传！';
            case 4:
                return '没有文件被上传！';
            case 6:
                return '找不到临时文件夹！';
            case 7:
                return '文件写入失败！';
            default:
                return '未知上传错误！';
        }
    }
}
