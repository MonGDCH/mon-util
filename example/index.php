<?php

require __DIR__ . '/../vendor/autoload.php';

$date = \mon\util\Date::instance();
var_dump($date->format());

$tool = \mon\util\Tool::instance();
var_dump($tool->hideMoble('13266564371'));


$common = \mon\util\Common::instance();
$encode = $common->encodeEX('abc_123');
$tool->debug($encode);
$tool->debug($common->decodeEx($encode));

$encryption = $common->encryption(json_encode(['ctime' => 1234567890, 'data' => 'Test']), 'DEMO');
$tool->debug($encryption);

$tool->debug($common->decryption($encryption, 'DEMO'));