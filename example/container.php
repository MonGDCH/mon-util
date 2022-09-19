<?php

use mon\util\Container;

require __DIR__ . '/../vendor/autoload.php';

class D
{
    protected $t;

    public function __construct(T $t)
    {
        $this->t = $t;
    }

    public function getT()
    {
        return $this->t;
    }

    public function test(T $t, $params)
    {
        return $t->test($params);
    }
}


class T
{
    public function test($args)
    {
        dd($args);
        return __METHOD__;
    }
}


$d = Container::instance()->get(D::class);

$test = Container::instance()->invokeMethd('D@test', ['params' => '123456']);
dd($test);
return;


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
