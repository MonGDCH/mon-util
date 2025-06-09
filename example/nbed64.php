<?php

use mon\util\Nbed64;

require __DIR__ . '/../vendor/autoload.php';

$data = [
    'app_id'    => '10321654',
    'key'       => 'sdflkjashgkhiuwaskldfja!@#$4$^&*()_+',
    'name'      => 'saklg',
    // 'value'     => [1, 2, 3,],
    // 'v2'        => [
    //     'aa'    => 'asd',
    //     'bb'    => 'oij'
    // ]
];

$str = json_encode($data, JSON_UNESCAPED_UNICODE);

// $key = '~!@#$+_)(*&^%sjlakwh54124';
$key = 'abcd';

$str = 'abcdefghijklnm1234560789';
// $key = 'abcd';


// $token = Nbed64::stringEncrypt($str, $key);
$token = Nbed64::stringEncryptEx($str, $key);

dd($token);


// $decode = Nbed64::stringDecrypt($token, $key);
$decode = Nbed64::stringDecryptEx($token, $key);
dd($decode);
// dd(json_decode($decode, true));
