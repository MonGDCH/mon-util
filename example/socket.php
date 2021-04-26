<?php

use mon\util\Tool;

require __DIR__ . '/../vendor/autoload.php';

$data = [];
$result = Tool::instance()->sendCmdTCP('127.0.0.1', '8818', 'test', $data);

debug($data);
// debug($result);