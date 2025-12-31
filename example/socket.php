<?php

use mon\util\Network;

require __DIR__ . '/../vendor/autoload.php';

$ip = '127.0.0.1';
$port = '8082';

for ($i = 0; $i < 10; $i++) {
    $result = Network::sendTCP($ip, $port, 'test' . $i . "\n", close: false);
    dd($result);
    echo PHP_EOL;

    if ($i == 9) {
        $result = Network::sendTCP($ip, $port, 'close!' . "\n", close: true);
        dd($result);
        echo PHP_EOL;
    }
}

$result = Network::sendTCP($ip, $port, 'hello!' . "\n");



dd($result);
