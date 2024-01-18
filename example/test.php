<?php

declare(strict_types=1);

use mon\util\Validate;

require __DIR__ . '/../vendor/autoload.php';


class TestValidate extends Validate
{
    protected $data = ['a' => 2];

    /**
     * 验证规则
     *
     * @var array
     */
    protected $rule = [
        'id'    => ['required', 'int'],
        'idx'   => ['required', 'confirm:id'],
        'name'  => ['required', 'str'],
    ];

    /**
     * 错误提示信息
     *
     * @var array
     */
    protected $message = [
        'id'    => '参数异常1',
        'idx'   => '参数异常',
        'name'  => '请输入名称',
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    protected $scope = [
        'test'  => ['name', 'idx'],
    ];
}

$data = [
    'idx' => 1,
    'id' => '1',
    'name' => 'mon',
];

$sdk = new TestValidate;

$check = $sdk->data($data)->scope('test')->check();
dd($sdk->getError());

dd($sdk);

dd($check);
