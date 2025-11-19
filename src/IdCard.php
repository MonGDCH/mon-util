<?php

declare(strict_types=1);

namespace mon\util;

use DateTime;
use RuntimeException;
use InvalidArgumentException;
use Throwable;

/**
 * 身份证号码工具类(支持15位和18位)
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1 增加获取省份城市信息
 */
class IdCard
{
    use Instance;

    /**
     * 省份城市地区扩展数据配置文件路径
     *
     * @var string
     */
    public $dataFile = __DIR__ . '/data/idcard.php';

    /**
     * 正则验证规则
     *
     * @var string
     */
    protected $regx = '/^([\d]{17}[xX\d]|[\d]{15})$/';

    /**
     * 省份信息
     *
     * @var array
     */
    protected $provinces = [
        11 => '北京',
        12 => '天津',
        13 => '河北',
        14 => '山西',
        15 => '内蒙古',
        21 => '辽宁',
        22 => '吉林',
        23 => '黑龙江',
        31 => '上海',
        32 => '江苏',
        33 => '浙江',
        34 => '安徽',
        35 => '福建',
        36 => '江西',
        37 => '山东',
        41 => '河南',
        42 => '湖北',
        43 => '湖南',
        44 => '广东',
        45 => '广西',
        46 => '海南',
        50 => '重庆',
        51 => '四川',
        52 => '贵州',
        53 => '云南',
        54 => '西藏',
        61 => '陕西',
        62 => '甘肃',
        63 => '青海',
        64 => '宁夏',
        65 => '新疆',
        71 => '台湾',
        81 => '香港',
        82 => '澳门',
        91 => '国外'
    ];

    /**
     * 详细的省份城市地区信息
     *
     * @var array
     */
    protected $location = null;

    /**
     * 校验身份证号码
     *
     * @param string $idcard 身份证号码
     * @return boolean
     */
    public function check(string $idcard): bool
    {
        $idcard = trim($idcard);
        // 校验位数
        if (!preg_match($this->regx, $idcard)) {
            return false;
        }
        // 校验省份码（将前两位转为整数）
        $provinceCode = intval(Common::mSubstr($idcard, 0, 2));
        if (!isset($this->provinces[$provinceCode])) {
            return false;
        }
        // 校验生日
        $birthday = $this->getBirthday($idcard);
        if ($birthday === '' || date('Y-m-d', strtotime($birthday)) !== $birthday) {
            return false;
        }
        // 验证校验码
        if (mb_strlen($idcard, 'UTF-8') == 18) {
            $base17 = Common::mSubstr($idcard, 0, 17);
            try {
                $expected = $this->getCode($base17);
            } catch (InvalidArgumentException $e) {
                return false;
            }
            $last = strtoupper(Common::mSubstr($idcard, 17, 1));
            if ($last !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取所属省份
     *
     * @param string $idcard 身份证号码
     * @return string
     */
    public function getProvinces(string $idcard): string
    {
        $code = Common::mSubstr($idcard, 0, 2);
        return isset($this->provinces[$code]) ? $this->provinces[$code] : '';
    }

    /**
     * 获取所属省份城市
     *
     * @param string $idcard 身份证号码
     * @throws RuntimeException
     * @return string
     */
    public function getCity(string $idcard): string
    {
        if (is_null($this->location)) {
            if (!is_readable($this->dataFile)) {
                throw new RuntimeException('location data file not found: ' . $this->dataFile);
            }
            $this->location = include($this->dataFile);
            if (empty($this->location) || !is_array($this->location)) {
                throw new RuntimeException('Failed to get extended location information data!');
            }
        }

        $code = Common::mSubstr($idcard, 0, 4) . '00';
        return isset($this->location[$code]) ? $this->location[$code] : '';
    }

    /**
     * 获取所属省份城市地区信息
     *
     * @param string $idcard 身份证号码
     * @throws RuntimeException
     * @return string
     */
    public function getLocation(string $idcard): string
    {
        if (is_null($this->location)) {
            if (!is_readable($this->dataFile)) {
                throw new RuntimeException('location data file not found: ' . $this->dataFile);
            }
            $this->location = include($this->dataFile);
            if (empty($this->location) || !is_array($this->location)) {
                throw new RuntimeException('Failed to get extended location information data!');
            }
        }

        $code = Common::mSubstr($idcard, 0, 6);
        return isset($this->location[$code]) ? $this->location[$code] : '';
    }

    /**
     * 获取生日
     *
     * @param string $idcard 身份证号
     * @return string
     */
    public function getBirthday(string $idcard): string
    {
        $idcard = trim($idcard);
        $len = mb_strlen($idcard, 'UTF-8');
        if ($len === 18) {
            return Common::mSubstr($idcard, 6, 4) . '-' . Common::mSubstr($idcard, 10, 2) . '-' . Common::mSubstr($idcard, 12, 2);
        }
        if ($len === 15) {
            return '19' . Common::mSubstr($idcard, 6, 2) . '-' . Common::mSubstr($idcard, 8, 2) . '-' . Common::mSubstr($idcard, 10, 2);
        }
        return '';
    }

    /**
     * 获取性别
     *
     * @param string $idcard 身份证号码
     * @return integer 1男 2女
     */
    public function getSex(string $idcard): int
    {
        // 转18位身份证号码
        $idcard = $this->fifteen2Eighteen($idcard);
        $num = intval(Common::mSubstr($idcard, 16, 1));
        return ($num % 2 === 0) ? 2 : 1;
    }

    /**
     * 获取年龄
     *
     * @param string $idcard 身份证号码
     * @return integer
     */
    public function getAge(string $idcard): int
    {
        $b = $this->getBirthday($idcard);
        if ($b === '') {
            return 0;
        }
        try {
            $born = new DateTime($b);
            $now = new DateTime('now');
            return (int)$now->diff($born)->y;
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * 15位转18位身份证号
     *
     * @param string $idcard 身份证号
     * @return string
     */
    public function fifteen2Eighteen(string $idcard): string
    {
        if (mb_strlen($idcard, 'UTF-8') !== 15) {
            return $idcard;
        }

        $code = '19';
        $idCardBase = Common::mSubstr($idcard, 0, 6) . $code . Common::mSubstr($idcard, 6, 9);
        return $idCardBase . $this->getCode($idCardBase);
    }

    /**
     * 获取校验码
     *
     * @param string $idCardBase 17位以上的身份号码
     * @throws InvalidArgumentException
     * @return string
     */
    protected function getCode(string $idCardBase): string
    {
        $length = 17;
        if (mb_strlen($idCardBase, 'UTF-8') < $length) {
            throw new InvalidArgumentException('idCardBase params faild');
        }
        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $verifyNumbers = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;
        for ($i = 0; $i < $length; $i++) {
            $sum += Common::mSubstr($idCardBase, $i, 1) * $factor[$i];
        }
        $index = $sum % 11;
        return $verifyNumbers[$index];
    }
}
