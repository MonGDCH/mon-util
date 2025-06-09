<?php

use mon\util\QRcode;
use mon\util\QRcodeEnum;
use mon\util\Tool;

require __DIR__ . '/../vendor/autoload.php';

// qrcode('https://gdmon.com/');
$img = Tool::qrcode('https://gdmon.com/');
// $img = QRcode::text('https://gdmon.com/', false, QRcodeEnum::QR_ECLEVEL_L, 8, 1);
dd($img);
// file_put_contents('./aaa.png', $img);
// QRcode::png('https://gdmon.com/', 'a.png', QR_ECLEVEL_L, 12, 1, true);
