<?php

declare(strict_types=1);

use mon\util\Context;
use mon\util\File;
use mon\util\OS;

require __DIR__ . '/../vendor/autoload.php';

// dd(OS::instance()->getCpuInfo());

// dd(File::instance()->formatByte(6545648, 2));

$d = Context::set('aa', 'bb');
$c = Context::get('aa');

$e = Context::get('aac', 'cc1');
dd($c);
dd($e);
