<?php

use mon\util\Instance;
use mon\util\Tool;
use mon\util\Validate;

require __DIR__ . '/../vendor/autoload.php';


// $base64 = Tool::instance()->img_base64('111.jpg');
// $img = Tool::instance()->base64_img($base64, '222.jpg');
// debug($base64);
// debug($img);


// $create = Tool::instance()->createTicket('123456', '123456', 3600, 'aaa', 'aaa_time');

// $check = Tool::instance()->checkTicket('123456');
// debug($check);

$check = check('num', '1');
debug($check);

class V extends Validate
{
    use Instance;

    public $rule = [
        'a' => 'required|num',
        'b' => 'required|str',
        'c' => 'confirm:b'
    ];

    public $message = [
        'a' => 'a错误',
        'b' => 'b错误',
        'c' => 'c错误'
    ];
}

$data = [
    'a' => '1',
    'b' => '1123',
    'c' => '1123'
];
$check = V::instance()->data($data)->check();
debug($check);
if (!$check) {
    debug(V::instance()->getError());
}
