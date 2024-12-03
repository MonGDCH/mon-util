<?php

declare(strict_types=1);

use mon\util\Date;
use mon\util\IdCard;
use mon\util\SnowFlake;
use mon\util\Spids;

require __DIR__ . '/../vendor/autoload.php';

$data = ['a' => 1, 'b' => 2];

collection($data)->each(function ($item) {
    // do something
    // dd($item);
});

// dd(collection($data)->count());

// dd((new Date())->magicInfo('xz'));

dd(Spids::instance()->encode([1, 123, 445]));
dd(Spids::instance()->decode('vs1234uxxJDh2Qr'));

