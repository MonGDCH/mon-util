<?php

use mon\util\Pinyin;

require __DIR__ . '/../vendor/autoload.php';


echo Pinyin::instance()->format('我是拼 音，一起 拼音！');

