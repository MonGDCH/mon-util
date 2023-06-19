<?php

use mon\util\Lottery;

require __DIR__ . '/../vendor/autoload.php';


// 抽奖奖品
$awards = [
    ['id' => 1, 'title' => '平板电脑', 'probability' => 0.5],
    ['id' => 2, 'title' => '数码相机', 'probability' => 0.15],
    ['id' => 3, 'title' => '音箱设备', 'probability' => 0.25],
    ['id' => 4, 'title' => '4G优盘', 'probability' => 24.5],
    ['id' => 5, 'title' => '10Q币', 'probability' => 3.5],
];

$lottery = new Lottery($awards);
// 初始化抽奖配置，抽奖
$gift = $lottery->getDraw();
dd($gift);
