<?php

use mon\util\Dictionary;
use mon\util\Migrate;

require __DIR__ . '/../vendor/autoload.php';


$config = [
    // 数据库备份路径
    'path'      => './data/',
    // 数据库备份卷大小
    'part'      => 20971520,
    // 数据库备份文件是否启用压缩 0不压缩 1 压缩
    'compress'  => 1,
    // 压缩级别
    'level'     => 9,
    // 数据库配置
    'db'        => [
        // 数据库类型
        'type'          => 'mysql',
        // 服务器地址
        'host'          => '127.0.0.1',
        // 数据库名
        'database'      => 'gaia',
        // 用户名
        'username'      => 'dev',
        // 密码
        'password'      => 'dev',
        // 端口
        'port'          => '3306',
        // 数据库编码默认采用utf8
        'charset'       => 'utf8mb4',
        // 数据库连接参数
        'params'        => []
    ]
];

// 获取所有表格信息
$tableList = Migrate::instance($config)->tableList();
// 获取指定表格结构
$tableStruct = Migrate::instance()->tableStruct('record');
// 获取生成的备份文件列表
$fileList = Migrate::instance()->fileList();
// 获取指定备份文件信息
$fileInfo = Migrate::instance()->fileInfo();



// 删除备份文件
// $del = Migrate::instance()->remove(time());
// 备份指定表
// $backup = Migrate::instance()->backup('user_log');
// 备份所有数据表
// $migrate = Migrate::instance()->migrate();


// 下载导出，参数为$fileList返回结果集中对应的time字段值
// $export = Migrate::instance()->export(1657692293);

// 导入，参数为$fileList返回结果集中对应的time字段值
// $import = Migrate::instance()->import(1657696160);
// debug($tableList);

// $d = new Dictionary($config['db']);
// echo $d->getHTML();