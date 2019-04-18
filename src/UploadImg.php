<?php
namespace mon\util;

/**
 * base64图片上传类
 *
 * @author Mon <985558837@qq.com>
 * @version v1.0
 */
class UploadImg
{
    /**
     * 文件保存路径
     *
     * @var [type]
     */
    protected $path = '';

    /**
     * 文件保存名称
     *
     * @var string
     */
    protected $name = '';

    /**
     * 错误信息
     *
     * @var [type]
     */
    protected $error;

    /**
     * 获取错误信息
     *
     * @return [type] [description]
     */
    public function getError()
    {
        $error = $this->error;
        $this->error = '';
        return $error;
    }

    /**
     * 设置默认文件保存路径
     *
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 获取默认文件保存路径
     *
     * @return [type] [description]
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 设置默认文件保存名称
     *
     * @param [type] $name [description]
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 获取默认文件保存名称
     *
     * @return [type] [description]
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 保存上传图片
     *
     * @param  [type] $data 图片base64
     * @param  string $path 保存路径
     * @param  string $name 保存名称
     * @return [type]       [description]
     */
    public function upload($data, $path = '', $name = '')
    {
        $img_base64 = explode('base64,', $data);
        if (!$img_base64) {
            $this->error = 'no picture data was obtained';      // 未获取到图片数据
            return false;
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
                $this->error = 'img type failed';       // 图片类型错误
                return false;
        }

        return $this->saveImg($img, $img_suffix, $path, $name);
    }

    /**
     * 保存图片
     *
     * @param  [type] $img    内容
     * @param  [type] $suffix 文件名称后缀
     * @return [type]         [description]
     */
    protected function saveImg($img, $suffix, $path, $name)
    {
        $path = empty($path) ? $this->path : $path;
        $name = empty($name) ? $this->name : $name;

        if(!is_dir($path)){
            if(!mkdir($path, 0755, true)){
                $this->error = 'save image failed, file directory does not exist';      // 保存图片失败，文件目录不存在
                return false;
            }
        }

        $file_name = $this->buildName($name) . '.' . $suffix;
        $path = $path . DIRECTORY_SEPARATOR . $file_name;
        $save = file_put_contents($path, $img);
        if($save === false){
            $this->error = 'save image failed';     // 保存图片失败
            return false;
        }

        return $file_name;
    }

    /**
     * 获取文章保存名称
     *
     * @return [type] [description]
     */
    protected function buildName($name)
    {
        return empty($name) ? uniqid(mt_rand()) : $name;
    }
}