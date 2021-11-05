<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\util\Image;
use mon\util\exception\ImgException;
use mon\util\Tool;


$img = new Image;

try {
    // 保存图片
    // $save = $img->open('./img/gril.jpg')->save('a.jpg');
    // 裁剪图片
    // $save = $img->open('./img/gril.jpg')->crop(500, 500, 50, 50)->save('b.jpeg');
    // 生成缩略图
    // $save = $img->open('./a.png')->thumb(500, 500, 2)->save('c.jpeg');
    $save = $img->open('./a.png')->thumb(500, 500, 2)->output();
    // 加水印
    // $save = $img->open('./img/gril.jpg')->water('./img/logo.jpg')->save('d.jpeg');
    // 文字水印
    // $save = $img->open('./a.png')->text('CCAV', null, 32)->save('e.jpeg');
    // var_dump($save);
} catch (ImgException $e) {
    var_dump($e->getMessage(), $e->getCode());
}
