<?php

use mon\util\Pipeline;

require __DIR__ . '/../vendor/autoload.php';

// 中间件
$middlewares = [
    function ($data, $next) {
        echo 'a' . PHP_EOL;
        $data[] = 'a';
        return $next($data);
    },
    function ($data, $next) {
        echo 'b' . PHP_EOL;
        $data[] = 'b';
        return $next($data);
    },
    function ($data, $next) {
        $data[] = 'c';
        $result = $next($data);
        echo 'c' . PHP_EOL;
        return $result;
    },
];

// 执行的回调函数
$callable = function ($data) {
    echo 'callback' . PHP_EOL;
    $data[] = 'callback';
    return $data;
};

// 异常错误处理
$exceptionHandler = function ($params, Throwable $e) {
    dd($params);
    echo $e->getMessage() . PHP_EOL;
};

// 初始化传入的参数
$data = [];

// 创建洋葱管道模型
$pipeline = new Pipeline();

// 绑定初始化参数
$pipeline->send($data);
// 绑定中间件
$pipeline->withMiddlewares($middlewares);
// 额外单个添加中间件
$pipeline->then(function ($data, $next) {
    echo 'd' . PHP_EOL;
    $data[] = 'd';
    // throw new Exception(111);
    return $next($data);
});
// 绑定异常错误处理
$pipeline->withExceptionHandler($exceptionHandler);
// 执行回调方法
$result = $pipeline->run($callable);

dd($result);
