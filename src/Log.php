<?php

namespace mon\util;

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * 日志处理
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.1.0   引入psr/log标准 2022-07-26
 */
class Log implements LoggerInterface
{
    use Instance;

    /**
     * 日志配置
     *
     * @var array
     */
    protected $config = [
        // 日志文件大小
        'maxSize'       => 20480000,
        // 日志目录
        'logPath'       => __DIR__,
        // 日志滚动卷数   
        'rollNum'       => 3,
        // 日志名称，空则使用当前日期作为名称       
        'logName'       => '',
        // 日志分割符
        'splitLine'     => '====================================================================================',
        // 是否自动执行save方法保存日志
        'save'          => false,
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
    public function __construct(array $config = [])
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
     * 任意级别日志信息
     *
     * @param string $level     日志级别
     * @param string $message   日志信息
     * @param array $context    替换内容
     * @param boolean $trace    是否开启日志追踪
     * @param integer $layer    日志追踪层级，一般不需要自定义
     * @return Log
     */
    public function log($level, $message, array $context = [], $trace = false, $layer =  1)
    {
        // 内容替换
        if (!empty($context)) {
            $replace = [];
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }

            $message = strtr($message, $replace);
        }
        // 日志追踪
        if ($trace) {
            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $layer);
            $infoLayer = $layer - 1;
            $file = $traceInfo[$infoLayer]['file'];
            $line = $traceInfo[$infoLayer]['line'];
            $message = "[{$file} => {$line}] " . $message;
        }
        // 记录日志
        $this->log[$level][] = $message;
        // 写入日志
        if ($this->config['save']) {
            $this->save();
        }
        return $this;
    }

    /**
     * 系统无法使用错误级别信息
     *
     * @param string $message   日志信息
     * @param array $context    替换内容
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function emergency($message, array $context = [], $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->log(LogLevel::EMERGENCY, $message, $context, $trace, $layer);
    }

    /**
     * 必须立即采取行动错误级别信息
     *
     * 例如: 整个网站宕机了，数据库挂了，等等。 这应该发送短信通知警告你.
     * @param string $message   日志信息
     * @param array $context    替换内容
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function alert($message, array $context = [], $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->log(LogLevel::ALERT, $message, $context, $trace, $layer);
    }

    /**
     * 临界错误级别信息
     *
     * 例如: 应用组件不可用，意外的异常
     * @param string $message   日志信息
     * @param array $context    替换内容
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function critical($message, array $context = [], $trace = false)
    {
        $layer = $trace ? 2 : 1;
        return $this->log(LogLevel::CRITICAL, $message, $context, $trace, $layer);
    }

    /**
     * 运行时错误级别信息
     *
     * @param string $message   日志信息
     * @param array $context    替换内容
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function error($message, array $context = [], $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->log(LogLevel::ERROR, $message, $context, $trace, $level);
    }

    /**
     * 警告级别错误信息
     *
     * 例如: 使用过时的API，API使用不当
     * @param string $message   日志信息
     * @param array $context    替换内容
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function warning($message, array $context = [], $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->log(LogLevel::WARNING, $message, $context, $trace, $level);
    }

    /**
     * 事件级别信息
     *
     * @param string $message   日志信息
     * @param array $context    替换内容
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function notice($message, array $context = [], $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->log(LogLevel::NOTICE, $message, $context, $trace, $level);
    }

    /**
     * 一般级别信息
     *
     * @param string $message   日志信息
     * @param array $context    替换内容
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function info($message, array $context = [], $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->log(LogLevel::INFO, $message, $context, $trace, $level);
    }

    /**
     * 调试级别信息
     *
     * @param string $message   日志信息
     * @param array $context    替换内容
     * @param boolean $trace    是否开启日志追踪
     * @return Log
     */
    public function debug($message, array $context = [], $trace = false)
    {
        $level = $trace ? 2 : 1;
        return $this->log(LogLevel::DEBUG, $message, $context, $trace, $level);
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
