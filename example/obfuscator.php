<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\util\Obfuscator;

$appPath = __DIR__ . '/mon-http';
$buildPath = __DIR__ . '/build';
$content = file_get_contents(__DIR__ . '/nbed64.php');
$obfuscator = new Obfuscator();
$php = $obfuscator->encode($content);
dd($php);