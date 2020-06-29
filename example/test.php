<?php

use mon\util\Date;
use mon\util\Tree;
use mon\util\Validate;

require __DIR__ . '/../vendor/autoload.php';

// $a = '440612199456781122';
$a = '440583199306032816';

debug(check('idCard', $a, 100));

$c = Tree::instance()->data([['id' => 1, 'pid' => 0]])->getTree();

debug($c);

debug(Date::instance('2020-05-31')->getDayOfWeek(1)->format());

debug(randString(12));

$ip = '192.168.1.123';
debug(check('ip', $ip));

debug(check('email', '988855@qq.com'));

debug(safe_ip($ip, '127.0.0.1,192.168.*.*'));
