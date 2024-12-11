<?php

declare(strict_types=1);

use mon\util\File;
use mon\util\OS;

require __DIR__ . '/../vendor/autoload.php';

dd(OS::instance()->getCpuInfo());

// dd(File::instance()->formatByte(6545648, 2));
