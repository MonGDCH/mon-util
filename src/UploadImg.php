<?php

declare(strict_types=1);

namespace mon\util;

use mon\util\exception\UploadException;

/**
 * base64图片上传类
 *
 * @author Mon <985558837@qq.com>
 * @version v1.1 增加图片大小限制
 */
class UploadImg
{
    /**
     * 文件保存路径
     *
     * @var string
     */
    protected $path = '';

    /**
     * 文件保存名称
     *
     * @var string
     */
    protected $name = '';

    /**
     * 设置默认文件保存路径
     *
     * @param  string $path 保存的文件路径
     * @return UploadImg
     */
    public function setPath(string $path): UploadImg
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 获取默认文件保存路径
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * 设置默认文件保存名称
     *
     * @param string $name 保存的文件名称
     * @return UploadImg
     */
    public function setName(string $name): UploadImg
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 获取默认文件保存名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 保存上传图片
     *
     * @param string $data      图片base64
     * @param string $path      保存路径
     * @param string $name      保存名称
     * @param integer $maxSize  图片最大尺寸, 0或空则不验证
     * @throws UploadException
     * @return string
     */
    public function upload(string $data, string $path = '', string $name = '', int $maxSize = 0): string
    {
        // 严格解析 data URI: data:{mime};base64,{data}
        if (!preg_match('/^data:(image\/[a-z0-9\-\+\.]+);base64,(.+)$/is', trim($data), $matches)) {
            throw new UploadException('未获取到合法的图片数据', UploadException::ERROR_UPLOAD_FAILD);
        }
        $mime = strtolower($matches[1]);
        $b64  = $matches[2];

        // 严格 base64 解码
        $img = base64_decode($b64, true);
        if ($img === false) {
            throw new UploadException('base64 解码失败', UploadException::ERROR_UPLOAD_FAILD);
        }

        // 后缀判断
        $parts = explode('/', $mime);
        $img_suffix = $parts[1] ?? '';
        if (!in_array($img_suffix, ['jpeg', 'jpg', 'png', 'gif'], true)) {
            throw new UploadException('图片类型错误', UploadException::ERROR_UPLOAD_NOT_IMG);
        }
        // 统一扩展名
        if ($img_suffix === 'jpeg') {
            $img_suffix = 'jpg';
        }

        // 大小按字节判断
        if ($maxSize > 0 && strlen($img) > $maxSize) {
            throw new UploadException('文件大小超出', UploadException::ERROR_UPLOAD_SIZE_FAILD);
        }

        return $this->saveImg($img, $img_suffix, $path, $name);
    }

    /**
     * 保存图片
     *
     * @param string $img    内容
     * @param string $suffix 文件名称后缀
     * @param string $path   保存路径
     * @param string $name   保存文件名
     * @throws UploadException
     * @return string
     */
    protected function saveImg(string $img, string $suffix, string $path, string $name): string
    {
        $path = empty($path) ? $this->path : $path;
        $name = empty($name) ? $this->name : $name;
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        if ($path === '') {
            throw new UploadException('保存目录未设置', UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
        }
        // 检测或创建目录
        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            throw new UploadException('保存图片失败，文件目录无法创建', UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
        }
        if (!is_writable($path)) {
            throw new UploadException('保存目录不可写', UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
        }

        // 安全文件名
        if (!empty($name)) {
            $file_name = basename($name);
            $file_name = preg_replace('/[^\w\.\-]/u', '_', $file_name);
            // 保证带后缀
            if (!preg_match('/\.' . preg_quote($suffix, '/') . '$/i', $file_name)) {
                $file_name .= '.' . $suffix;
            }
        } else {
            $file_name = $this->buildName('') . '.' . $suffix;
        }

        $full = $path . DIRECTORY_SEPARATOR . $file_name;
        $save = File::createFile($img, $full, false);
        if ($save === false) {
            throw new UploadException('保存图片失败', UploadException::ERROR_UPLOAD_SAVE_FAILD);
        }

        return $file_name;
    }

    /**
     * 获取文章保存名称
     *
     * @param string $name  文件名
     * @return string
     */
    protected function buildName(string $name): string
    {
        if (!empty($name)) {
            return $name;
        }
        try {
            return 'img_' . bin2hex(random_bytes(8)) . '_' . uniqid();
        } catch (\Throwable $e) {
            return 'img_' . uniqid();
        }
    }
}
