<?php

declare(strict_types=1);

use mon\util\SnowFlake;
use mon\util\Spids;

require __DIR__ . '/../vendor/autoload.php';

// $id = SnowFlake::instance()->createID();

// dd($id);

$d = Spids::instance();

$c = $d->encode([1, 2, 799]);
dd($c);

$b = $d->decode('x1');
dd($b);
