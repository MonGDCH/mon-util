<?php

declare(strict_types=1);

use mon\util\Common;
use mon\util\Container;
use mon\util\Instance;
use mon\util\Network;
use mon\util\Tree;

require __DIR__ . '/../vendor/autoload.php';

// $app = Container::instance()->get(Tree::class);
// dd($app);


class A
{
    use Instance;

    public function test()
    {
        dd(123);
    }
}

// $app = new A;
// $app->test();

A::instance()->test();
