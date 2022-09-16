<?php

use mon\util\Container;

require __DIR__ . '/../vendor/autoload.php';


class T
{
    public function test($args)
    {
        dd($args);
    }
}

// 绑定匿名函数
Container::instance()->set('dump', function ($ages) {
    dd($ages);
});

// 调用
Container::instance()->get('dump', ['asdfgg']);

// 绑定对象
Container::instance()->set('t', T::class);

// 魔术方法调用
Container::instance()->t()->test('aaasd');

// 魔术属性调用
Container::instance()->dump('test');
