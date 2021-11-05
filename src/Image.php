<?php

namespace mon\util;

use mon\util\GIF;
use mon\util\exception\ImgException;

/**
 * 图像操作类
 * 
 * @author Name <985558837@qq.com>
 * @version 1.1.0
 */
class Image
{
    /**
     * 图像资源对象
     *
     * @var \GdImage
     */
    private $img;

    /**
     * 图像信息，包括width,height,type,mime,size
     *
     * @var array
     */
    private $info;

    /**
     * gif图片特殊处理实例
     *
     * @var GIF
     */
    protected $gif;

    /**
     * 构造方法
     *
     * @param string $img 图片地址
     */
    public function __construct($img = null)
    {
        if (!extension_loaded('gd')) {
            throw new ImgException('未启用GD扩展');
        }
        if (is_string($img)) {
            $this->open($img);
        }
    }

    /**
     * 打开一张图片
     *
     * @param string $imgname 图片路径
     * @return Image
     */
    public function open($imgname)
    {
        // 检测图像文件
        if (!is_file($imgname)) {
            throw new ImgException('不存在的图像文件', ImgException::ERROR_IMG_NOT_FOUND);
        }
        // 获取图像信息
        $info = getimagesize($imgname);
        // 检测图像合法性
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new ImgException('非法图像文件', ImgException::ERROR_IMG_FAILD);
        }
        // 设置图像信息
        $this->info = [
            'width'  => $info[0],
            'height' => $info[1],
            'type'   => image_type_to_extension($info[2], false),
            'mime'   => $info['mime'],
        ];

        // 销毁已存在的图像
        empty($this->img) || imagedestroy($this->img);

        //打开图像
        if ('gif' == $this->info['type']) {
            $this->gif = new GIF($imgname);
            $this->img = imagecreatefromstring($this->gif->image());
        } else {
            $fun = "imagecreatefrom{$this->info['type']}";
            if (!function_exists($fun)) {
                throw new ImgException('不支持的图片类型', ImgException::ERROR_IMG_TYPE_NOT_SUPPORT);
            }
            $this->img = call_user_func($fun, $imgname);
        }

        return $this;
    }

    /**
     * 保存图像
     *
     * @param  string  $imgname   图像保存名称
     * @param  string  $type      图像类型
     * @param  boolean $interlace 是否对JPEG类型图像设置隔行扫描
     * @return mixed|boolean
     */
    public function save($imgname, $type = null, $interlace = true)
    {
        if (empty($this->img)) {
            throw new ImgException('没有可以被保存的图像资源', ImgException::ERROR_IMG_SAVE);
        }
        // 自动获取图像类型
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }
        // JPEG图像设置隔行扫描
        if ('jpeg' == $type || 'jpg' == $type) {
            $type = 'jpeg';
            imageinterlace($this->img, $interlace);
        }
        // 保存图像
        if ('gif' == $type && !empty($this->gif)) {
            $save = $this->gif->save($imgname);
        } else {
            $fun = "image{$type}";
            if (!function_exists($fun)) {
                throw new ImgException('不支持的图片类型', ImgException::ERROR_IMG_TYPE_NOT_SUPPORT);
            }
            $save = call_user_func($fun, $this->img, $imgname);
        }

        return $save;
    }

    /**
     * 输出图片
     *
     * @param boolean $echo 是否直接输出
     * @return mixed
     */
    public function output($type = null, $interlace = true, $echo = true)
    {
        if (empty($this->img)) {
            throw new ImgException('没有可以被保存的图像资源', ImgException::ERROR_IMG_SAVE);
        }
        // 自动获取图像类型
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }
        // JPEG图像设置隔行扫描
        if ('jpeg' == $type || 'jpg' == $type) {
            $type = 'jpeg';
            imageinterlace($this->img, $interlace);
        }
        $fun = "image{$type}";
        if (!function_exists($fun)) {
            throw new ImgException('不支持的图片类型', ImgException::ERROR_IMG_TYPE_NOT_SUPPORT);
        }
        // 获取输出图像
        ob_start();
        call_user_func($fun, $this->img);
        $content = ob_get_clean();
        // 输出图像
        if ($echo) {
            header("Content-type: image/" . $type);
            echo $content;
        }

        return $content;
    }

    /**
     * 返回图像宽度
     * 
     * @return integer 图像宽度
     */
    public function width()
    {
        if (empty($this->img)) {
            throw new ImgException('没有指定图像资源', ImgException::ERROR_IMG_NOT_SPECIFIED);
        }

        return $this->info['width'];
    }

    /**
     * 返回图像高度
     * 
     * @return integer 图像高度
     */
    public function height()
    {
        if (empty($this->img)) {
            throw new ImgException('没有指定图像资源', ImgException::ERROR_IMG_NOT_SPECIFIED);
        }

        return $this->info['height'];
    }

    /**
     * 返回图像类型
     *
     * @return string 图像类型
     */
    public function type()
    {
        if (empty($this->img)) {
            throw new ImgException('没有指定图像资源', ImgException::ERROR_IMG_NOT_SPECIFIED);
        }

        return $this->info['type'];
    }

    /**
     * 返回图像MIME类型
     *
     * @return string 图像MIME类型
     */
    public function mime()
    {
        if (empty($this->img)) {
            throw new ImgException('没有指定图像资源', ImgException::ERROR_IMG_NOT_SPECIFIED);
        }

        return $this->info['mime'];
    }

    /**
     * 返回图像尺寸数组 0 - 图像宽度，1 - 图像高度
     *
     * @return array 图像尺寸
     */
    public function size()
    {
        if (empty($this->img)) {
            throw new ImgException('没有指定图像资源', ImgException::ERROR_IMG_NOT_SPECIFIED);
        }

        return [
            'width' => $this->info['width'],
            'height' => $this->info['height']
        ];
    }

    /**
     * 裁剪图像
     *
     * @param  integer $w      裁剪区域宽度
     * @param  integer $h      裁剪区域高度
     * @param  integer $x      裁剪区域x坐标
     * @param  integer $y      裁剪区域y坐标
     * @param  integer $width  图像保存宽度
     * @param  integer $height 图像保存高度
     * @return Image
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        if (empty($this->img)) {
            throw new ImgException('没有指定图像资源', ImgException::ERROR_IMG_NOT_SPECIFIED);
        }

        //设置保存尺寸
        empty($width)  && $width  = $w;
        empty($height) && $height = $h;

        do {
            // 创建新图像
            $img = imagecreatetruecolor($width, $height);
            // 调整默认颜色
            $color = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $color);
            // 裁剪
            imagecopyresampled($img, $this->img, 0, 0, $x, $y, $width, $height, $w, $h);
            // 销毁原图
            imagedestroy($this->img);
            // 设置新图像
            $this->img = $img;
        } while (!empty($this->gif) && $this->gifNext());

        $this->info['width']  = $width;
        $this->info['height'] = $height;

        return $this;
    }

    /**
     * 生成缩略图
     *
     * @param  integer $width  缩略图最大宽度
     * @param  integer $height 缩略图最大高度
     * @param  integer $type   缩略图裁剪类型, 1:等比例缩放;2:居中裁剪;3:左上角裁剪;4:右下角裁剪;5:填充;6:固定
     * @return Image
     */
    public function thumb($width, $height, $type = 1)
    {
        if (empty($this->img)) {
            throw new ImgException('没有指定图像资源', ImgException::ERROR_IMG_NOT_SPECIFIED);
        }

        // 原图宽度和高度
        $w = $this->info['width'];
        $h = $this->info['height'];

        // 计算缩略图生成的必要参数
        switch ($type) {
            case 1:
                // 等比例缩放 原图尺寸小于缩略图尺寸则不进行缩略
                if ($w < $width && $h < $height) {
                    return;
                };
                // 计算缩放比例
                $scale = min($width / $w, $height / $h);
                // 设置缩略图的坐标及宽度和高度
                $x = $y = 0;
                $width  = $w * $scale;
                $height = $h * $scale;
                break;

            case 2:
                // 居中裁剪
                $scale = max($width / $w, $height / $h);
                // 设置缩略图的坐标及宽度和高度
                $w = $width / $scale;
                $h = $height / $scale;
                $x = ($this->info['width'] - $w) / 2;
                $y = ($this->info['height'] - $h) / 2;
                break;
            case 3:
                // 左上角裁剪
                $scale = max($width / $w, $height / $h);
                // 设置缩略图的坐标及宽度和高度
                $x = $y = 0;
                $w = $width / $scale;
                $h = $height / $scale;
                break;
            case 4:
                // 右下角裁剪
                $scale = max($width / $w, $height / $h);
                // 设置缩略图的坐标及宽度和高度
                $w = $width / $scale;
                $h = $height / $scale;
                $x = $this->info['width'] - $w;
                $y = $this->info['height'] - $h;
                break;
            case 5:
                // 填充
                if ($w < $width && $h < $height) {
                    $scale = 1;
                } else {
                    $scale = min($width / $w, $height / $h);
                }
                // 设置缩略图的坐标及宽度和高度
                $neww = $w * $scale;
                $newh = $h * $scale;
                $posx = ($width  - $w * $scale) / 2;
                $posy = ($height - $h * $scale) / 2;
                $x = $y = 0;
                do {
                    // 创建新图像
                    $img = imagecreatetruecolor($width, $height);
                    // 调整默认颜色
                    $color = imagecolorallocate($img, 255, 255, 255);
                    imagefill($img, 0, 0, $color);

                    // 裁剪
                    imagecopyresampled($img, $this->img, $posx, $posy, $x, $y, $neww, $newh, $w, $h);
                    imagedestroy($this->img); //销毁原图
                    $this->img = $img;
                } while (!empty($this->gif) && $this->gifNext());

                $this->info['width']  = $width;
                $this->info['height'] = $height;
                return $this;
            case 6:
                // 固定
                $x = $y = 0;
                break;
            default:
                throw new ImgException('不支持的缩略图裁剪类型', ImgException::ERROR_IMG_NOT_SUPPORT);
        }

        // 裁剪图像
        return $this->crop($w, $h, $x, $y, $width, $height);
    }

    /**
     * 添加水印
     *
     * @param  string  $source 水印图片路径
     * @param  integer $locate 水印位置, 1-9对应数字键盘位置
     * @return Image
     */
    public function water($source, $locate = 3)
    {
        if (empty($this->img)) {
            throw new ImgException('没有指定图像资源', ImgException::ERROR_IMG_NOT_SPECIFIED);
        }
        if (!is_file($source)) {
            throw new ImgException('水印图像不存在', ImgException::ERROR_IMG_NOT_FOUND_WATER);
        }

        // 获取水印图像信息
        $info = getimagesize($source);
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new ImgException('非法水印文件', ImgException::ERROR_IMG_FAILD_WATER);
        }
        // 创建水印图像资源
        $fun   = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);
        // 设定水印图像的混色模式
        imagealphablending($water, true);

        // 设定水印位置
        switch ($locate) {
            case 3:
                // 右下角水印
                $x = $this->info['width'] - $info[0];
                $y = $this->info['height'] - $info[1];
                break;

            case 1:
                // 左下角水印
                $x = 0;
                $y = $this->info['height'] - $info[1];
                break;

            case 7:
                // 左上角水印
                $x = $y = 0;
                break;

            case 9:
                // 右上角水印
                $x = $this->info['width'] - $info[0];
                $y = 0;
                break;

            case 5:
                // 居中水印
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            case 2:
                // 下居中水印
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = $this->info['height'] - $info[1];
                break;

            case 6:
                // 右居中水印
                $x = $this->info['width'] - $info[0];
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            case 8:
                // 上居中水印
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = 0;
                break;

            case 4:
                // 左居中水印
                $x = 0;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            default:
                // 自定义水印坐标
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    throw new ImgException('不支持的水印位置类型', ImgException::ERROR_IMG_NOT_SUPPORT_WATER);
                }
        }

        do {
            // 添加水印
            $src = imagecreatetruecolor($info[0], $info[1]);
            // 调整默认颜色
            $color = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $color);
            imagecopy($src, $this->img, 0, 0, $x, $y, $info[0], $info[1]);
            imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
            imagecopymerge($this->img, $src, $x, $y, 0, 0, $info[0], $info[1], 100);

            //销毁零时图片资源
            imagedestroy($src);
        } while (!empty($this->gif) && $this->gifNext());

        // 销毁水印资源
        imagedestroy($water);

        return $this;
    }

    /**
     * 图像添加文字
     *
     * @param  string           $text   添加的文字
     * @param  string           $font   字体路径
     * @param  integer          $size   字号
     * @param  string           $color  文字颜色
     * @param  integer          $locate 文字写入位置, 1-9对应小键盘位置
     * @param  integer|array    $offset 文字相对当前位置的偏移量
     * @param  integer          $angle  文字倾斜角度
     * @return Image
     */
    public function text($text, $font, $size, $color = '#00000000', $locate = 3, $offset = 0, $angle = 0)
    {
        // 资源检测
        if (empty($this->img)) {
            throw new ImgException('没有指定图像资源', ImgException::ERROR_IMG_NOT_SPECIFIED);
        }
        if (!is_file($font)) {
            throw new ImgException("不存在的字体文件：{$font}", ImgException::ERROR_IMG_NOT_FOUND_FONT);
        }
        // 获取文字信息
        $info = imagettfbbox($size, $angle, realpath($font), $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);

        // 计算文字初始坐标和尺寸
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;

        // 设定文字位置
        switch ($locate) {
            case 3:
                // 右下角文字
                $x += $this->info['width']  - $w;
                $y += $this->info['height'] - $h;
                break;

            case 1:
                // 左下角文字
                $y += $this->info['height'] - $h;
                break;

            case 7:
                // 左上角文字，起始坐标即为左上角坐标，无需调整
                break;

            case 9:
                // 右上角文字
                $x += $this->info['width'] - $w;
                break;

            case 5:
                // 居中文字
                $x += ($this->info['width']  - $w) / 2;
                $y += ($this->info['height'] - $h) / 2;
                break;

            case 2:
                // 下居中文字
                $x += ($this->info['width'] - $w) / 2;
                $y += $this->info['height'] - $h;
                break;

            case 6:
                // 右居中文字
                $x += $this->info['width'] - $w;
                $y += ($this->info['height'] - $h) / 2;
                break;

            case 8:
                // 上居中文字
                $x += ($this->info['width'] - $w) / 2;
                break;

            case 4:
                // 左居中文字
                $y += ($this->info['height'] - $h) / 2;
                break;

            default:
                // 自定义文字坐标
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    throw new ImgException('不支持的文字位置类型', ImgException::ERROR_IMG_NOT_SUPPORT_FONT);
                }
        }

        // 设置偏移量
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox = $oy = $offset;
        }

        // 设置颜色
        if (is_string($color) && 0 === mb_strpos($color, '#')) {
            $color = str_split(mb_substr($color, 1), 2);
            $color = array_map('hexdec', (array) $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            throw new ImgException('错误的颜色值', ImgException::ERROR_IMG_FAILD_COLOR);
        }

        do {
            // 写入文字
            $col = imagecolorallocatealpha($this->img, $color[0], $color[1], $color[2], $color[3]);
            imagettftext($this->img, $size, $angle, $x + $ox, $y + $oy, $col, realpath($font), $text);
        } while (!empty($this->gif) && $this->gifNext());

        return $this;
    }

    /**
     * 切换到GIF的下一帧并保存当前帧
     * 
     * @return mixed
     */
    protected function gifNext()
    {
        ob_start();
        ob_implicit_flush(0);
        imagegif($this->img);
        $img = ob_get_clean();
        $this->gif->image($img);
        $next = $this->gif->nextImage();
        if ($next) {
            imagedestroy($this->img);
            $this->img = imagecreatefromstring($next);
            return $next;
        } else {
            imagedestroy($this->img);
            $this->img = imagecreatefromstring($this->gif->image());
            return false;
        }
    }

    /**
     * 析构方法，用于销毁图像资源
     * 
     * @return void
     */
    public function __destruct()
    {
        empty($this->img) || imagedestroy($this->img);
    }
}
