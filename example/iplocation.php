<?php

use mon\util\IPLocation;

require __DIR__ . '/../vendor/autoload.php';


$qqwry = __DIR__ . '/qqwry.dat';

$class = new IPLocation($qqwry);

$ip = '255.255.255.1';
// $location = $class->getLocation($ip);

$location = IPLocation::instance()->init($qqwry)->getLocation($ip);

dd($location);
