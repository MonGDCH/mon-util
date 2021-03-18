<?php

use mon\util\Lottery;

require __DIR__ . '/../vendor/autoload.php';


// 抽奖奖品
$awards = array(
    '0' => array('id' => 1, 'title' => '平板电脑', 'probability' => 0.5),
    '1' => array('id' => 2, 'title' => '数码相机', 'probability' => 0.15),
    '2' => array('id' => 3, 'title' => '音箱设备', 'probability' => 0.25),
    '3' => array('id' => 4, 'title' => '4G优盘', 'probability' => 24.5),
    '4' => array('id' => 5, 'title' => '10Q币', 'probability' => 3.5),
);

$lottery = new Lottery();
// 初始化抽奖配置，抽奖
$gift = $lottery->init($awards)->getDraw();
debug($gift);
