<?php

use mon\util\Network;

require __DIR__ . '/../vendor/autoload.php';

$result = Network::sendTCP('127.0.0.1', '8818', 'test');

dd($result);
