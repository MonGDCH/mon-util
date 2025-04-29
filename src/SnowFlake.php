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
    protected $twepoch = 1707031970;

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
     * 最大机房号
     * datacenterId所占的位数 5个bit 最大:11111(2进制)--> 31(10进制)
     * 5 bit最多只能有31个数字，机房id最多只能是32以内
     * 
     * @var integer
     */
    protected $maxDatacenterId = 32;

    /**
     * 最大机器号
     * workerId所占的位数 5个bit 最大:11111(2进制)--> 31(10进制)
     * 5 bit最多只能有31个数字，机房id最多只能是32以内
     * 
     * @var integer
     */
    protected $maxWorkerId = 32;

    /**
     * 同一时间的序列所占的位数 12个bit 111111111111(2进制) = 4095(10进制) 最多就是同一毫秒生成4096个
     * 用于序号的与运算，保证序号最大值在0-4095之间
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
     * @return void
     */
    public function setConfig(int $datacenter, int $worker): void
    {
        if ($datacenter > $this->maxDatacenterId || $datacenter < 0) {
            throw new InvalidArgumentException("datacenter Id can't be greater than [{$this->maxDatacenterId}] or less than 0");
        }

        if ($worker > $this->maxWorkerId || $worker < 0) {
            throw new InvalidArgumentException("worker Id can't be greater than [{$this->maxWorkerId}] or less than 0");
        }

        $this->datacenterId = $datacenter;
        $this->workerId = $worker;
    }

    /**
     * 生成雪花ID
     *
     * @return integer
     */
    public function createID(): int
    {
        // 获取当前时间戳，单位毫秒
        $timestamp = $this->currentTime();
        if ($timestamp < $this->lastTimestamp) {
            $t = $this->lastTimestamp - $timestamp;
            throw new InvalidArgumentException("Clock moved backwards. Refusing to generate id for {$t} milliseconds");
        }

        // 去重
        if ($this->lastTimestamp == $timestamp) {
            $this->sequence += 1;
            // sequence序列大于4095
            if ($this->sequence == $this->maxSequence) {
                // 调用到下一个时间戳的方法
                $timestamp = $this->tilNextMillisecond($this->lastTimestamp);
            }
        } else {
            // 如果是当前时间的第一次获取，那么就置为0
            $this->sequence = 0;
        }

        // 记录上一次的时间戳
        $this->lastTimestamp = $timestamp;

        // 偏移计算
        return (($timestamp - $this->twepoch) << 22) |
            ($this->datacenterId << 17) |
            ($this->workerId << 12) |
            $this->sequence;
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
        return intval(microtime(true) * 1000);
    }
}
