<?php

declare(strict_types=1);

use mon\util\Common;
use mon\util\Container;
use mon\util\Network;
use mon\util\Tree;

require __DIR__ . '/../vendor/autoload.php';

$app = Container::instance()->get(Tree::class);
dd($app);

