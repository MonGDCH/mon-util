<?php

declare(strict_types=1);

namespace mon\util;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use mon\util\exception\ObfuscationException;

/**
 * PHP代码混淆器
 * 
 * @author monLam <985558837@qq.com>
 * @version 1.0.0
 */
class Obfuscator
{
    /**
     * 需要过滤掉的变量名列表（不进行混淆）
     *
     * @var array
     */
    protected $fillterVars = [
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
    ];

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 是否对变量名进行混淆（默认 true）
        'renameVariables' => true,
        // 是否保留注释（默认 false，保留=false 表示会移除注释）
        'preserveComments' => false,
        // 在保留注释时是否规范化注释中的换行为 LF（默认 false）
        'normalizeCommentNewlines' => false,
    ];

    /**
     * 构造函数
     *
     * @param array $config 配置项
     */
    public function __construct(array $config = [], array $fillterVars = [])
    {
        if (!empty($fillterVars)) {
            $this->fillterVars = array_merge($this->fillterVars, $fillterVars);
        }

        $this->config = array_merge($this->config, $config);
    }

    /**
     * 混淆PHP代码
     *
     * @param string $code PHP源代码
     * @param array $config 覆盖默认配置的选项
     * @return string 混淆后的PHP代码
     * @throws ObfuscationException 当输入代码无效时抛出异常
     */
    public function encode(string $code, array $config = []): string
    {
        $opts = array_merge($this->config, $config);

        $code = trim($code);
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
                    dd($t);
                    throw new ObfuscationException('Invalid PHP code: missing opening <?php tag! file');
                }
                break;
            } else {
                throw new ObfuscationException('Invalid PHP code');
            }
        }
        $out = '';
        // 变量名映射：原始变量名 => 混淆后名称（每个文件单独映射）
        $varMap = [];
        $varCounter = 0;
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
                // 如果前一个显著 token 是对象操作符或作用域解析符，则视为属性访问
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
                        if (empty($opts['renameVariables'])) {
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
                            if (in_array($name, $this->fillterVars, true)) {
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
                    // 注释处理：根据配置决定保留或丢弃注释
                    if (!empty($opts['preserveComments'])) {
                        $ctext = $text;
                        if (!empty($opts['normalizeCommentNewlines'])) {
                            // 将 CRLF/CR 统一为 LF
                            $ctext = preg_replace("/\r\n?|\r/", "\n", $ctext);
                        }
                        $out .= $ctext;
                    }
                    // 若不保留注释则直接跳过
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
                    if (empty($opts['renameVariables'])) {
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
                    if (in_array($name, $this->fillterVars, true)) {
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
        $out = preg_replace(
            ['/[ \t]*([;{}(),.=+\-<>\/*%&|!?:])[ \t]*/', '/[ \t]{2,}/'],
            ['\\1', ' '],
            $out
        );

        return trim($out);
    }

    /**
     * 混淆并保存PHP文件
     *
     * @param string $inputFile 输入PHP文件路径
     * @param string $outputFile 输出混淆后PHP文件路径
     * @param array $config 覆盖默认配置的选项
     * @return bool 成功返回true
     * @throws ObfuscationException 当输入文件不可读或输出文件写入失败时抛出异常
     */
    public function encodeFile(string $inputFile, string $outputFile, array $config = []): bool
    {
        if (!file_exists($inputFile) || !is_readable($inputFile)) {
            throw new ObfuscationException("Input file is not readable: $inputFile");
        }
        $code = File::read($inputFile);
        if ($code === false) {
            throw new ObfuscationException("Failed to read file: $inputFile");
        }
        try {
            $obfuscated = $this->encode($code, $config);
        } catch (ObfuscationException $e) {
            throw new ObfuscationException("Failed to obfuscate file: $inputFile. " . $e->getMessage());
        }
        $result = File::createFile($obfuscated, $outputFile, false);
        if (!$result) {
            throw new ObfuscationException("Failed to write file: $outputFile");
        }

        return true;
    }

    /**
     * 混淆并保存目录下所有PHP文件
     *
     * @param string $inputDir 输入目录路径
     * @param string $outputDir 输出目录路径
     * @param array $config 覆盖默认配置的选项
     * @return bool 成功返回true
     * @throws ObfuscationException 当输入目录不可读、输出目录创建失败或文件写入失败时抛出异常
     */
    public function encodeDirectory(string $inputDir, string $outputDir, array $config = []): bool
    {
        $dir_iterator = new RecursiveDirectoryIterator($inputDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $sontDir = $outputDir . '/' . $iterator->getSubPathName();
                File::createDir($sontDir);
            } elseif ($item->isFile() && strtolower($item->getExtension()) === 'php') {
                $file = $outputDir . '/' . $iterator->getSubPathName();
                $content = File::read($item->getPathname());
                try {
                    $obfuscated = $this->encode($content, $config);
                } catch (ObfuscationException $e) {
                    throw new ObfuscationException("Failed to obfuscate file: $item. " . $e->getMessage());
                }
                $save = File::createFile($obfuscated, $file, false);
                if (!$save) {
                    throw new ObfuscationException("Failed to write file: $file");
                }
            }
        }

        return true;
    }

    /**
     * 混淆应用，将应用目录下的所有PHP文件混淆并保存到输出目录，非php文件及排除的文件目录则原样复制
     *
     * @param string $appPath 应用目录路径
     * @param string $buildPath 输出目录路径
     * @param array $excludePath 排除的路径列表
     * @param array $excludeNames 排除的文件名列表
     * @param array $config 配置选项
     * @return bool 成功返回true
     */
    public function encodeApp(string $appPath, string $buildPath, array $excludePath = [], array $excludeNames = [], array $config = []): bool
    {
        // 修正路径
        $excludePath = array_map(function ($dir) {
            // 将目录分割符号\,/统一修改为 DIRECTORY_SEPARATOR
            return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dir);
        }, $excludePath);

        // 迭代应用目录
        $dir_iterator = new RecursiveDirectoryIterator($appPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathname();
            // 判断是否在排除路径列表中，
            foreach ($excludePath as $pattern) {
                if (strpos($relativePath, $pattern) === 0) {
                    continue 2;
                }
            }
            // 判断是否在排除文件名列表中
            if (in_array($item->getFilename(), $excludeNames)) {
                continue;
            }

            $filePath = $item->getPathname();
            $newPath = $buildPath . DIRECTORY_SEPARATOR . $relativePath;
            if ($item->isDir()) {
                // 创建目录
                $createDir = File::createDir($newPath);
                if (!$createDir) {
                    throw new ObfuscationException("Failed to create dir: $newPath");
                }
                continue;
            }
            if ($item->getExtension() !== 'php') {
                // 复制非php文件
                $copy = File::copyFile($filePath, $newPath, true);
                if (!$copy) {
                    throw new ObfuscationException("Failed to copy file: $filePath to $newPath");
                }
                continue;
            }
            // php文件，处理文件
            $save = $this->encodeFile($filePath, $newPath, $config);
            if (!$save) {
                throw new ObfuscationException("Failed to write file: $newPath");
            }
        }

        return true;
    }
}
