<?php

declare(strict_types=1);

use mon\util\SnowFlake;

require __DIR__ . '/../vendor/autoload.php';

$id = SnowFlake::instance()->createID();

dd($id);