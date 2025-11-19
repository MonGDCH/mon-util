<?php

declare(strict_types=1);

namespace mon\util;

use InvalidArgumentException;

/**
 * 雪花算法生成ID
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class SnowFlake
{
    use Instance;

    /**
     * 开始时间
     *
     * @var integer
     */
    protected $twepoch = 1707031970000;

    /**
     * 数据中心(机房) id
     *
     * @var integer
     */
    protected $datacenterId = 0;

    /**
     * 机器号
     *
     * @var integer
     */
    protected $workerId = 0;

    /**
     * 各部分位宽（可调整）
     */
    protected $datacenterIdBits = 5;
    protected $workerIdBits = 5;
    protected $sequenceBits = 12;

    /**
     * 派生值：最大值与移位量
     */
    protected $maxDatacenterId;
    protected $maxWorkerId;
    protected $sequenceMask;
    protected $workerIdShift;
    protected $datacenterIdShift;
    protected $timestampLeftShift;

    /**
     * 同一时间的序列
     *
     * @var integer
     */
    protected $sequence = 0;

    /**
     * 最近一次时间戳
     *
     * @var integer
     */
    protected $lastTimestamp = 0;

    /**
     * 同一时间的序列所占的位数 12个bit (默认)
     *
     * @var integer
     */
    protected $maxSequence = 4095;

    /**
     * 私有化构造方法
     */
    protected function __construct() {}

    /**
     * 配置，设置机房号及机器号
     *
     * @param integer $datacenter   机房号
     * @param integer $worker       机器号
     * @return SnowFlake
     */
    public function setConfig(int $datacenter, int $worker): SnowFlake
    {
        $this->initConstants();

        if ($datacenter > $this->maxDatacenterId || $datacenter < 0) {
            throw new InvalidArgumentException("datacenter Id can't be greater than [{$this->maxDatacenterId}] or less than 0");
        }

        if ($worker > $this->maxWorkerId || $worker < 0) {
            throw new InvalidArgumentException("worker Id can't be greater than [{$this->maxWorkerId}] or less than 0");
        }

        $this->datacenterId = $datacenter;
        $this->workerId = $worker;
        return $this;
    }

    /**
     * 生成雪花ID
     *
     * @return string
     */
    public function createID(): string
    {
        // 延迟初始化常量（保证位掩码正确）
        $this->initConstants();

        // 获取当前时间戳，单位毫秒
        $timestamp = $this->currentTime();
        // 如果 twepoch 误设为秒级，则转为毫秒（兼容旧值）
        $twepoch = $this->twepoch;
        if ($twepoch < 1000000000000) {
            // 小于 1e12 视为秒
            $twepoch = $twepoch * 1000;
        }
        if ($timestamp < $this->lastTimestamp) {
            $t = $this->lastTimestamp - $timestamp;
            throw new InvalidArgumentException("Clock moved backwards. Refusing to generate id for {$t} milliseconds");
        }

        // 去重
        if ($this->lastTimestamp === $timestamp) {
            // 增加序列并做掩码，掩码结果为0表示溢出到下一毫秒
            $this->sequence = ($this->sequence + 1) & $this->sequenceMask;
            if ($this->sequence === 0) {
                // 序列溢出，等待下一毫秒
                $timestamp = $this->tilNextMillisecond($this->lastTimestamp);
            }
        } else {
            // 新的时间窗口，序列从0开始
            $this->sequence = 0;
        }

        // 记录上一次的时间戳
        $this->lastTimestamp = $timestamp;

        // 偏移计算
        $id = ((($timestamp - $twepoch) << $this->timestampLeftShift) |
            ($this->datacenterId << $this->datacenterIdShift) |
            ($this->workerId << $this->workerIdShift) |
            $this->sequence);

        // 注意：在 32 位 PHP 上可能会溢出，需跨平台稳定使用将返回类型改为 string
        return (string)$id;
    }

    /**
     * 获取机房号
     *
     * @return integer
     */
    public function getDatacenterId(): int
    {
        return $this->datacenterId;
    }

    /**
     * 获取机器号
     *
     * @return integer
     */
    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    /**
     * 获取最新一次获取的时间戳
     *
     * @return integer
     */
    public function getLastTimestamp(): int
    {
        return $this->lastTimestamp;
    }

    /**
     * 初始化派生常量（延迟初始化）
     */
    protected function initConstants(): void
    {
        if ($this->maxDatacenterId !== null) {
            return;
        }
        $this->maxDatacenterId = (1 << $this->datacenterIdBits) - 1;
        $this->maxWorkerId     = (1 << $this->workerIdBits) - 1;
        $this->sequenceMask    = (1 << $this->sequenceBits) - 1;
        $this->workerIdShift   = $this->sequenceBits;
        $this->datacenterIdShift = $this->sequenceBits + $this->workerIdBits;
        $this->timestampLeftShift = $this->sequenceBits + $this->workerIdBits + $this->datacenterIdBits;
    }

    /**
     * 获取下一毫秒值
     *
     * @param integer $lastTimestamp
     * @return integer
     */
    protected function tilNextMillisecond(int $lastTimestamp): int
    {
        // 获取最新时间戳
        $timestamp = $this->currentTime();
        // 如果发现最新的时间戳小于或者等于序列号已经超4095的那个时间戳
        while ($timestamp <= $lastTimestamp) {
            // 不符合则继续
            usleep(500);
            $timestamp = $this->currentTime();
        }
        return $timestamp;
    }

    /**
     * 获取毫秒值
     *
     * @return integer
     */
    protected function currentTime(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
