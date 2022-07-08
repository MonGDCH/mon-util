<?php

use mon\util\QRcode;
use mon\util\Tool;

require __DIR__ . '/../vendor/autoload.php';

// qrcode('https://gdmon.com/');
Tool::instance()->qrcode('https://gdmon.com/');
// QRcode::png('https://gdmon.com/', false, QR_ECLEVEL_L, 8, 1);
// QRcode::png('https://gdmon.com/', 'a.png', QR_ECLEVEL_L, 12, 1, true);
