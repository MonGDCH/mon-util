<?php

use mon\util\Date;
use mon\util\Tool;

require __DIR__ . '/../vendor/autoload.php';

dd(buildURL('http://localhost:8777/?verify_code=SML2#dsf', ['a' => 22]));


// Tool::instance()->exportFile(__DIR__ . '/lottery.php');

