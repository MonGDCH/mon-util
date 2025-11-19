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
        if ($content === '') {
            return [];
        }

        // 统一换行
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);

        $statements = [];
        $buffer = '';
        $delimiter = ';'; // 当前分隔符（支持 DELIMITER 指令切换）

        $inString = false;
        $stringChar = '';
        $inBlockComment = false;

        foreach ($lines as $line) {
            $trimLine = ltrim($line);
            // 检测 DELIMITER 指令（仅在非字符串、非块注释时生效）
            if (!$inString && !$inBlockComment && preg_match('/^DELIMITER\s+(.+)$/i', $trimLine, $m)) {
                $delimiter = $m[1];
                continue;
            }

            // 逐字符解析，保留换行以便识别行注释位置
            $lineWithNL = $line . "\n";
            $len = strlen($lineWithNL);
            for ($i = 0; $i < $len; $i++) {
                $ch = $lineWithNL[$i];

                // 行注释： --   或  #
                if (!$inString && !$inBlockComment) {
                    if ($ch === '#') {
                        // 跳过剩余行
                        break;
                    }
                    if ($ch === '-' && $i + 1 < $len && $lineWithNL[$i + 1] === '-') {
                        // ensure '--' is at line-start or followed by space
                        // 跳过剩余行
                        break;
                    }
                }

                // 块注释开始 /* ...
                if (!$inString && !$inBlockComment && $ch === '/' && $i + 1 < $len && $lineWithNL[$i + 1] === '*') {
                    $inBlockComment = true;
                    $i++; // skip '*'
                    continue;
                }
                // 块注释结束 ... */
                if ($inBlockComment && $ch === '*' && $i + 1 < $len && $lineWithNL[$i + 1] === '/') {
                    $inBlockComment = false;
                    $i++; // skip '/'
                    continue;
                }

                if ($inBlockComment) {
                    continue;
                }

                // 字符串处理，支持 ' " 以及反斜杠转义
                if (!$inString && ($ch === '"' || $ch === "'")) {
                    $inString = true;
                    $stringChar = $ch;
                    $buffer .= $ch;
                    continue;
                } elseif ($inString && $ch === $stringChar) {
                    // 判断是否被转义（简单判断前一字符是否为反斜杠）
                    $prev = $i > 0 ? $lineWithNL[$i - 1] : '';
                    if ($prev !== '\\') {
                        $inString = false;
                        $stringChar = '';
                    }
                    $buffer .= $ch;
                    continue;
                } else {
                    $buffer .= $ch;
                }

                // 当不在字符串、块注释时，检测当前缓冲是否以分隔符结尾
                if (!$inString && !$inBlockComment && $delimiter !== '') {
                    if (substr($buffer, -strlen($delimiter)) === $delimiter) {
                        $stmt = substr($buffer, 0, -strlen($delimiter));
                        $stmt = trim($stmt);
                        if ($stmt !== '') {
                            $statements[] = $stmt;
                        }
                        $buffer = '';
                    }
                }
            }
        }

        // 结尾残留
        $left = trim($buffer);
        if ($left !== '') {
            $statements[] = $left;
        }

        return $statements;
    }
}
