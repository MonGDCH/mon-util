<?php

use mon\util\Date;
use mon\util\File;
use mon\util\Tree;

require __DIR__ . '/../vendor/autoload.php';


debug(str2ascii('12b3a43sf45'));
debug(ascii2str('1323263316433337664353'));
exit;


// RGB转16进制
// debug(rgb2hex('rgb(255,255,255)'));
debug(rgb2hex([255, 0, 255]));
debug(hex2rgb('#ccc'));


// 笛卡尔积生成商品规格
$list = [
    [
        "title" => "颜色",
        "value" => ["黑色", "白色", "蓝色"]
    ],
    [
        "title" => "尺码",
        "value" => ["S", "M", "L", "XL", "XXL"]
    ],
    [
        "title" => "长度",
        "value" => ["5分裤", "7分裤", "9分裤", "长裤"]
    ]
];

$data = specCartesian($list);
debug($data);



$res = check('idCard', '632123820927051');
// $res = check('idCard', '440584199412303286');

debug($res);


$lng = '116.655540';
$lat = '39.910980';
$squares = GetSquarePoint($lng, $lat);


// $save = download('https://gdmon.com/upload/202007/12181222475f092231ea608.jpeg', './test');
// debug($save);


$code = id2code(891255);
debug($code);

$id = code2id($code);
debug($id);
exit;


$c = Tree::instance()->data([['id' => 1, 'pid' => 0]])->getTree();

debug($c);

debug(Date::instance('2020-05-31')->getDayOfWeek(1)->format());

debug(randString(12));

$ip = '192.168.1.123';
debug(check('ip', $ip));

debug(check('email', '988855@qq.com'));

debug(safe_ip($ip, '127.0.0.1,192.168.*.*'));
