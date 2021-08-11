<?php

use mon\util\Client;

$url = 'http://103.1.66.36:8752/index.php/admin/index/index.html?group=1';

$url2 = 'http://103.1.66.36:8752/index.php/admin/ccdcysave/basic.html';


require __DIR__ . '/../vendor/autoload.php';

$data = Client::instance()->sendHTTP($url2);

var_dump($data);