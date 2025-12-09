<?php

declare(strict_types=1);

namespace mon\util;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use mon\util\exception\MinifierException;

/**
 * HTML、JS、CSS、VUE、PHP文件代码压缩工具
 * 
 * @author monLam <985558837@qq.com>
 * @version 1.0.0
 */
class Minifier
{
    /**
     * 移除HTML文件中的注释和多余换行，并可选压缩内容
     *
     * @param string $html
     * @param bool $compress 是否压缩HTML内容
     * @return string
     */
    public function html(string $html, bool $compress = false): string
    {
        // 移除HTML注释，但保留条件注释
        $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);

        // 提取并处理style标签内容
        preg_match_all('/(<style[^>]*>)(.*?)(<\/style>)/s', $html, $styles);
        foreach ($styles[0] as $key => $style) {
            $openingTag = $styles[1][$key];
            $content = $styles[2][$key];
            $closingTag = $styles[3][$key];
            $cleanedContent = $this->css($content, $compress);
            $cleanedStyle = $openingTag . $cleanedContent . $closingTag;
            $html = str_replace($style, $cleanedStyle, $html);
        }

        // 提取并处理script标签内容
        preg_match_all('/(<script[^>]*>)(.*?)(<\/script>)/s', $html, $scripts);
        foreach ($scripts[0] as $key => $script) {
            $openingTag = $scripts[1][$key];
            $content = $scripts[2][$key];
            $closingTag = $scripts[3][$key];
            $cleanedContent = $this->js($content);
            $cleanedScript = $openingTag . $cleanedContent . $closingTag;
            $html = str_replace($script, $cleanedScript, $html);
        }

        // 移除多余换行，连续换行替换为单个换行
        $html = preg_replace('/\n\s*\n+/', "\n", $html);

        // 压缩HTML内容（如果开启）
        if ($compress) {
            // 移除标签之间的多余空白字符
            $html = preg_replace('/>\s+</', '><', $html);
            // 移除行首和行尾的空白字符
            $html = trim($html);
        }

        return $html;
    }

    /**
     * 移除CSS文件中的注释和多余换行，并可选压缩内容
     *
     * @param string $css
     * @param bool $compress 是否压缩CSS内容
     * @return string
     */
    public function css(string $css, bool $compress = false): string
    {
        // 移除CSS注释
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);

        // 移除多余换行，连续换行替换为单个换行
        $css = preg_replace('/\n\s*\n+/', "\n", $css);

        // 压缩CSS内容（如果开启）
        if ($compress) {
            // 移除换行符和制表符
            $css = str_replace(array("\n", "\r", "\t"), '', $css);
            // 移除多余空格（保留一个空格）
            $css = preg_replace('/\s+/', ' ', $css);
            // 移除CSS属性前后的空格
            $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
            // 移除行首和行尾的空白字符
            $css = trim($css);
        }

        return $css;
    }

    /**
     * 移除JS文件中的注释和多余换行
     *
     * @todo js暂时不支持进行压缩
     * @param string $js
     * @return string
     */
    public function js(string $js): string
    {
        // 先处理模板字符串，避免误删模板字符串内的注释
        $templateStrings = [];
        $js = preg_replace_callback('/`[^`]*`/s', function ($matches) use (&$templateStrings) {
            $key = '__TEMPLATE_STRING_' . count($templateStrings) . '__';
            $templateStrings[$key] = $matches[0];
            return $key;
        }, $js);

        // 移除JS多行注释
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);

        // 移除JS单行注释，但保留URL中的//
        // 使用更严格的正则表达式，确保匹配所有单行注释
        $js = preg_replace('/\s*\/\/[\s\S]*?$/m', '', $js);

        // 恢复模板字符串
        $js = str_replace(array_keys($templateStrings), array_values($templateStrings), $js);

        // 移除多余换行，连续换行替换为单个换行
        $js = preg_replace('/\n\s*\n+/', "\n", $js);

        // 移除行首和行尾的多余空格
        $lines = explode("\n", $js);
        $processedLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                $processedLines[] = $trimmed;
            }
        }
        $js = implode("\n", $processedLines);

        return $js;
    }

    /**
     * 移除Vue文件中的注释和多余换行，并可选压缩内容
     *
     * @param string $vue
     * @param bool $compress 是否压缩HTML和CSS内容
     * @return string
     */
    public function vue(string $vue, bool $compress = false): string
    {
        // 移除HTML注释，但保留条件注释
        $vue = preg_replace('/<!--(?!\[if).*?-->/s', '', $vue);

        // 提取并处理style标签内容
        preg_match_all('/(<style[^>]*>)(.*?)(<\/style>)/s', $vue, $styles);
        foreach ($styles[0] as $key => $style) {
            $openingTag = $styles[1][$key];
            $content = $styles[2][$key];
            $closingTag = $styles[3][$key];
            $cleanedContent = $this->css($content, $compress);
            $cleanedStyle = $openingTag . $cleanedContent . $closingTag;
            $vue = str_replace($style, $cleanedStyle, $vue);
        }

        // 提取并处理script标签内容
        preg_match_all('/(<script[^>]*>)(.*?)(<\/script>)/s', $vue, $scripts);
        foreach ($scripts[0] as $key => $script) {
            $openingTag = $scripts[1][$key];
            $content = $scripts[2][$key];
            $closingTag = $scripts[3][$key];
            $cleanedContent = $this->js($content);
            $cleanedScript = $openingTag . $cleanedContent . $closingTag;
            $vue = str_replace($script, $cleanedScript, $vue);
        }

        // 移除Vue文件本身的多余换行
        $vue = preg_replace('/\n\s*\n+/', "\n", $vue);

        // 压缩Vue文件中的HTML部分（如果开启）
        if ($compress) {
            // 移除标签之间的多余空白字符
            $vue = preg_replace('/>\s+</', '><', $vue);
            // 移除行首和行尾的空白字符
            $vue = trim($vue);
        }

        return $vue;
    }

    /**
     * 移除PHP文件中的注释和多余换行，并可选混淆变量名
     *
     * @todo 混淆变量名暂不支持PHP8命名参数的风格，如：a('aa', A: 'abc') 这种风格
     * @param string $php PHP源代码
     * @param bool $obfuscator 是否开启混淆（默认 false）
     * @param array $blackList 自定义黑名单变量名列表（不进行混淆）
     * @return string 混淆后的PHP代码
     */
    public function php(string $php, bool $obfuscator = false, array $blackList = []): string
    {
        $code = trim($php);
        // 判断code的第一行是否为 #!/usr/bin/env php 是则剔除
        if (strpos($code, '#!/usr/bin/env php') === 0) {
            $code = trim(substr($code, 19));
        }
        if (empty($code)) {
            return $code;
        }

        $tokens = token_get_all($code);
        // 验证输入是否为有效的 PHP 源代码（需要包含 <?php 开头的打开标签）
        // 找第一个非空白/注释 token，若不是 T_OPEN_TAG 则认为输入无效
        foreach ($tokens as $t) {
            if (is_array($t)) {
                if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) {
                    continue;
                }
                if ($t[0] !== T_OPEN_TAG) {
                    throw new MinifierException('Invalid PHP code: missing opening <?php tag! file');
                }
                break;
            } else {
                throw new MinifierException('Invalid PHP code');
            }
        }

        $out = '';
        // 变量名映射：原始变量名 => 混淆后名称（每个文件单独映射）
        $varMap = [];
        $varCounter = 0;

        // 常见超全局与特殊变量黑名单（不进行混淆）
        $blackList = array_merge($blackList, [
            '$GLOBALS',
            '$HTTP_RAW_POST_DATA',
            '$http_response_header',
            '$php_errormsg',
            '$_GET',
            '$_POST',
            '$_REQUEST',
            '$_SERVER',
            '$_ENV',
            '$_COOKIE',
            '$_FILES',
            '$_SESSION',
            '$this'
        ]);

        // 扫描 tokens，收集由 `global` 语句声明的变量，这些全局变量应保留原名，不进行混淆
        $globalVars = [];
        for ($gi = 0; $gi < count($tokens); $gi++) {
            $tk = $tokens[$gi];
            if (is_array($tk) && $tk[0] === T_GLOBAL) {
                // 向后扫描直到遇到分号结束 global 语句
                for ($gj = $gi + 1; $gj < count($tokens); $gj++) {
                    $nt = $tokens[$gj];
                    if (is_string($nt) && $nt === ';') {
                        break;
                    }
                    if (is_array($nt) && $nt[0] === T_VARIABLE) {
                        $globalVars[$nt[1]] = true;
                    }
                }
            }
        }
        if (!empty($globalVars)) {
            foreach ($globalVars as $g => $_) {
                $blackList[] = $g;
            }
        }

        // 处理 Heredoc/Nowdoc：区分 nowdoc（不插值）和 heredoc（会进行变量插值）
        $inHeredoc = false;
        $inNowdoc = false;
        // 是否在处理包含换行的 T_OPEN_TAG 之后（用于避免在开标签后插入空格）
        $suppressSpaceAfterOpen = false;

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            // 辅助函数：判断当前位置的变量是否处于类属性声明或属性访问的上下文
            $isPropertyContext = function ($idx) use ($tokens) {
                $prev = null;
                $prevIdx = null;
                for ($j = $idx - 1; $j >= 0; $j--) {
                    $t = $tokens[$j];
                    if (is_array($t)) {
                        if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) {
                            continue;
                        }
                        $prev = $t;
                        $prevIdx = $j;
                        break;
                    }
                    if (trim($t) === '') continue;
                    $prev = $t;
                    $prevIdx = $j;
                    break;
                }
                // 如果前一个显著 token 是对象操作符或作用域解析符，则通常视为属性访问
                // 特例：对于对象操作符 "->"，若当前 token 本身是一个变量（$var），
                // 则它是可变属性访问（$obj->$var），此处的 $var 应该被视为普通变量并允许混淆。
                if ($prev === '->' || $prev === '::') {
                    // 获取当前 token，判断是否为变量
                    $curr = $tokens[$idx] ?? null;
                    if ($prev === '::') {
                        // 作用域解析符 (::) 后面的通常是静态属性或常量，不应重命名
                        return true;
                    }
                    // 对于 '->'，若当前是 T_VARIABLE，则允许重命名（非属性字面量）
                    if (is_array($curr) && $curr[0] === T_VARIABLE) {
                        return false;
                    }
                    return true;
                }
                if (is_array($prev)) {
                    $pid = $prev[0];
                    // 对象操作符 '->' 可能以数组形式出现（T_OBJECT_OPERATOR），
                    // 若前一个为 T_OBJECT_OPERATOR，需要进一步判断当前 token 是否为变量（可变属性）。
                    if ($pid === T_OBJECT_OPERATOR) {
                        $curr = $tokens[$idx] ?? null;
                        if (is_array($curr) && $curr[0] === T_VARIABLE) {
                            // $obj->$var 中的 $var 是变量名，应允许被混淆/处理
                            return false;
                        }
                        // 否则（例如字面属性名的情况），视为属性访问
                        return true;
                    }
                    if ($pid === T_PAAMAYIM_NEKUDOTAYIM) return true;
                    // 如果前一个 token 是可见性修饰符（如 public/protected/private/var），则视为属性声明
                    if (in_array($pid, [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR], true)) return true;
                    // static 可能出现在 "public static $var"（属性）或函数内部的 static（局部静态）中，
                    // 因此在遇到 T_STATIC 或 T_VAR 时向前再看一位以判断是否有可见性修饰符
                    if ($pid === T_STATIC || $pid === T_VAR) {
                        // 向前再查找一位，判断是否存在可见性修饰符
                        for ($k = $prevIdx - 1; $k >= 0; $k--) {
                            $t2 = $tokens[$k];
                            if (is_array($t2)) {
                                if ($t2[0] === T_WHITESPACE || $t2[0] === T_COMMENT || $t2[0] === T_DOC_COMMENT) continue;
                                if (in_array($t2[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR], true)) return true;
                                break;
                            }
                            if (trim($t2) === '') continue;
                            break;
                        }
                    }

                    // 处理带类型提示的属性：例如 "public string $prop"，前一个显著 token 可能是类型名 (T_STRING)
                    // 如果前一个 token 看起来像类型名，则向前继续查找是否存在可见性修饰符
                    $typeTokens = [T_STRING];
                    if (defined('T_NAME_QUALIFIED')) $typeTokens[] = constant('T_NAME_QUALIFIED');
                    if (defined('T_NAME_FULLY_QUALIFIED')) $typeTokens[] = constant('T_NAME_FULLY_QUALIFIED');
                    if (defined('T_ARRAY')) $typeTokens[] = constant('T_ARRAY');
                    if (defined('T_CALLABLE')) $typeTokens[] = constant('T_CALLABLE');
                    if (defined('T_ITERABLE')) $typeTokens[] = constant('T_ITERABLE');

                    if (in_array($pid, $typeTokens, true)) {
                        for ($k = $prevIdx - 1; $k >= 0; $k--) {
                            $t2 = $tokens[$k];
                            // 跳过空白与注释
                            if (is_array($t2)) {
                                if ($t2[0] === T_WHITESPACE || $t2[0] === T_COMMENT || $t2[0] === T_DOC_COMMENT) continue;
                                // 找到可见性修饰符则确定为属性
                                if (in_array($t2[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR, T_STATIC], true)) return true;
                                // 如果碰到另一个类型标识（如命名空间或 array/callable），继续向前扫描
                                if (in_array($t2[0], $typeTokens, true)) continue;
                                // 其它 token（如表达式开始等）则停止查找
                                break;
                            } else {
                                // 跳过类型相关的字符（nullable '?'，union '|'，intersection '&'，命名空间分隔符 '\\'）
                                if (trim($t2) === '') continue;
                                if (in_array($t2, ['?', '|', '&', '\\'], true)) continue;
                                break;
                            }
                        }
                    }
                }
                return false;
            };

            if ($inHeredoc) {
                if (is_array($token)) {
                    $tid = $token[0];
                    $ttext = $token[1];

                    // 不对 heredoc 中的属性名（T_STRING）进行替换，确保类属性名保持原样
                    if (!$inNowdoc && $tid === T_VARIABLE) {
                        // 若全局配置禁用变量重命名，则 heredoc 中也不进行任何重命名/替换
                        if (empty($obfuscator)) {
                            $out .= $ttext;
                            // 继续下一 token
                            continue;
                        }
                        // 使用 isPropertyContext 判断是否处于属性上下文
                        if ($isPropertyContext($i)) {
                            $out .= $ttext;
                        } else {
                            // 与常规代码分支保持一致的重命名策略：
                            // - 黑名单变量不重命名
                            // - 含有复杂插值（{ 或 [ ）的不改动
                            // - 仅对长度大于1的变量名进行混淆（保留如 $a 的短变量）
                            $name = $ttext;
                            if (in_array($name, $blackList, true)) {
                                $out .= $name;
                            } else {
                                if (strpos($name, '{') !== false || strpos($name, '[') !== false) {
                                    $out .= $name;
                                } else {
                                    $bare = ltrim($name, '$');
                                    if (strlen($bare) > 1) {
                                        if (!isset($varMap[$name])) {
                                            $varMap[$name] = '_v' . $varCounter++;
                                        }
                                        $out .= '$' . ltrim($varMap[$name], '$');
                                    } else {
                                        $out .= $name;
                                    }
                                }
                            }
                        }
                    } else {
                        $out .= $ttext;
                    }

                    if ($tid === T_END_HEREDOC) {
                        $inHeredoc = false;
                        $inNowdoc = false;
                    }
                } else {
                    $out .= $token;
                }
                continue;
            }

            if (is_array($token)) {
                $id = $token[0];
                $text = $token[1];

                // 不对普通代码中的 T_STRING（属性名）做替换，保持类属性名原样
                if ($id === T_START_HEREDOC) {
                    $inHeredoc = true;
                    $inNowdoc = (strpos($text, "<<<'") !== false);
                    $out .= $text;
                    continue;
                }

                // 跳过注释节点（包括文档注释）
                if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
                    // 不保留注释则直接跳过
                    continue;
                }

                // 如果是 PHP 打开标签，确保输出后至少有分隔符（空格或换行），否则某些工具/解析器可能无法正确识别文件内容
                if ($id === T_OPEN_TAG) {
                    $orig = $text;
                    $hasNewline = strpos($orig, "\n") !== false;
                    if ($hasNewline) {
                        // 即便原始 open tag 含换行，我们也改为输出单个空格以确保统一且能被识别为 PHP
                        $out .= rtrim($orig, " \t\r\n\0\x0B") . ' ';
                    } else {
                        // 若原始 open tag 无换行，确保输出后至少一个空格，避免直接与代码拼接
                        $out .= rtrim($orig, " \t\r\n\0\x0B") . ' ';
                    }
                    continue;
                }

                if ($id === T_WHITESPACE) {
                    // 普通空白压缩：将连续空白压缩为单空格（保留换行由后续正则处理），
                    // 注意：如果紧跟在包含换行的 open tag 后面，则不输出该空白
                    if ($suppressSpaceAfterOpen) {
                        $suppressSpaceAfterOpen = false;
                        continue;
                    }
                    $text = preg_replace('/\\s+/u', ' ', $text);
                    $out .= $text;
                    continue;
                }

                if ($id === T_VARIABLE) {
                    // 若配置关闭变量重命名，则直接输出原始变量文本
                    if (empty($obfuscator)) {
                        $out .= $text;
                        continue;
                    }

                    // 使用 $isPropertyContext 统一判断是否位于属性相关的上下文（属性访问或声明），
                    // 若是，则不要对该变量做混淆替换
                    if ($isPropertyContext($i)) {
                        $out .= $text;
                        continue;
                    }

                    $name = $text;
                    if (in_array($name, $blackList, true)) {
                        $out .= $name;
                        continue;
                    }

                    if (strpos($name, '{') !== false || strpos($name, '[') !== false) {
                        $out .= $name;
                        continue;
                    }

                    $bare = ltrim($name, '$');
                    // 只重命名长度大于1的变量名（保留短变量如 $a, $b 等不变）
                    if (strlen($bare) > 1) {
                        if (!isset($varMap[$name])) {
                            $varMap[$name] = '_v' . $varCounter++;
                        }
                        $out .= '$' . ltrim($varMap[$name], '$');
                    } else {
                        $out .= $name;
                    }
                    continue;
                }

                $out .= $text;
            } else {
                $out .= $token;
            }
        }

        // 最终清理：只清理空格并保留换行，避免破坏 heredoc/nowdoc
        // 仅移除运算符周围的水平空白（空格/制表符），不触及换行符
        // 注意：直接对整个 $out 运行正则会影响字符串字面量（如 "%s in %s"）中的空格，
        // 因此先把单/双引号字符串替换为占位符，清理完成后再还原。
        $placeholders = [];
        $index = 0;
        $outProtected = preg_replace_callback(
            '/(\'"(?:\\\\.|[^\\\\\"])*\'"|\'(?:\\\\.|[^\\\\\'])*\')/s',
            function ($m) use (&$placeholders, &$index) {
                $ph = '__OBF_STR_' . $index . '__';
                $placeholders[$ph] = $m[0];
                $index++;
                return $ph;
            },
            $out
        );

        // 执行原有的空白/运算符周围空格清理（作用域仅在占位符之外）
        $outProtected = preg_replace(
            ['/[ \t]*([;{}(),.=+\-<>\/*%&|!?:])[ \t]*/', '/[ \t]{2,}/'],
            ['\\1', ' '],
            $outProtected
        );

        // 还原被保护的字符串字面量
        if (!empty($placeholders)) {
            $out = strtr($outProtected, $placeholders);
        } else {
            $out = $outProtected;
        }

        return trim($out);
    }

    /**
     * 根据文件类型自动处理，并可选压缩内容
     * @param string $content
     * @param string $type 文件类型：html, css, js, vue, php
     * @param bool $compress 是否压缩内容（仅对HTML、CSS、Vue有效）, 当文件类型为php时，则表示为是否混淆
     * @return string
     */
    public function auto(string $content, string $type, bool $compress = false): string
    {
        switch (strtolower($type)) {
            case 'html':
                return $this->html($content, $compress);
            case 'css':
                return $this->css($content, $compress);
            case 'js':
                return $this->js($content);
            case 'vue':
                return $this->vue($content, $compress);
            case 'php':
                return $this->php($content, $compress);
            default:
                return $content;
        }
    }

    /**
     * 处理文件
     *
     * @param string $filePath
     * @param bool $compress 是否压缩或混淆内容（仅对HTML、CSS、Vue、PHP有效）
     * @return string 处理后的内容
     */
    public function file(string $filePath, bool $compress = false): string
    {
        if (!file_exists($filePath)) {
            throw new MinifierException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return $this->auto($content, $extension, $compress);
    }

    /**
     * 处理目录下所有文件
     *
     * @param string $inputDir 输入目录路径
     * @param string $outputDir 输出目录路径
     * @param bool $compress 是否压缩或混淆内容（仅对HTML、CSS、Vue、PHP有效）
     * @return bool 是否成功处理所有文件
     */
    public function directory(string $inputDir, string $outputDir, bool $compress): bool
    {
        $dir_iterator = new RecursiveDirectoryIterator($inputDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $sontDir = $outputDir . '/' . $iterator->getSubPathName();
                File::createDir($sontDir);
            } elseif ($item->isFile()) {
                $outputFile = $outputDir . '/' . $iterator->getSubPathName();
                $content = $this->file($item->getPathname(), $compress);
                $save = File::createFile($content, $outputFile, false);
                if (!$save) {
                    throw new MinifierException("Failed to write file: $outputFile");
                }
            }
        }

        return true;
    }
}
