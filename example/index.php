<?php

use mon\util\Date;

require __DIR__ . '/../vendor/autoload.php';


$date = new Date();

$sunday = $date->getDayOfWeek(0);

dd($sunday->format());