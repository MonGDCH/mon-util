<?php

declare(strict_types=1);

use mon\util\Common;
use mon\util\Network;

require __DIR__ . '/../vendor/autoload.php';

$data = ['a' => 1, 'b' => 2];

$file = __FILE__;

// dd(http_build_query($data));

// dd(Common::instance()->mapToStr($data));

// $url = 'http://gdmon.test/test.php?ac=3';
$url = 'http://localhost:8088/';
// $result = Network::instance()->sendFile('http://gdmon.test/test.php', $file, ['a' => 123], 'test.txt', 'file', [], false);
// $result = Network::instance()->sendHTTP($url, ['a' => 'asdf', 'bb' => 3], 'get', ['Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'], toJson: false);
$result = Network::instance()->sendHTTP($url, ['xml' => ['a' => 'asdf', 'bb' => 3]], 'post', ['Content-Type' => 'application/xml'], toJson: false);

echo $result;

$ret = Common::instance()->xmlToArr($result);
dd($ret);
