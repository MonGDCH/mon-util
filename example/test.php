<?php

use mon\util\Date;

require __DIR__ . '/../vendor/autoload.php';

$now = '2023-05-17';

$next = '2022-06-01';

$date = new Date($now);

// $diff = $date->dateDiff($next, 'm');
$diff = $date->monthDiff($next);

dd($diff);
