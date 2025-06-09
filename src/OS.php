<?php

declare(strict_types=1);

namespace mon\util;

/**
 * 操作系统工具类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class OS
{
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
        if (static::getOS() === 'Windows') {
            $cap = shell_exec('wmic Path Win32_PhysicalMemory Get Capacity | findstr /V "Capacity"');
            $cap = trim($cap ?? '');
            $total = array_sum(array_map('intval', explode("\n", $cap)));
            $result['total'] = round($total / 1024 / 1024 / 1024, 2);

            $free = shell_exec('wmic OS get FreePhysicalMemory | findstr /V "FreePhysicalMemory"');
            $result['free']  = round(intval($free) / 1024 / 1024, 2);
            $result['usage'] = round($result['total'] - $result['free'], 2);
        } else {
            $total = shell_exec('grep MemTotal /proc/meminfo | awk \'{print $2}\'');
            $available = shell_exec('grep MemAvailable /proc/meminfo | awk \'{print $2}\'');

            $result['total'] = sprintf('%.2f', $total / 1024 / 1024);
            $result['free']  = sprintf('%.2f', $available / 1024 / 1024);
            $result['usage'] = sprintf('%.2f', ($total - $available) / 1024 / 1024);
        }

        $result['rate']  = floatval(sprintf('%.2f', ($result['usage'] / $result['total']) * 100));
        return $result;
    }

    /**
     * 获取磁盘信息
     *
     * @return array
     */
    public static function getDiskInfo(): array
    {
        $disk = [];
        if (static::getOS() === 'Windows') {
            // Windows 系统
            $drives = shell_exec('wmic logicaldisk get size,freespace,caption');
            $lines  = explode("\n", trim($drives ?? ''));
            foreach ($lines as $line) {
                if (preg_match('/^([A-Z]:)/', $line, $matches)) {
                    $parts = preg_split('/\s+/', trim($line));
                    if (count($parts) >= 3) {
                        $disk[] = [
                            'sys'       => $matches[1],
                            'size'      => File::formatByteText(intval($parts[2])),
                            'free'      => File::formatByteText(intval($parts[1])),
                            'used'      => File::formatByteText(intval($parts[2]) - intval($parts[1])),
                            'rate'      => sprintf('%.2f', (intval($parts[2]) - intval($parts[1])) / intval($parts[2]) * 100) . '%',
                            'mounted'   => $matches[1],
                        ];
                    }
                }
            }
        } else {
            // Linux 系统
            $diskInfo = shell_exec('df -h');
            $lines    = explode("\n", trim($diskInfo ?? ''));
            foreach ($lines as $line) {
                if (preg_match('/^\/dev\/\w+/', $line)) {
                    $parts  = preg_split('/\s+/', $line);
                    $disk[] = [
                        'sys'       => $parts[0],
                        'size'      => $parts[1],
                        'free'      => $parts[3],
                        'used'      => $parts[2],
                        'rate'      => $parts[4],
                        'mounted'   => $parts[5],
                    ];
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
        if (static::getOS() === 'Windows') {
            $num  = shell_exec('wmic cpu get NumberOfLogicalProcessors | findstr /V "NumberOfLogicalProcessors"');
            $num  = trim($num ?? '1');
            $nums = explode("\n", $num);
            $num  = 0;
            foreach ($nums as $n) {
                $num += intval(trim($n));
            }
            return $num;
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
        if (static::getOS() === 'Windows') {
            $num  = shell_exec('wmic cpu get NumberOfCores | findstr /V "NumberOfCores"');
            $num  = trim($num ?? '1');
            $nums = explode("\n", $num);
            $num  = 0;
            foreach ($nums as $n) {
                $num += intval(trim($n));
            }
            return $num;
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
        if (static::getOS() === 'Windows') {
            $cache = shell_exec('wmic cpu get L3CacheSize | findstr /V "L3CacheSize"');
            $cache = trim($cache ?? '');
            if ($cache === '') {
                $cache = shell_exec('wmic cpu get L2CacheSize | findstr /V "L2CacheSize"');
                $cache = trim($cache ?? '');
            }
            if ($cache !== '') {
                $cache = [0, intval($cache) * 1024];
            }
        } else {
            preg_match('/(\d+)/', shell_exec('cat /proc/cpuinfo | grep "cache size"'), $cache);
            if (count($cache) === 0) {
                $cache = trim(shell_exec("lscpu | grep L3 | awk '{print \$NF}'") ?? '');
                if ($cache === '') {
                    $cache = trim(shell_exec("lscpu | grep L2 | awk '{print \$NF}'") ?? '');
                }
                if ($cache !== '') {
                    $cache = [0, intval(str_replace(['K', 'B'], '', strtoupper($cache)))];
                }
            }
        }

        return $cache[1] ? intval($cache[1]) : 0;
    }

    /**
     * 获取 CPU 使用率
     *
     * @return float
     */
    public static function getCpuUsage(): float
    {
        if (static::getOS() === 'Windows') {
            $cpu = shell_exec('wmic cpu get LoadPercentage | findstr /V "LoadPercentage"');
            return floatval(trim($cpu ?? '0'));
        }
        $start = static::calculationCpu();
        sleep(1);
        $end = static::calculationCpu();

        $totalStart = $start['total'];
        $totalEnd   = $end['total'];
        $timeStart = $start['time'];
        $timeEnd   = $end['time'];
        $time = $timeEnd - $timeStart;
        $total = $totalEnd - $totalStart;
        if ($time <= 0 || $total <= 0) {
            return 0;
        }

        return floatval(sprintf('%.2f', $time / $total * 10));
    }

    /**
     * 计算 CPU
     *
     * @return array
     */
    protected static function calculationCpu(): array
    {
        $mode   = '/(cpu)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)/';
        $string = shell_exec('cat /proc/stat | grep cpu');
        $matches = [];
        preg_match_all($mode, strval($string), $matches);

        $total = array_sum(array_slice($matches[2], 0, 8));
        $time  = $matches[2][0] + $matches[3][0] + $matches[4][0] + $matches[6][0] + $matches[7][0] + $matches[8][0] + $matches[9][0];

        return ['total' => $total, 'time' => $time];
    }

    /**
     * 获取 CPU 名称
     *
     * @return string
     */
    public static function getCpuName(): string
    {
        if (static::getOS() === 'Windows') {
            $name = shell_exec('wmic cpu get Name | findstr /V "Name"');
            return trim($name);
        }

        preg_match('/^\s+\d\s+(.+)/', shell_exec('cat /proc/cpuinfo | grep name | cut -f2 -d: | uniq -c') ?? '', $matches);
        if (count($matches) === 0) {
            $name = trim(shell_exec("lscpu| grep Architecture | awk '{print $2}'") ?? '');
            if ($name !== '') {
                $mfMhz = trim(shell_exec("lscpu| grep 'MHz' | awk '{print \$NF}' | head -n1") ?? '');
                $mfGhz = trim(shell_exec("lscpu| grep 'GHz' | awk '{print \$NF}' | head -n1") ?? '');
                if ($mfMhz === '' && $mfGhz === '') {
                    return $name;
                } elseif ($mfGhz !== '') {
                    return $name . ' @ ' . $mfGhz . 'GHz';
                } elseif ($mfMhz !== '') {
                    return $name . ' @ ' . round(intval($mfMhz) / 1000, 2) . 'GHz';
                }
            } else {
                return 'unknown';
            }
        }
        return $matches[1] ?? "unknown";
    }
}
