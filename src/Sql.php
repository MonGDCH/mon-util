<?php

declare(strict_types=1);

namespace mon\util;

/**
 * 解析SQL文件，获取SQL语句
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1   采用File对象读取sql文件
 */
class Sql
{
    /**
     * 解析SQL文件
     *
     * @param string $file  sql文件路径
     * @return array
     */
    public static function parseFile(string $file): array
    {
        $content = File::read($file);
        return static::parseSql($content);
    }

    /**
     * 解析sql内容，分析生成sql语句
     *
     * @param string $content sql内容
     * @return array
     */
    public static function parseSql(string $content): array
    {
        if (empty($content)) {
            return [];
        }
        // 纯sql内容
        $sql = [];
        // 多行注释标记
        $comment = false;
        // 按行分割，兼容多个平台
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = explode("\n", trim($content));
        // 遍历处理每一行
        foreach ($content as $key => $line) {
            // 跳过空行
            if ($line == '') {
                continue;
            }
            // 跳过以#或者--开头的单行注释
            if (preg_match("/^(#|--)/", $line)) {
                continue;
            }
            // 跳过以/**/包裹起来的单行注释
            if (preg_match("/^\/\*(.*?)\*\//", $line)) {
                continue;
            }
            // 多行注释开始
            if (mb_substr($line, 0, 2, 'UTF-8') == '/*') {
                $comment = true;
                continue;
            }
            // 多行注释结束
            if (mb_substr($line, -2, null, 'UTF-8') == '*/') {
                $comment = false;
                continue;
            }
            // 多行注释没有结束，继续跳过
            if ($comment) {
                continue;
            }
            // 记录sql语句
            array_push($sql, $line);
        }

        // 以数组形式返回sql语句
        $sql = implode("\n", $sql);
        $sql = explode(";\n", $sql);
        return $sql;
    }
}
