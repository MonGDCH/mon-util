<?php

namespace mon\util;

/**
 * 时间日期相关操作
 *
 * @author Mon 985558837@qq.com
 * @version 1.0.1 2021-03-18 移除内置单例
 * @version 1.0.2 增加获取年月日周开始、结束时间
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
     * 时区
     *
     * @var integer
     */
    protected $timezone;

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
     * 星期的数字表示
     *
     * @var integer
     */
    protected $weekday;

    /**
     * 星期的完整表示
     *
     * @var string
     */
    protected $cWeekday;

    /**
     * 一年中的天数 0－365
     *
     * @var integer
     */
    protected $yDay;

    /**
     * 月份的完整表示
     *
     * @var string
     */
    protected $cMonth;

    /**
     * 日期CDATE表示
     *
     * @var string
     */
    protected $CDATE;

    /**
     * 日期的YMD表示
     *
     * @var string
     */
    protected $YMD;

    /**
     * 时间的输出表示
     *
     * @var string
     */
    protected $CTIME;

    /**
     * 星期的输出
     *
     * @var array
     */
    protected $Week = array("日", "一", "二", "三", "四", "五", "六");

    /**
     * 构造方法
     *
     * @param string $date 日期
     * @return void
     */
    public function __construct($date = '')
    {
        $this->date = $this->parse($date);
        $this->setDate($this->date);
    }

    /**
     * 日期分析，获取时间戳
     *
     * @param  mixed $date 日期
     * @return string 时间戳
     */
    public function parse($date)
    {
        // 字符串解析
        if (is_numeric($date)) {
            //数字格式直接转换为时间戳
            $tmpdate = $date;
        } elseif (is_null($date)) {
            //为空默认取得当前时间戳
            $tmpdate = time();
        } elseif (is_string($date)) {
            if (($date == '') || strtotime($date) == -1) {
                //为空默认取得当前时间戳
                $tmpdate = time();
            } else {
                //把字符串转换成UNIX时间戳
                $tmpdate = strtotime($date);
            }
        } else {
            //默认取当前时间戳
            $tmpdate = time();
        }

        return $tmpdate;
    }

    /**
     * 日期相关参数设置
     *
     * @param integer $date 日期时间戳
     * @return Date
     */
    public function setDate($date)
    {
        $dateArray          =   getdate($date);
        $this->date         =   $dateArray[0];            //时间戳
        $this->second       =   $dateArray["seconds"];    //秒
        $this->minute       =   $dateArray["minutes"];    //分
        $this->hour         =   $dateArray["hours"];      //时
        $this->day          =   $dateArray["mday"];       //日
        $this->month        =   $dateArray["mon"];        //月
        $this->year         =   $dateArray["year"];       //年

        $this->weekday      =   $dateArray["wday"];       //星期 0～6
        $this->cWeekday     =   '星期' . $this->Week[$this->weekday]; //$dateArray["weekday"];    //星期完整表示
        $this->yDay         =   $dateArray["yday"];       //一年中的天数 0－365
        $this->cMonth       =   $dateArray["month"];      //月份的完整表示

        $this->CDATE        =   $this->format("Y-m-d"); //日期表示
        $this->YMD          =   $this->format("Ymd");  //简单日期
        $this->CTIME        =   $this->format("H:i:s"); //时间表示

        return $this;
    }

    /**
     * 日期格式化
     * 默认返回 1970-01-01 11:30:45 格式
     *
     * @param string $format  格式化参数
     * @return string
     */
    public function format($format = "Y-m-d H:i:s")
    {
        return date($format, $this->date);
    }

    /**
     * 获取日期开始和结束的时间戳
     *
     * @param integer $timeStamp 时间戳，默认当天
     * @return array
     */
    public function getDayTime($timeStamp = '')
    {
        $date = $timeStamp ? $timeStamp : $this->date;
        list($y, $m, $d) = explode('-', date('Y-m-d', $date));
        return [
            mktime(0, 0, 0, $m, $d, $y),
            mktime(23, 59, 59, $m, $d, $y)
        ];
    }

    /**
     * 获取周开始和结束的时间戳
     *
     * @param integer $timeStamp 默认当周
     * @return array
     */
    public function getWeekTime($timeStamp = '')
    {
        $date = $timeStamp ? $timeStamp : $this->date;
        list($y, $m, $d, $w) = explode('-', date('Y-m-d-w', $date));
        // 修正周日的问题
        if ($w == 0) {
            $w = 7;
        }
        return [
            mktime(0, 0, 0, $m, $d - $w + 1, $y),
            mktime(23, 59, 59, $m, $d - $w + 7, $y)
        ];
    }

    /**
     * 获取月开始和结束的时间戳
     *
     * @param integer $timeStamp 默认当月
     * @return array
     */
    public function getMonthTime($timeStamp = '')
    {
        $date = $timeStamp ? $timeStamp : $this->date;
        list($y, $m, $t) = explode('-', date('Y-m-t', $date));
        return [
            mktime(0, 0, 0, $m, 1, $y),
            mktime(23, 59, 59, $m, $t, $y)
        ];
    }

    /**
     * 获取年开始和结束的时间戳
     *
     * @param integer $timeStamp 默认当年
     * @return array
     */
    public function getYearTime($timeStamp = '')
    {
        $date = $timeStamp ? $timeStamp : $this->date;
        $y = date('Y', $date);
        return [
            mktime(0, 0, 0, 1, 1, $y),
            mktime(23, 59, 59, 12, 31, $y)
        ];
    }

    /**
     * 根据指定日期和1~7来获取周一至周日对应的日期
     *
     * @param string $date 指定日期，为空则默认为当前天
     * @param int $weekday 指定返回周几的日期（1~7），默认为返回周一对应的日期
     * @param string $format 指定返回日期的格式
     * @return  string
     */
    public function getWeekDay($weekday = 1, $date = '', $format = 'Y-m-d')
    {
        $time = strtotime($date);
        $time = ($time == '') ? $this->date : $time;

        return date($format, $time - 86400 * (date('N', $time) - $weekday));
    }

    /**
     * 是否为闰年
     *
     * @return string
     */
    public function isLeapYear($year = '')
    {
        if (empty($year)) {
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
     * @return integer
     */
    public function dateDiff($date, $elaps = "d")
    {
        $__DAYS_PER_WEEK__       = (7);
        $__DAYS_PER_MONTH__      = (30);
        $__DAYS_PER_YEAR__       = (365);
        $__HOURS_IN_A_DAY__      = (24);
        $__MINUTES_IN_A_DAY__    = (1440);
        $__SECONDS_IN_A_DAY__    = (86400);
        //计算天数差
        $__DAYSELAPS = ($this->parse($date) - $this->date) / $__SECONDS_IN_A_DAY__;
        switch ($elaps) {
            case "y": //转换成年
                $__DAYSELAPS =  $__DAYSELAPS / $__DAYS_PER_YEAR__;
                break;
            case "m": //转换成月
                $__DAYSELAPS =  $__DAYSELAPS / $__DAYS_PER_MONTH__;
                break;
            case "w": //转换成星期
                $__DAYSELAPS =  $__DAYSELAPS / $__DAYS_PER_WEEK__;
                break;
            case "h": //转换成小时
                $__DAYSELAPS =  $__DAYSELAPS * $__HOURS_IN_A_DAY__;
                break;
            case "i": //转换成分钟
                $__DAYSELAPS =  $__DAYSELAPS * $__MINUTES_IN_A_DAY__;
                break;
            case "s": //转换成秒
                $__DAYSELAPS =  $__DAYSELAPS * $__SECONDS_IN_A_DAY__;
                break;
        }

        return $__DAYSELAPS;
    }

    /**
     * 人性化的计算日期差
     *
     * @param mixed $time 要比较的时间
     * @param mixed $precision 返回的精度
     * @return string
     */
    public function timeDiff($time, $precision = false)
    {
        if (!is_numeric($precision) && !is_bool($precision)) {
            static $_diff = array('y' => '年', 'M' => '个月', 'd' => '天', 'w' => '周', 's' => '秒', 'h' => '小时', 'm' => '分钟');
            return ceil($this->dateDiff($time, $precision)) . $_diff[$precision] . '前';
        }
        $diff = abs($this->parse($time) - $this->date);
        static $chunks = array(array(31536000, '年'), array(2592000, '个月'), array(604800, '周'), array(86400, '天'), array(3600, '小时'), array(60, '分钟'), array(1, '秒'));
        $count = 0;
        $since = '';
        for ($i = 0; $i < count($chunks); $i++) {
            if ($diff >= $chunks[$i][0]) {
                $num   =  floor($diff / $chunks[$i][0]);
                $since .= sprintf('%d' . $chunks[$i][1], $num);
                $diff =  (int) ($diff - $chunks[$i][0] * $num);
                $count++;
                if (!$precision || $count >= $precision) {
                    break;
                }
            }
        }

        return $since . '前';
    }

    /**
     * 返回周的某一天 返回Date对象
     *
     * @return Date
     */
    public function getDayOfWeek($n)
    {
        $week = array(0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday');
        return (new self($week[$n]));
    }

    /**
     * 计算周的第一天 返回Date对象
     *
     * @return Date
     */
    public function firstDayOfWeek()
    {
        return $this->getDayOfWeek(1);
    }

    /**
     * 计算月份的第一天 返回Date对象
     *
     * @return Date
     */
    public function firstDayOfMonth()
    {
        return (new self(mktime(0, 0, 0, $this->month, 1, $this->year)));
    }

    /**
     * 计算年份的第一天 返回Date对象
     *
     * @return Date
     */
    public function firstDayOfYear()
    {
        return (new self(mktime(0, 0, 0, 1, 1, $this->year)));
    }

    /**
     * 计算周的最后一天 返回Date对象
     *
     * @return Date
     */
    public function lastDayOfWeek()
    {
        return $this->getDayOfWeek(0);
    }

    /**
     * 计算月份的最后一天 返回Date对象
     *
     * @return Date
     */
    public function lastDayOfMonth()
    {
        return (new self(mktime(0, 0, 0, $this->month + 1, 0, $this->year)));
    }

    /**
     * 计算年份的最后一天 返回Date对象
     *
     * @return Date
     */
    public function lastDayOfYear()
    {
        return (new self(mktime(0, 0, 0, 1, 0, $this->year + 1)));
    }

    /**
     * 计算月份的最大天数
     *
     * @return integer
     */
    public function maxDayOfMonth()
    {
        return $this->dateDiff(strtotime($this->dateAdd(1, 'm')), 'd');
    }

    /**
     * 取得指定间隔日期
     *
     *    yyyy - 年
     *    q    - 季度
     *    m    - 月
     *    y    - day of year
     *    d    - 日
     *    w    - 周
     *    ww   - week of year
     *    h    - 小时
     *    n    - 分钟
     *    s    - 秒
     *
     * @param integer $number 间隔数目
     * @param string $interval  比较类型
     * @return Date
     */
    public function dateAdd($number = 0, $interval = "d")
    {
        $hours =  $this->hour;
        $minutes =  $this->minute;
        $seconds =  $this->second;
        $month =  $this->month;
        $day =  $this->day;
        $year =  $this->year;

        switch ($interval) {
            case "yyyy":
                //---Add $number to year
                $year += $number;
                break;

            case "q":
                //---Add $number to quarter
                $month += ($number * 3);
                break;

            case "m":
                //---Add $number to month
                $month += $number;
                break;

            case "y":
            case "d":
            case "w":
                //---Add $number to day of year, day, day of week
                $day += $number;
                break;

            case "ww":
                //---Add $number to week
                $day += ($number * 7);
                break;

            case "h":
                //---Add $number to hours
                $hours += $number;
                break;

            case "n":
                //---Add $number to minutes
                $minutes += $number;
                break;

            case "s":
                //---Add $number to seconds
                $seconds += $number;
                break;
        }

        return (new self(mktime($hours, $minutes, $seconds, $month, $day, $year)));
    }

    /**
     * 日期数字转中文,用于日和月、周
     *
     * @param integer $number 日期数字
     * @return string
     */
    public function numberToCh($number)
    {
        $number = intval($number);
        $array  = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十');
        $str = '';
        if ($number  == 0) {
            $str .= "十";
        }
        if ($number  <  10) {
            $str .= $array[$number - 1];
        } elseif ($number  <  20) {
            $str .= "十" . $array[$number - 11];
        } elseif ($number  <  30) {
            $str .= "二十" . $array[$number - 21];
        } else {
            $str .= "三十" . $array[$number - 31];
        }

        return $str;
    }

    /**
     * 年份数字转中文
     *
     * @param integer $yearStr 年份数字
     * @param boolean $flag 是否显示公元
     * @return string
     */
    public function yearToCh($yearStr, $flag = false)
    {
        $array = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $str = $flag ? '公元' : '';
        for ($i = 0; $i < 4; $i++) {
            $str .= $array[mb_substr($yearStr, $i, 1)];
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
    public function magicInfo($type)
    {
        $result = '';
        $m = $this->month;
        $y = $this->year;
        $d = $this->day;

        switch ($type) {
            case 'XZ': //星座
                $XZDict = array('摩羯', '宝瓶', '双鱼', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手');
                $Zone   = array(1222, 122, 222, 321, 421, 522, 622, 722, 822, 922, 1022, 1122, 1222);
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

            case 'GZ': //干支
                $GZDict = array(
                    array('甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'),
                    array('子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥')
                );
                $i = $y - 1900 + 36;
                $result = $GZDict[0][$i % 10] . $GZDict[1][$i % 12];
                break;

            case 'SX': //生肖
                $SXDict = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
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
