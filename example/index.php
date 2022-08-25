<?php

use mon\util\File;
use mon\util\Instance;

require __DIR__ . '/../vendor/autoload.php';


$source = __DIR__ . '/a';

$desc = __DIR__ . '/b';

File::instance()->copydir($source, $desc, true);