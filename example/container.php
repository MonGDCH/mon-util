<?php

use mon\util\Container;

require __DIR__ . '/../vendor/autoload.php';


class T
{
    public function test($args)
    {
        debug($args);
    }
}

// 绑定匿名函数
Container::instance()->bind('dump', function ($ages) {
    debug($ages);
});

// 调用
Container::instance()->get('dump', ['asdfgg']);


// 绑定对象
Container::set('t', T::class);

// 调用
Container::instance()->t->test('aaasd');
