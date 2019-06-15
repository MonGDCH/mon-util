<?php

require __DIR__ . '/../vendor/autoload.php';

$validate = new \mon\util\Validate;

$rule = [
    'a'     => 'required',
    'b'     => 'required|int',
    'c'     => 'num',
];

$data = [
    'a' => '1', 'b' => '3', 'c' => '1.1',
];

$check = $validate->rule($rule)->data($data)->scope(['a', 'b', 'c'])->check();
var_dump($check);
exit;

class V extends \mon\util\Validate
{
    public $rule = [
        'a'     => 'required',
        'b'     => 'in:1,2,3',
        'd'     => 'required'
    ];

    public $message = [
        'a'     => 'a faild',
        'b'     => 'b faild'
    ];

    public $scope = [
        'test'  => ['a', 'b']
    ];
}

$check = (new V)->data($data)->check();
var_dump($check);
exit;


$date = \mon\util\Date::instance();
var_dump($date->format());

$tool = \mon\util\Tool::instance();
var_dump($tool->hideMoble('13266564371'));


$common = \mon\util\Common::instance();
$encode = $common->encodeEX('abc_123');
$tool->debug($encode);
$tool->debug($common->decodeEx($encode));

$encryption = $common->encryption(json_encode(['ctime' => 1234567890, 'data' => 'Test']), 'DEMO');
$tool->debug($encryption);

$tool->debug($common->decryption($encryption, 'DEMO'));