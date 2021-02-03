<?php

use mon\util\IPLocation;

require __DIR__ . '/../vendor/autoload.php';


$qqwry = 'C:\Users\Administrator\Desktop\address\qqwry.dat';

$class = new IPLocation($qqwry);

$ip = '113.116.68.80';
// $location = $class->getLocation($ip);

$location = IPLocation::instance()->init($qqwry)->getLocation($ip);
$location = IPLocation::instance()->init($qqwry)->getLocation($ip);

debug($location);