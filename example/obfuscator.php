<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\util\Obfuscator;

$appPath = __DIR__ . '/mon-http';
$buildPath = __DIR__ . '/build';
$obfuscator = new Obfuscator();
$obfuscator->encodeApp($appPath, $buildPath, ['.git', 'doc', 'vendor'], ['README.md', 'LICENSE']);
