<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\util\Network;


$path = __DIR__ . '/img.php';
$url = 'http://localhost/index.php';


$data = Network::instance()->sendFile($url, $path, ['a' => 1, 'b' => 2]);

dd($data);
