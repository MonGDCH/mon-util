<?php

declare(strict_types=1);

namespace mon\util;

use RuntimeException;

/**
 * 时间日期相关操作
 *
 * @author Mon 985558837@qq.com
 * @version 1.0.4 优化代码 2022-09-16
 */
class Date
{
    /**
     * 日期的时间戳
     *
     * @var integer
     */
    protected $date;

    /**
     * 年
     *
     * @var integer
     */
    protected $year;

    /**
     * 月
     *
     * @var integer
     */
    protected $month;

    /**
     * 日
     *
     * @var integer
     */
    protected $day;

    /**
     * 时
     *
     * @var integer
     */
    protected $hour;

    /**
     * 分
     *
     * @var integer
     */
    protected $minute;

    /**
     * 秒
     *
     * @var integer
     */
    protected $second;

    /**
     * 毫秒
     *
     * @var float
     */
    protected $milliSecond;

    /**
     * 星期的数字表示
     *
     * @var integer
     */
    protected $weekDay;

    /**
     * 一年中的天数 0－365
     *
     * @var integer
     */
    protected $yDay;

    /**
     * 构造方法
     *
     * @param string $date 日期
     * @return void
     */
    public function __construct($date = null)
    {
        $this->date = $this->parse($date);
        $this->setDate($this->date);
    }

    /**
     * 日期分析，获取时间戳
     *
     * @param  mixed $date 日期
     * @return integer 时间戳
     */
    public function parse($date = null): int
    {
        // 字符串解析
        if (is_numeric($date)) {
            // 数字格式直接转换为时间戳
            $tmpdate = $date;
        } elseif (is_null($date)) {
            // 为空默认取得当前时间戳
            $tmpdate = time();
        } elseif (is_string($date)) {
            if (($date == '') || strtotime($date) == false) {
                // 为空默认取得当前时间戳
                $tmpdate = time();
            } else {
                // 把字符串转换成UNIX时间戳
                $tmpdate = strtotime($date);
            }
        } else {
            // 默认取当前时间戳
            $tmpdate = time();
        }

        return $tmpdate;
    }

    /**
     * 日期相关参数设置
     *
     * @param integer $timestamp 日期时间戳
     * @return Date
     */
    public function setDate(int $timestamp): Date
    {
        // 时间信息
        $dateArray          = getdate($timestamp);
        // 时间戳
        $this->date         = $dateArray[0];
        // 秒
        $this->second       = $dateArray['seconds'];
        // 毫秒
        list($milliSecond)  = explode(' ', (string)microtime());
        $this->milliSecond  = $milliSecond;
        // 分 
        $this->minute       = $dateArray['minutes'];
        // 时
        $this->hour         = $dateArray['hours'];
        // 日
        $this->day          = $dateArray['mday'];
        // 月
        $this->month        = $dateArray['mon'];
        // 年 
        $this->year         = $dateArray['year'];
        // 星期 0～6
        $this->weekDay      = $dateArray['wday'];
        // 一年中的天数 0－365
        $this->yDay         = $dateArray['yday'];

        return $this;
    }

    /**
     * 获取时间戳
     *
     * @return integer
     */
    public function getTime(): int
    {
        return $this->date;
    }

    /**
     * 获取毫秒数
     *
     * @param boolean $timeStamp    是否返回完整时间戳
     * @return float
     */
    public function getMilliSecond(bool $timeStamp = true)
    {
        return $timeStamp ? (float)sprintf('%.0f', ($this->date + $this->milliSecond) * 1000) : $this->milliSecond;
    }

    /**
     * 获取当前周几, 0-6，0为周天
     *
     * @return integer
     */
    public function getWeek(): int
    {
        return $this->weekDay;
    }

    /**
     * 获取当前一年中第几天，0－365
     *
     * @return integer
     */
    public function getYearDay(): int
    {
        return $this->yDay;
    }

    /**
     * 日期格式化
     * 默认返回 1970-01-01 11:30:45 格式
     *
     * @param string $format  格式化参数
     * @return string
     */
    public function format(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, $this->date);
    }

    /**
     * 获取日期开始和结束的时间戳
     *
     * @param integer $timeStamp 时间戳，默认当天
     * @return array
     */
    public function getDayTime(?int $timeStamp = null): array
    {
        $date = !is_null($timeStamp) ? $timeStamp : $this->date;
        list($y, $m, $d) = explode('-', date('Y-m-d', $date));
        return [
            mktime(0, 0, 0, (int)$m, (int)$d, (int)$y),
            mktime(23, 59, 59, (int)$m, (int)$d, (int)$y)
        ];
    }

    /**
     * 获取周开始和结束的时间戳
     *
     * @param integer $timeStamp 默认当周
     * @return array
     */
    public function getWeekTime(?int $timeStamp = null): array
    {
        $date = !is_null($timeStamp) ? $timeStamp : $this->date;
        list($y, $m, $d, $w) = explode('-', date('Y-m-d-w', $date));
        // 修正周日的问题
        if ($w == 0) {
            $w = 7;
        }
        return [
            mktime(0, 0, 0, (int)$m, (int)$d - (int)$w + 1, (int)$y),
            mktime(23, 59, 59, (int)$m, (int)$d - (int)$w + 7, (int)$y)
        ];
    }

    /**
     * 获取月开始和结束的时间戳
     *
     * @param integer $timeStamp 默认当月
     * @return array
     */
    public function getMonthTime(?int $timeStamp = null): array
    {
        $date = !is_null($timeStamp) ? $timeStamp : $this->date;
        list($y, $m, $t) = explode('-', date('Y-m-t', $date));
        return [
            mktime(0, 0, 0, (int)$m, 1, (int)$y),
            mktime(23, 59, 59, (int)$m, (int)$t, (int)$y)
        ];
    }

    /**
     * 获取年开始和结束的时间戳
     *
     * @param integer $timeStamp 默认当年
     * @return array
     */
    public function getYearTime(?int $timeStamp = null): array
    {
        $date = !is_null($timeStamp) ? $timeStamp : $this->date;
        $y = date('Y', $date);
        return [
            mktime(0, 0, 0, 1, 1, (int)$y),
            mktime(23, 59, 59, 12, 31, (int)$y)
        ];
    }

    /**
     * 根据指定日期和1~7来获取周一至周日对应的日期
     *
     * @param integer $weekday 指定返回周几的日期（1~7），默认为返回周一对应的日期
     * @param string $date 指定日期，为空则默认为当前对象时间
     * @param string $format 指定返回日期的格式
     * @return  string
     */
    public function getWeekDay(int $weekday = 1, string $date = '', string $format = 'Y-m-d'): string
    {
        $time = $date ? strtotime($date) : $this->date;

        return date($format, $time - 86400 * (date('N', $time) - $weekday));
    }

    /**
     * 是否为闰年
     *
     * @param string $year 年份
     * @return boolean
     */
    public function isLeapYear(?int $year = null)
    {
        if (is_null($year)) {
            $year = $this->year;
        }
        return ((($year % 4) == 0) && (($year % 100) != 0) || (($year % 400) == 0));
    }

    /**
     * 计算日期差
     *
     *  w - 周
     *  d - 天
     *  h - 时
     *  m - 月
     *  s - 秒
     *  i - 分
     *  y - 年
     *
     * @param mixed $date 要比较的日期
     * @param string $elaps  比较跨度
     * @return integer|float
     */
    public function dateDiff($date, string $elaps = 'd')
    {
        $days_per_week  = 7;
        $days_per_month = 30;
        $days_per_year  = 365;
        $hours_in_day   = 24;
        $minutes_in_day = 1440;
        $seconds_in_day = 86400;
        //计算天数差
        $dayselaps = ($this->parse($date) - $this->date) / $seconds_in_day;
        switch ($elaps) {
            case 'y':
                // 转换成年
                $dayselaps = $dayselaps / $days_per_year;
                break;
            case 'm':
                // 转换成月
                $dayselaps = $dayselaps / $days_per_month;
                break;
            case 'w':
                // 转换成星期
                $dayselaps = $dayselaps / $days_per_week;
                break;
            case 'h':
                // 转换成小时
                $dayselaps = $dayselaps * $hours_in_day;
                break;
            case 'i':
                // 转换成分钟
                $dayselaps = $dayselaps * $minutes_in_day;
                break;
            case 's':
                // 转换成秒
                $dayselaps = $dayselaps * $seconds_in_day;
                break;
        }

        return $dayselaps;
    }

    /**
     * 人性化的计算日期差
     *
     * @param mixed $time 要比较的时间
     * @param mixed $precision 返回的精度
     * @return string
     */
    public function timeDiff($time, $precision = null): string
    {
        if (!is_null($precision) && !is_numeric($precision) && !is_bool($precision)) {
            $_diff = ['y' => '年', 'm' => '个月', 'd' => '天', 'h' => '小时', 'i' => '分钟', 's' => '秒', 'w' => '周'];
            return ceil($this->dateDiff($time, $precision)) . $_diff[$precision] . '前';
        }
        $diff = abs($this->parse($time) - $this->date);
        $chunks = [[31536000, '年'], [2592000, '个月'], [604800, '周'], [86400, '天'], [3600, '小时'], [60, '分钟'], [1, '秒']];
        $count = 0;
        $since = '';
        for ($i = 0; $i < count($chunks); $i++) {
            if ($diff >= $chunks[$i][0]) {
                $num = floor($diff / $chunks[$i][0]);
                $since .= sprintf('%d' . $chunks[$i][1], $num);
                $diff = intval($diff - $chunks[$i][0] * $num);
                $count++;
                if (!$precision || $count >= $precision) {
                    break;
                }
            }
        }

        return $since . '前';
    }

    /**
     * 比对月份查，只比较月份
     *
     * @param mixed $date  对比的日期
     * @return integer|float
     */
    public function monthDiff($date)
    {
        $start = explode('-', $this->format('Y-m'), 2);
        $end = explode('-', date('Y-m', $this->parse($date)), 2);
        return abs($start[0] - $end[0]) * 12 + abs($start[1] - $end[1]);
    }

    /**
     * 返回周的某一天 返回Date对象
     *
     * @param integer $n 星期几
     * @return Date
     */
    public function getDayOfWeek($n): Date
    {
        $week = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        return new self($week[$n]);
    }

    /**
     * 计算周的第一天 返回Date对象
     *
     * @return Date
     */
    public function firstDayOfWeek(): Date
    {
        return $this->getDayOfWeek(1);
    }

    /**
     * 计算月份的第一天 返回Date对象
     *
     * @return Date
     */
    public function firstDayOfMonth(): Date
    {
        return (new self(mktime(0, 0, 0, $this->month, 1, $this->year)));
    }

    /**
     * 计算年份的第一天 返回Date对象
     *
     * @return Date
     */
    public function firstDayOfYear(): Date
    {
        return (new self(mktime(0, 0, 0, 1, 1, $this->year)));
    }

    /**
     * 计算周的最后一天 返回Date对象
     *
     * @return Date
     */
    public function lastDayOfWeek(): Date
    {
        return $this->getDayOfWeek(0);
    }

    /**
     * 计算月份的最后一天 返回Date对象
     *
     * @return Date
     */
    public function lastDayOfMonth(): Date
    {
        return (new self(mktime(0, 0, 0, $this->month + 1, 0, $this->year)));
    }

    /**
     * 计算年份的最后一天 返回Date对象
     *
     * @return Date
     */
    public function lastDayOfYear(): Date
    {
        return (new self(mktime(0, 0, 0, 1, 0, $this->year + 1)));
    }

    /**
     * 计算月份的最大天数
     *
     * @return integer|float
     */
    public function maxDayOfMonth()
    {
        return $this->dateDiff(strtotime($this->dateAdd(1, 'm')->format()), 'd');
    }

    /**
     * 取得指定间隔日期
     *
     *    y    - 年
     *    m    - 月
     *    d    - 日
     *    h    - 小时
     *    i    - 分钟
     *    s    - 秒
     *    q    - 季度
     *    w    - 周
     *
     * @param integer $number 间隔数目
     * @param string $interval  间隔类型
     * @return Date
     */
    public function dateAdd(int $number = 0, string $interval = 'd'): Date
    {
        $year = $this->year;
        $month = $this->month;
        $day = $this->day;
        $hours = $this->hour;
        $minutes = $this->minute;
        $seconds = $this->second;
        switch ($interval) {
            case 'y':
                // 年
                $year += $number;
                break;
            case 'm':
                // 月
                $month += $number;
                break;
            case 'd':
                // 日
                $day += $number;
                break;
            case 'h':
                // 时
                $hours += $number;
                break;
            case 'i':
                // 分
                $minutes += $number;
                break;
            case 's':
                // 秒
                $seconds += $number;
                break;
            case 'q':
                // 季
                $month += ($number * 3);
                break;
            case 'w':
                // 周
                $day += ($number * 7);
                break;
            default:
                throw new RuntimeException('未支持的间隔类型');
        }

        return new self(mktime($hours, $minutes, $seconds, $month, $day, $year));
    }

    /**
     * 月中日期数字转中文
     *
     * @param integer $day 日期数字
     * @return string
     */
    public function mdayToCh(?int $mday = null): string
    {
        $mday = is_null($mday) ? $this->day : intval($mday);
        $array  = ['一', '二', '三', '四', '五', '六', '七', '八', '九', '十'];
        $str = '';
        if ($mday == 0) {
            $str .= '十';
        }
        if ($mday < 10) {
            $str .= $array[$mday - 1];
        } elseif ($mday < 20) {
            $str .= '十' . $array[$mday - 11];
        } elseif ($mday  <  30) {
            $str .= '二十' . $array[$mday - 21];
        } else {
            $str .= '三十' . $array[$mday - 31];
        }

        return $str;
    }

    /**
     * 年份数字转中文
     *
     * @param integer $year 年份数字
     * @param boolean $flag 是否显示公元
     * @return string
     */
    public function yearToCh(?int $year = null, bool $flag = false): string
    {
        $year = is_null($year) ? $this->year : intval($year);
        $array = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
        $str = $flag ? '公元' : '';
        for ($i = 0; $i < 4; $i++) {
            $str .= $array[Common::instance()->mSubstr((string)$year, $i, 1)];
        }

        return $str;
    }

    /**
     *  判断日期 所属 干支 生肖 星座
     *  type 参数：XZ 星座 GZ 干支 SX 生肖
     *
     * @param string $type  获取信息类型
     * @return string
     */
    public function magicInfo(string $type): string
    {
        $result = '';
        $y = $this->year;
        $m = $this->month;
        $d = $this->day;

        switch ($type) {
            case 'XZ':
                // 星座
                $XZDict = ['摩羯', '宝瓶', '双鱼', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手'];
                $Zone   = [1222, 122, 222, 321, 421, 522, 622, 722, 822, 922, 1022, 1122, 1222];
                if ((100 * $m + $d) >= $Zone[0] || (100 * $m + $d) < $Zone[1]) {
                    $i = 0;
                } else {
                    for ($i = 1; $i < 12; $i++) {
                        if ((100 * $m + $d) >= $Zone[$i] && (100 * $m + $d) < $Zone[$i + 1]) {
                            break;
                        }
                    }
                }
                $result = $XZDict[$i] . '座';
                break;
            case 'GZ':
                // 干支
                $GZDict = [
                    ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'],
                    ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']
                ];
                $i = $y - 1900 + 36;
                $result = $GZDict[0][$i % 10] . $GZDict[1][$i % 12];
                break;
            case 'SX':
                // 生肖
                $SXDict = ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'];
                $result = $SXDict[($y - 4) % 12];
                break;
        }

        return $result;
    }

    /**
     * 魔术方法，支持字符串输出对象
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format();
    }
}
