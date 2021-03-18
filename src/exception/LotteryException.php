<?php

namespace mon\util\exception;

use Exception;

/**
 * 抽奖异常
 */
class LotteryException extends Exception
{
    /**
     * 未初始化抽奖配置
     */
    const ERROR_NOT_INIT = 0;

    /**
     * 抽奖失败，未抽到奖品
     */
    const ERROR_NOT_AWARD = -1;

    /**
     * 概率总和必须小于等于概率总和
     */
    const ERROR_PROBABILITY_MIN = -2;
}
