<?php

declare(strict_types=1);

use mon\util\Common;
use mon\util\Container;
use mon\util\exception\ValidateException;
use mon\util\Instance;
use mon\util\Network;
use mon\util\Tree;
use mon\util\Validate;

require __DIR__ . '/../vendor/autoload.php';

// $app = Container::instance()->get(Tree::class);
// dd($app);


class T extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'id'        => ['required', 'id'],
        'ids'       => ['required', 'arrayCheck:required,id'],
        'cc'        => ['isset', 'str']
    ];

    /**
     * 错误提示信息
     *
     * @var array
     */
    public $message = [
        'id'        => [
            'required' => '参数错误',
            'id'       => '参数错误!'
        ],
        'ids'       => '参数异常',
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 新增
        'add' => ['id'],
        // 编辑
        'edit' => ['id', 'ids', 'cc'],
    ];
}

dd(Common::uuid());
dd(Common::guid());

$app = new T;
$data = ['id' => '11', 'ids' => ['1', '2'], 'cc' => [], 'ca' => 'ss'];


try {
    $check = $app->scope('edit')->checked($data);
    dd($check);
} catch (ValidateException $e) {
    dd($e->getMessage());
    dd($e->getKey());
    dd($e->getRule());
    dd($e->getData());
}
