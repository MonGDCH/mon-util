<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\util\Network;


$path = __DIR__ . '/http.php';
$url = 'http://store.qltoys.cn:8383/';


$data = Network::sendHTTP($url,  ['a' => 1, 'b' => 2], toJson: false);

dd($data);
