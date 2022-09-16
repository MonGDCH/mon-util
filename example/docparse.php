<?php

use mon\util\DocParse;

require __DIR__ . '/../vendor/autoload.php';


$data = DocParse::instance()->parseClass(DemoTest::class);
dd($data);

class DemoTest
{
    /**
     * 测试方法
     *
     * @param string $a 字符串参数
     * @param integer $b 整型参数
     * @param float $c 浮点型
     * @param Test $d 对象类型
     * @param array $e 数组类型
     * @author Mon <985558837@qq.com>
     * @copyright MonLam
     * @deprecated deprecated test
     * @example location description
     * @final lalala
     * @global 全局的xxx
     * @ignore skldgkasgj for ignore
     * @license MIT
     * @link http://url.com
     * @package ppap
     * @abstract ccccc
     * @static staticcccc
     * @var mixed
     * @version 1.0.0
     * @todo Something
     * @throws Exception 的事实
     * @return void 无
     */
    public function test($a, $b, $c, $d, $e)
    {
        return false;
    }

    /**
     * demo方法
     *
     * @return array
     */
    public function demo()
    {
        return [];
    }
}
