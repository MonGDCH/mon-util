<?php

declare(strict_types=1);

namespace mon\util;

use mon\util\exception\LotteryException;

/**
 * 概率抽奖工具类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 2021-03-18
 */
class Lottery
{
    /**
     * 奖品列表
     *
     * @var array
     */
    protected $awards = [];

    /**
     * 概率总和
     *
     * @var integer
     */
    protected $probabilityCount = 100;

    /**
     * 奖品概率索引
     *
     * @var string
     */
    protected $probabilityKey = 'probability';

    /**
     * 支持概率的小数位数
     *
     * @var integer
     */
    protected $scale = 4;

    /**
     * 构造方法
     *
     * @param array $awards 奖品列表
     * @param string $probabilityKey 奖品概率索引
     * @param string $notWin 未中奖信息
     * @param integer $scale 支持概率的小数位数
     * @param integer $probabilityCount 概率总和
     * @throws LotteryException
     */
    public function __construct(array $awards, string $probabilityKey = 'probability', string $notWin = '抱歉，您未中奖', int $scale = 4, int $probabilityCount = 100)
    {
        $this->awards = $awards;
        $this->probabilityKey = $probabilityKey;
        $this->scale = $scale;
        $this->probabilityCount = $probabilityCount;

        // 计算奖品总概率
        $totalProbability = '0';
        foreach ($this->awards as $item) {
            $totalProbability = bcadd($totalProbability, (string)$item[$this->probabilityKey], $this->scale);
            // 标记中奖
            $item['isWin'] = true;
        }

        if (bccomp($totalProbability, (string)$this->probabilityCount, $this->scale) > 0) {
            throw new LotteryException('概率总和必须小于等于' . $this->probabilityCount . '，当前总和为：' . $totalProbability, LotteryException::ERROR_PROBABILITY_MIN);
        }

        // 未中奖奖项
        $notAward = [
            'title' => $notWin,
            'isWin' => false,
            $this->probabilityKey => bcsub((string)$this->probabilityCount, $totalProbability, $this->scale)
        ];
        $this->awards[] = $notAward;

        // 打乱顺序
        shuffle($this->awards);
    }

    /**
     * 生成抽奖工具类
     *
     * @param array $awards 奖品列表
     * @param string $probabilityKey 奖品概率索引
     * @param string $notWin 未中奖信息
     * @param integer $scale 支持概率的小数位数
     * @param integer $probabilityCount 概率总和
     * @return Lottery
     */
    public static function gen(array $awards, string $probabilityKey = 'probability', string $notWin = '抱歉，您未中奖', int $scale = 4, int $probabilityCount = 100): Lottery
    {
        return new self($awards, $probabilityKey, $notWin, $scale, $probabilityCount);
    }

    /**
     * 采用经典概率算法进行抽奖
     *
     * @throws LotteryException
     * @return array 返回数据中，isWin为true则表示中奖
     */
    public function draw(): array
    {
        $remainingProbability = $this->probabilityCount;
        $pow = pow(10, $this->scale);
        foreach ($this->awards as $item) {
            $randInum = random_int(1, intval($remainingProbability * $pow));
            $randNum = bcdiv((string)$randInum, (string)$pow, $this->scale);
            if (bccomp($randNum, (string)$item[$this->probabilityKey], $this->scale) <= 0) {
                return $item;
            }
            $remainingProbability = bcsub((string)$remainingProbability, (string)$item[$this->probabilityKey], $this->scale);
        }

        throw new LotteryException('抽奖失败，未抽到奖品', LotteryException::ERROR_NOT_AWARD);
    }

    /**
     * 获取奖品列表
     *
     * @return array
     */
    public function getAwards(): array
    {
        return $this->awards;
    }
}
