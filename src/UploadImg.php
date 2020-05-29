<?php

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
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 获取默认文件保存路径
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 设置默认文件保存名称
     *
     * @param string $name 保存的文件名称
     * @return UploadImg
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 获取默认文件保存名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 保存上传图片
     *
     * @param string $data      图片base64
     * @param string $path      保存路径
     * @param string $name      保存名称
     * @param string $maxSize   图片最大尺寸, 0或空则不验证
     * @throws UploadException
     * @return string
     */
    public function upload($data, $path = '', $name = '', $maxSize = '2048000')
    {
        $img_base64 = explode('base64,', $data);
        if (!$img_base64) {
            throw new UploadException('未获取到图片数据', 1);
        }

        $img = base64_decode(trim($img_base64[1]));
        $img_type = explode('/', trim($img_base64[0], ';'));
        switch (strtolower($img_type[1])) {
            case 'jpeg':
            case 'jpg':
                $img_suffix = "jpg";
                break;
            case 'png':
                $img_suffix = "png";
                break;
            case 'gif':
                $img_suffix = "gif";
                break;
            default:
                throw new UploadException('图片类型错误', 2);
        }

        if (!empty($maxSize) && strlen($data) > $maxSize) {
            throw new UploadException('文件大小超出', 5);
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
    protected function saveImg($img, $suffix, $path, $name)
    {
        $path = empty($path) ? $this->path : $path;
        $name = empty($name) ? $this->name : $name;
        // 检测目录是否存在, 不存在则创建
        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            throw new UploadException('保存图片失败，文件目录不存在', 3);
        }

        $file_name = $this->buildName($name) . '.' . $suffix;
        $path = $path . DIRECTORY_SEPARATOR . $file_name;
        $save = file_put_contents($path, $img);
        if ($save === false) {
            throw new UploadException('保存图片失败', 4);
        }

        return $file_name;
    }

    /**
     * 获取文章保存名称
     *
     * @param string $name  文件名
     * @return string
     */
    protected function buildName($name)
    {
        return empty($name) ? uniqid(mt_rand()) : $name;
    }
}
