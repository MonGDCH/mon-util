<?php

namespace mon\util;

/**
 * 日志处理
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Log
{
    use Instance;

    /**
     * 日志级别
     */
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    /**
     * 日志配置
     *
     * @var array
     */
    protected $config = [
        'maxSize'       => 20480000,     // 日志文件大小
        'logPath'       => '',           // 日志目录
        'rollNum'       => 3,            // 日志滚动卷数
        'logName'       => '',           // 日志名称，空则使用当前日期作为名称
        'splitLine'     => '====================================================================================',
    ];

    /**
     * 日志信息
     *
     * @var array
     */
    protected $log = [];

    /**
     * 初始化日志配置
     *
     * @param array $config 配置信息
     */
    protected function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * 定义日志配置信息
     *
     * @param  array  $config 配置信息
     * @return Log
     */
    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 获取日志信息
     *
     * @param string $type 信息类型
     * @return array
     */
    public function getLog($type = null)
    {
        return $type ? $this->log[$type] : $this->log;
    }

    /**
     * 清空日志信息
     *
     * @return Log
     */
    public function clear()
    {
        $this->log = [];
        return $this;
    }

    /**
     * 记录日志信息
     *
     * @param string  $message  日志信息
     * @param string  $type     日志类型
     * @param boolean $trace    是否开启日志追踪
     * @param integer $level    日志追踪层级，一般不需要自定义
     * @return Log
     */
    public function record($message, $type = Log::INFO, $trace = false, $level = 1)
    {
        if ($trace) {
            $traceInfo = debug_backtrace(false, $level);
            $infoLevel = $level - 1;
            $file = $traceInfo[$infoLevel]['file'];
            $line = $traceInfo[$infoLevel]['line'];
            $message = "[{$file} => {$line}] " . $message;
        }

        $this->log[strtolower($type)][] = $message;
        return $this;
    }

    /**
     * 记录调试信息
     *
     * @param string  $message  日志信息
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function debug($message, $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->record($message, Log::DEBUG, $trace, $level);
    }

    /**
     * 记录一般信息
     *
     * @param string  $message  日志信息
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function info($message, $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->record($message, Log::INFO, $trace, $level);
    }

    /**
     * 记录通知信息
     *
     * @param string  $message  日志信息
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function notice($message, $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->record($message, Log::NOTICE, $trace, $level);
    }

    /**
     * 记录警告信息
     *
     * @param string  $message  日志信息
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function warning($message, $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->record($message, Log::WARNING, $trace, $level);
    }

    /**
     * 记录错误信息
     *
     * @param string  $message  日志信息
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function error($message, $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->record($message, Log::ERROR, $trace, $level);
    }

    /**
     * 批量写入日志
     *
     * @return boolean
     */
    public function save()
    {
        if (!empty($this->log)) {
            // 解析获取日志内容
            $log = $this->parseLog($this->log);
            $time = time();
            $logName = empty($this->config['logName']) ? date('Ym', $time) . DIRECTORY_SEPARATOR . date('Ymd', $time) : $this->config['logName'];
            $path = $this->config['logPath'] . DIRECTORY_SEPARATOR . $logName;
            // 分卷记录日志
            $save = File::instance()->subsectionFile($log, $path, $this->config['maxSize'], $this->config['rollNum']);
            // 保存成功，清空日志
            if ($save) {
                $this->clear();
            }
            return $save;
        }

        return true;
    }

    /**
     * 解析日志
     *
     * @param  array $logs 日志列表
     * @return string 解析生成的日志字符串
     */
    protected function parseLog($logs)
    {
        $log = '';
        $now = date('Y-m-d H:i:s', time());
        foreach ($logs as $type => $value) {
            $offset = "[{$now}] [{$type}] ";

            if (is_array($value)) {
                $info = '';
                foreach ($value as $msg) {
                    $info = $info . $offset . $msg . PHP_EOL;
                }
            } else {
                $info = $offset . $value . PHP_EOL;
            }
            $log .= $info;
        }

        // 添加分割线
        if (!empty($this->config['splitLine'])) {
            $log .= $this->config['splitLine'] . PHP_EOL;
        }

        return $log;
    }
}
