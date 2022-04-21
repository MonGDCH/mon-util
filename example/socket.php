<?php

use mon\util\Network;

require __DIR__ . '/../vendor/autoload.php';

$result = Network::instance()->sendTCP('127.0.0.1', '8818', 'test');

debug($result);
// debug($result);