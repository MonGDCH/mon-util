<?php

declare(strict_types=1);

namespace mon\util;

use RuntimeException;

/**
 * 操作系统工具类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class OS
{
    /**
     * 当前环境是否未windows运行环境
     *
     * @return boolean
     */
    public static function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * 获取服务器CPU内核数
     *
     * @return integer
     */
    public static function getCpuCount(): int
    {
        if (self::isWindows()) {
            $psCommand = 'Get-CimInstance Win32_Processor | Select-Object -ExpandProperty NumberOfLogicalProcessors | Measure-Object -Sum | Select-Object -ExpandProperty Sum';
            $count = shell_exec("powershell -Command \"{$psCommand}\" 2>&1");

            if ($count === null || strpos($count, '错误') !== false) {
                return 0;
            }

            return (int)trim($count);
        }

        $count = 0;
        if (strtolower(PHP_OS) === 'darwin') {
            $count = shell_exec('sysctl -n machdep.cpu.core_count');
        } else {
            $count = shell_exec('nproc');
        }

        return intval($count);
    }

    /**
     * 获取操作系统
     *
     * @return string
     */
    public static function getOS(): string
    {
        $os = php_uname('s');
        if (stripos($os, 'win') !== false) {
            return 'Windows';
        } elseif (stripos($os, 'linux') !== false) {
            return 'Linux';
        } elseif (stripos($os, 'darwin') !== false) {
            return 'Mac';
        }
        return $os;
    }

    /**
     * 获取本机mac地址
     *
     * @return string
     */
    public static function getMac(): string
    {
        if (!function_exists('exec')) {
            throw new RuntimeException('exec函数被禁用，无法获取mac地址');
        }
        $data = [];
        switch (strtolower(PHP_OS)) {
            case 'darwin':
            case 'linux':
                @exec('ifconfig -a', $data);
                break;
            case 'unix':
            case 'aix':
            case 'solaris':
                break;
            default:
                @exec('ipconfig /all', $data);
                if (!$data) {
                    $ipconfig = $_SERVER['WINDIR'] . '\system32\ipconfig.exe';
                    if (is_file($ipconfig)) {
                        @exec($ipconfig . ' /all', $data);
                    } else {
                        @exec($_SERVER['WINDIR'] . '\system\ipconfig.exe /all', $data);
                    }
                }
                break;
        }

        $mac = '';
        $tmp = [];
        foreach ($data as $value) {
            if (preg_match('/[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f][:-]' . '[0-9a-f][0-9a-f]/i', $value, $tmp)) {
                $mac = $tmp[0];
                break;
            }
        }
        unset($tmp);
        return $mac;
    }

    /**
     * 获取内存信息，单位GB
     *
     * @return array
     */
    public static function getMemoryInfo(): array
    {
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('shell_exec函数被禁用，无法获取内存信息');
        }

        $result = ['total' => 0.0, 'free' => 0.0, 'usage' => 0.0, 'rate' => 0.0];
        if (static::isWindows()) {
            $execPsCmd = function ($psCommand) {
                // 执行PowerShell命令：-NoProfile加速执行，2>NUL重定向错误输出
                $output = shell_exec("powershell -NoProfile -Command \"{$psCommand}\" 2>NUL");
                if ($output === null) {
                    return false;
                }

                // 清洗输出：去除所有空白字符，保留纯数字
                $cleanOutput = trim(preg_replace('/\s+/', '', $output));
                // 校验是否为纯数字（PHP 7.4 兼容的ctype_digit判断）
                return ctype_digit($cleanOutput) ? $cleanOutput : false;
            };

            // 1. 获取总物理内存（字节）
            $totalMemCmd = 'Get-CimInstance Win32_PhysicalMemory | Measure-Object -Property Capacity -Sum | Select-Object -ExpandProperty Sum';
            $totalBytes = $execPsCmd($totalMemCmd);
            if ($totalBytes !== false) {
                $result['total'] = round((int)$totalBytes / 1024 / 1024 / 1024, 2);
            }

            // 2. 获取可用物理内存（KB）
            $freeMemCmd = 'Get-CimInstance Win32_OperatingSystem | Select-Object -ExpandProperty FreePhysicalMemory';
            $freeKb = $execPsCmd($freeMemCmd);
            if ($freeKb !== false) {
                $result['free'] = round((int)$freeKb / 1024 / 1024, 2);
            }

            // 3. 计算已使用内存（确保数值非负，PHP 7.4 兼容）
            $result['usage'] = round(max(0, $result['total'] - $result['free']), 2);
        } else {
            // 读取 /proc/meminfo 更可靠
            $content = @file_get_contents('/proc/meminfo');
            if ($content !== false) {
                $vals = [];
                foreach (['MemTotal', 'MemAvailable', 'MemFree', 'Buffers', 'Cached'] as $k) {
                    if (preg_match('/^' . preg_quote($k) . ':\s+(\d+)/mi', $content, $m)) {
                        $vals[$k] = (int)$m[1]; // KB
                    }
                }
                $totalKb = $vals['MemTotal'] ?? 0;
                $availableKb = $vals['MemAvailable'] ?? ($vals['MemFree'] + ($vals['Buffers'] ?? 0) + ($vals['Cached'] ?? 0));
                if ($totalKb > 0) {
                    $result['total'] = round($totalKb / 1024 / 1024, 2); // GB
                    $result['free'] = round($availableKb / 1024 / 1024, 2);
                    $result['usage'] = round(($totalKb - $availableKb) / 1024 / 1024, 2);
                }
            }
        }

        $result['rate'] = $result['total'] > 0 ? floatval(sprintf('%.2f', ($result['usage'] / $result['total']) * 100)) : 0.0;
        return $result;
    }

    /**
     * 获取磁盘信息
     *
     * @return array
     */
    public static function getDiskInfo(): array
    {
        // 检查shell_exec函数是否被禁用
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('shell_exec函数被禁用，无法获取磁盘信息');
        }

        $disk = [];
        if (static::isWindows()) {
            // PowerShell命令：获取本地固定磁盘（DriveType=3），输出Caption,Size,FreeSpace为CSV格式（解析更稳定）
            $psCommand = 'Get-CimInstance Win32_LogicalDisk -Filter "DriveType=3" | Select-Object Caption,Size,FreeSpace | ConvertTo-Csv -NoTypeInformation';
            // 执行命令：2>NUL适配Windows错误重定向，-NoProfile加速PowerShell执行
            $output = shell_exec("powershell -NoProfile -Command \"{$psCommand}\" 2>NUL");
            if ($output === null || trim($output) === '') {
                return $disk;
            }

            // 解析CSV格式的输出
            $csvLines = array_filter(array_map('trim', explode("\n", $output)));
            if (count($csvLines) < 2) { // 至少包含标题行+数据行
                return $disk;
            }

            // 移除CSV标题行，解析数据行
            array_shift($csvLines);
            foreach ($csvLines as $line) {
                // 去除CSV的引号并分割字段（处理"C:","123456789","987654321"格式）
                $parts = str_getcsv($line);
                if (count($parts) < 3 || !preg_match('/^[A-Z]:$/', $parts[0])) {
                    continue;
                }

                // 赋值并转换为整数（处理空值/非数值情况）
                $caption = $parts[0]; // 盘符（如C:）
                $freeSpace = $parts[2] !== '' ? (int)$parts[2] : 0; // 可用空间（字节）
                $size = $parts[1] !== '' ? (int)$parts[1] : 0; // 总容量（字节）
                $used = max(0, $size - $freeSpace); // 已用空间（字节）
                $rate = $size > 0 ? sprintf('%.2f%%', ($used / $size) * 100) : '0%'; // 使用率

                // 组装磁盘信息（保留你原有的字段结构）
                $disk[] = [
                    'sys'     => $caption,
                    'size'    => File::formatByteText($size), // 复用你的字节格式化方法
                    'free'    => File::formatByteText($freeSpace),
                    'used'    => File::formatByteText($used),
                    'rate'    => $rate,
                    'mounted' => $caption,
                ];
            }
        } else {
            // 使用 POSIX 兼容输出，避免 -h 导致单位混合，使用 -P 保持列稳定
            $out = shell_exec('df -P -T 2>/dev/null');
            $lines = array_filter(array_map('trim', explode("\n", strval($out))));
            // 跳过标题行
            array_shift($lines);
            foreach ($lines as $line) {
                // 只处理以 /dev 或 /dev/mapper 开头的行（常见分区）
                if (preg_match('/^\/dev[\/\w\-\._]*/', $line)) {
                    $parts = preg_split('/\s+/', $line);
                    // df -P -T 格式：Filesystem Type 1024-blocks Used Available Use% Mounted_on
                    if (count($parts) >= 6) {
                        $disk[] = [
                            'sys'     => $parts[0],
                            'size'    => $parts[2] ?? $parts[1],
                            'used'    => $parts[3] ?? $parts[2],
                            'free'    => $parts[4] ?? $parts[3],
                            'rate'    => $parts[5] ?? ($parts[4] ?? ''),
                            'mounted' => end($parts),
                        ];
                    }
                }
            }
        }
        return $disk;
    }

    /**
     * 获取PHP环境信息
     *
     * @return array
     */
    public static function getPHPInfo(): array
    {
        return [
            'php_version'         => PHP_VERSION,
            'os'                  => PHP_OS,
            'memory_limit'        => ini_get('memory_limit'),
            'max_execution_time'  => ini_get('max_execution_time'),
            'error_reporting'     => ini_get('error_reporting'),
            'display_errors'      => ini_get('display_errors'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'extension_dir'       => ini_get('extension_dir'),
            'loaded_extensions'   => implode(', ', get_loaded_extensions()),
        ];
    }

    /**
     * 获取 CPU 信息
     *
     * @return array
     */
    public static function getCpuInfo(): array
    {
        $cpu = static::getCpuUsage();
        $cache = static::getCpuCache();
        return [
            // CPU 名称
            'name'  => static::getCpuName(),
            // 物理核心数
            'physics' =>  static::getCpuPhysicsCores(),
            // 逻辑核心数
            'logic' => static::getCpuLogicCores(),
            // 缓存大小
            'cache' => $cache ? File::formatByteText($cache) : 0,
            // CPU 使用率（%）
            'usage' => $cpu,
            // 可用 CPU 百分比（%）
            'free'  => round(100 - $cpu, 2),
        ];
    }

    /**
     * 获取 CPU 逻辑核心数
     *
     * @return string
     */
    public static function getCpuLogicCores(): int
    {
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('shell_exec函数被禁用，无法获取CPU逻辑核心数');
        }

        if (static::isWindows()) {
            // PowerShell命令：获取所有CPU的逻辑处理器数并直接求和（返回纯数值）
            $psCommand = 'Get-CimInstance Win32_Processor | Measure-Object -Property NumberOfLogicalProcessors -Sum | Select-Object -ExpandProperty Sum';
            // 执行PowerShell命令（-NoProfile加速执行，2>NUL重定向错误输出）
            $output = shell_exec("powershell -NoProfile -Command \"{$psCommand}\" 2>NUL");
            // 处理基础输出：过滤空值并转换为整数
            $num = $output !== null ? intval(trim($output)) : 0;
            // 确保返回有效数值，失败时返回1（与原代码默认值一致）
            return $num > 0 ? $num : 1;
        }

        return (int)str_replace("\n", '', shell_exec('cat /proc/cpuinfo | grep "processor" | wc -l'));
    }

    /**
     * 获取 CPU 物理核心数
     *
     * @return int
     */
    public static function getCpuPhysicsCores(): int
    {
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('shell_exec函数被禁用，无法获取CPU物理核心数');
        }
        if (static::isWindows()) {
            // PowerShell命令：获取所有CPU的物理核心数并求和（直接返回数值）
            $psCommand = 'Get-CimInstance Win32_Processor | Measure-Object -Property NumberOfCores -Sum | Select-Object -ExpandProperty Sum';
            // 执行PowerShell命令（-NoProfile加速，2>NUL重定向错误）
            $output = shell_exec("powershell -NoProfile -Command \"{$psCommand}\" 2>NUL");
            // 处理输出：过滤空值，转换为整数
            $num = $output !== null ? intval(trim($output)) : 0;
            return $num > 0 ? $num : 1;
        }

        $num = str_replace("\n", '', shell_exec('cat /proc/cpuinfo | grep "physical id" | sort | uniq | wc -l'));
        $num = intval($num);
        return $num === 0 ? 1 : $num;
    }

    /**
     * 获取 CPU 缓存大小，单位 bye
     *
     * @return integer
     */
    public static function getCpuCache(): int
    {
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('shell_exec函数被禁用，无法获取CPU缓存大小');
        }
        $cacheBytes = 0;
        if (static::isWindows()) {
            // 定义PowerShell执行函数（复用逻辑，避免重复代码）
            $getCpuCache = function (string $cacheProperty) {
                // PowerShell命令：获取CPU缓存大小（求和多CPU的缓存，返回纯数值）
                $psCommand = "Get-CimInstance Win32_Processor | Measure-Object -Property {$cacheProperty} -Sum | Select-Object -ExpandProperty Sum";
                // 执行命令：-NoProfile加速，2>NUL重定向错误（Windows兼容）
                $output = shell_exec("powershell -NoProfile -Command \"{$psCommand}\" 2>NUL");

                // 适配32位PHP在64位Windows的PowerShell路径
                if (empty($output)) {
                    $sysNativePs = 'C:\Windows\SysNative\WindowsPowerShell\v1.0\powershell.exe';
                    if (is_file($sysNativePs)) {
                        $output = shell_exec("\"{$sysNativePs}\" -NoProfile -Command \"{$psCommand}\" 2>NUL");
                    }
                }

                // 解析输出：转换为整数（Win32_Processor中缓存属性单位为KB）
                $cacheKb = $output !== null ? intval(trim($output)) : 0;
                return ($cacheKb > 0) ? $cacheKb * 1024 : 0;
            };
            $cacheBytes = $getCpuCache('L3CacheSize');
            // 步骤1：优先获取L3缓存（L3CacheSize）
            if (!$cacheBytes) {
                // 步骤2：L3缓存获取失败，获取L2缓存（L2CacheSize）
                $cacheBytes = $getCpuCache('L2CacheSize');
            }
        } else {
            // 尝试 /proc/cpuinfo
            $content = @file_get_contents('/proc/cpuinfo');
            if ($content !== false && preg_match_all('/cache size\s*:\s*(\d+)\s*KB/i', $content, $matches)) {
                // 使用第一个匹配值（KB -> B）
                $cacheBytes = (int)$matches[1][0] * 1024;
            } else {
                // fallback to lscpu
                $out = shell_exec("lscpu 2>/dev/null | egrep 'L3|L2' | awk '{print \$NF}'");
                if ($out !== null) {
                    // 解析可能包含单位，如 '8192K' 或 '6M'
                    if (preg_match('/(\d+)([KkMmGg]?)/', trim($out), $m)) {
                        $num = (int)$m[1];
                        $unit = strtoupper($m[2] ?? '');
                        switch ($unit) {
                            case 'G':
                                $num *= 1024 * 1024 * 1024;
                                break;
                            case 'M':
                                $num *= 1024 * 1024;
                                break;
                            case 'K':
                                $num *= 1024;
                                break;
                            default:
                                break;
                        }
                        $cacheBytes = $num;
                    }
                }
            }
        }

        return $cacheBytes > 0 ? (int)$cacheBytes : 0;
    }

    /**
     * 获取 CPU 使用率
     *
     * @return float
     */
    public static function getCpuUsage(): float
    {
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('shell_exec函数被禁用，无法获取CPU使用率');
        }
        if (static::isWindows()) {
            // PowerShell命令：输出 LoadPercentage=数值 的键值对格式（与wmic /Value一致）
            // 取第一个CPU的负载百分比（与原代码逻辑对齐）
            $psCommand = 'Get-CimInstance Win32_Processor | Select-Object -First 1 LoadPercentage | ForEach-Object { "LoadPercentage=$($_.LoadPercentage)" }';
            // 执行命令：2>NUL 是Windows的错误重定向（替换原代码的2>/dev/null），-NoProfile加速执行
            $cpuOutput = shell_exec("powershell -NoProfile -Command \"{$psCommand}\" 2>NUL");
            // 复刻原代码的正则匹配逻辑
            if (preg_match('/LoadPercentage=(\d+)/i', strval($cpuOutput), $m)) {
                return (float)$m[1];
            }

            // 匹配失败返回0.0
            return 0.0;
        }
        // 使用 /proc/stat 第一行计算更可靠
        $start = static::calculationCpu();
        sleep(1);
        $end = static::calculationCpu();
        $totalDiff = $end['total'] - $start['total'];
        $idleDiff  = $end['idle'] - $start['idle'];
        if ($totalDiff <= 0) {
            return 0.0;
        }
        $usage = (1 - ($idleDiff / $totalDiff)) * 100;
        return floatval(sprintf('%.2f', max(0, min(100, $usage))));
    }

    /**
     * 计算 CPU
     *
     * @return array
     */
    protected static function calculationCpu(): array
    {
        $content = @file_get_contents('/proc/stat');
        if ($content === false) {
            return ['total' => 0, 'idle' => 0];
        }
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (strpos($line, 'cpu ') === 0) {
                $parts = preg_split('/\s+/', trim($line));
                // parts[0] = 'cpu', parts[1..] are numbers: user nice system idle iowait irq softirq steal ...
                $nums = array_slice($parts, 1);
                $nums = array_map('intval', $nums);
                $total = array_sum($nums);
                // idle is index 3 (idle) + index 4 (iowait) sometimes considered idle
                $idle = ($nums[3] ?? 0) + ($nums[4] ?? 0);
                return ['total' => $total, 'idle' => $idle];
            }
        }
        return ['total' => 0, 'idle' => 0];
    }

    /**
     * 获取 CPU 名称
     *
     * @return string
     */
    public static function getCpuName(): string
    {
        if (static::isWindows()) {
            // PowerShell命令：输出 Name=CPU名称 的键值对格式（与wmic /Value一致）
            // 取第一个CPU的名称（与原代码逻辑对齐，多CPU场景下取主CPU名称）
            $psCommand = 'Get-CimInstance Win32_Processor | Select-Object -First 1 Name | ForEach-Object { "Name=$($_.Name)" }';
            // 执行命令：2>NUL 是Windows的错误重定向（替换原代码的2>/dev/null），-NoProfile加速执行
            $nameOutput = shell_exec("powershell -NoProfile -Command \"{$psCommand}\" 2>NUL");
            // 复刻原代码的正则匹配逻辑，捕获CPU名称
            if (preg_match('/Name=(.+)/i', strval($nameOutput), $m)) {
                return trim($m[1]);
            }
            // 匹配失败返回unknown
            return 'unknown';
        }
        // Linux: 首选 /proc/cpuinfo 的 model name
        $content = @file_get_contents('/proc/cpuinfo');
        if ($content !== false && preg_match('/model name\s*:\s*(.+)/i', $content, $m)) {
            return trim($m[1]);
        }
        // fallback to lscpu
        $out = shell_exec("lscpu 2>/dev/null");
        if ($out !== null && preg_match('/Model name:\s*(.+)/i', $out, $m)) {
            return trim($m[1]);
        }
        // 最终尝试 architecture
        if ($out !== null && preg_match('/Architecture:\s*(.+)/i', $out, $m)) {
            return trim($m[1]);
        }
        return 'unknown';
    }

    /**
     * 获取系统已安装的打印机列表
     *
     * @return array 打印机名数组（失败返回空数组）
     */
    public static function getPrinterList(): array
    {
        $printers = [];
        // 执行PowerShell命令：获取打印机名称，格式化为纯文本（避免复杂JSON/XML解析）
        // -Command：执行指定命令；Get-Printer：获取所有打印机；Select-Object Name：只取名称列；
        // Out-String：转为字符串；2>&1：捕获错误输出
        $psCommand = 'powershell -Command "Get-Printer | Select-Object -ExpandProperty Name" 2>&1';
        $output = shell_exec($psCommand);
        // 检查命令执行结果
        if ($output === null) {
            throw new RuntimeException("无法执行PowerShell命令，可能是权限不足");
        }

        // 解析输出：按行分割，过滤空行和无效内容
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            $line = trim($line);
            // 过滤错误提示（如命令不存在时的报错）
            if (!empty($line) && strpos($line, 'Get-Printer') === false) {
                $printers[] = $line;
            }
        }

        // 处理无打印机的情况
        if (empty($printers) && strpos($output, 'No printers found') !== false) {
            // throw new RuntimeException("系统中未安装任何打印机");
            return [];
        }

        return $printers;
    }
}
