<?php

declare(strict_types=1);

namespace mon\util;

use mon\util\exception\IPLocationException;

/**
 * 基于纯真IP库的ip地址定位，解析GBK数据输出UTF8编码地址
 * 
 * @see https://cz88.net/ 可通过纯真IP库官网下载最新版IP库文件
 * @author Mon <985558837@qq.com>
 * @version 1.0.1 优化注解 2022-07-08
 */
class IPLocation
{
    use Instance;

    /**
     * qqwry.dat 文件指针
     *
     * @var resource
     */
    private $fp;

    /**
     * 第一条IP记录的偏移地址
     *
     * @var integer
     */
    private $firstip;

    /**
     * 最后一条IP记录的偏移地址
     *
     * @var integer
     */
    private $lastip;

    /**
     * IP记录的总条数（不包含版本信息记录）
     *
     * @var integer
     */
    private $totalip;

    /**
     * 运营商
     *
     * @var array
     */
    private $dict_isp = ['联通', '移动', '铁通', '电信', '长城', '鹏博士'];

    /**
     * 直辖市
     *
     * @var array
     */
    private $dict_city_directly = ['北京', '天津', '重庆', '上海'];

    /**
     * 省份
     *
     * @var array
     */
    private $dict_province = [
        '北京', '天津', '重庆', '上海', '河北', '山西', '辽宁', '吉林', '黑龙江', '江苏', '浙江', '安徽', '福建', '江西', '山东', '河南', '湖北', '湖南',
        '广东', '海南', '四川', '贵州', '云南', '陕西', '甘肃', '青海', '台湾', '内蒙古', '广西', '宁夏', '新疆', '西藏', '香港', '澳门'
    ];

    /**
     * 初始化标志
     *
     * @var boolean
     */
    private $init = false;

    /**
     * 构造方法
     *
     * @param string $db IP库文件路径
     */
    public function __construct(?string $db = '')
    {
        if (!empty($db)) {
            $this->init($db);
        }
    }

    /**
     * 析构函数，关闭打开的IP库文件
     */
    public function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
        $this->fp = 0;
    }

    /**
     * 初始化
     *
     * @param string $db IP库文件路径
     * @return IPLocation
     */
    public function init(string $db): IPLocation
    {
        if (!file_exists($db)) {
            throw new IPLocationException('IP数据文件未找到', IPLocationException::ERROR_DATA_NOT_FOUND);
        }

        $this->fp = 0;
        if (($this->fp = fopen($db, 'rb')) !== false) {
            $this->firstip = $this->getlong();
            $this->lastip  = $this->getlong();
            $this->totalip = ($this->lastip - $this->firstip) / 7;
        }

        $this->init = true;
        return $this;
    }

    /**
     * 获取IP所在位置信息
     * 注：province city county isp 对中国以外的ip无法识别
     * 
     * <code>
     * $result 是返回的数组
     * $result['ip']            输入的ip
     * $result['country']       国家 如 中国
     * $result['province']      省份信息 如 河北省
     * $result['city']          市区 如 邢台市
     * $result['county']        郡县 如 威县
     * $result['isp']           运营商 如 联通
     * $result['area']          最完整的信息 如 中国河北省邢台市威县新科网吧(北外街)
     * </code>
     *
     * @param string $ip IP地址
     * @throws IPLocationException
     * @return array
     */
    public function getLocation(string $ip): array
    {
        if (!$this->init) {
            throw new IPLocationException('未初始化实例', IPLocationException::ERROR_NOT_INIT);
        }
        $result = [];
        $location = [];
        $is_china = false;
        $seperator_sheng = '省';
        $seperator_shi = '市';
        $seperator_xian = '县';
        $seperator_qu = '区';

        if (!$this->isValidIpV4($ip)) {
            // 验证IP格式
            throw new IPLocationException('无效的IPv4地址', IPLocationException::ERROR_IPV4_FAILD);
        } else {
            // 获取所在地区信息
            $location = $this->getLocationFromIP($ip);
            if (!$location) {
                throw new IPLocationException('读取IP数据文件失败', IPLocationException::ERROR_DATA_READ_FAILD);
            }

            // 原国家数据（例：北京市朝阳区）
            $location['org_country'] = $location['country'];
            // 原地区数据（例：// 金桥国际小区）
            $location['org_area'] = $location['area'];
            // 定义省市区参数
            $location['province'] = '';
            $location['city'] = '';
            $location['county'] = '';
            // 获取省市区信息
            $_tmp_province = explode($seperator_sheng, $location['country']);
            // 存在 省 标志 xxx省yyyy 中的yyyy
            if (isset($_tmp_province[1])) {
                $is_china = true;
                $location['province'] = $_tmp_province[0];
                // 存在市
                if (mb_strpos($_tmp_province[1], $seperator_shi) !== false) {
                    $_tmp_city = explode($seperator_shi, $_tmp_province[1]);
                    $location['city'] = $_tmp_city[0] . $seperator_shi;
                    // 存在县
                    if (isset($_tmp_city[1])) {
                        if (mb_strpos($_tmp_city[1], $seperator_xian) !== false) {
                            $_tmp_county = explode($seperator_xian, $_tmp_city[1]);
                            $location['county'] = $_tmp_county[0] . $seperator_xian;
                        }
                        // 存在区
                        if (!$location['county'] && mb_strpos($_tmp_city[1], $seperator_qu) !== false) {
                            $_tmp_qu = explode($seperator_qu, $_tmp_city[1]);
                            $location['county'] = $_tmp_qu[0] . $seperator_qu;
                        }
                    }
                }
            } else {
                // 处理如内蒙古等不带省份类型的和直辖市
                foreach ($this->dict_province as $value) {
                    if (false !== mb_strpos($location['country'], $value)) {
                        $is_china = true;
                        // 存在直辖市
                        if (in_array($value, $this->dict_city_directly)) {
                            $_tmp_province = explode($seperator_shi, $location['country']);
                            // 上海市浦江区xxx
                            if ($_tmp_province[0] == $value) {
                                $location['province'] = $_tmp_province[0];
                                // 市辖区
                                if (isset($_tmp_province[1])) {
                                    if (mb_strpos($_tmp_province[1], $seperator_qu) !== false) {
                                        $_tmp_qu = explode($seperator_qu, $_tmp_province[1]);
                                        $location['city'] = $_tmp_qu[0] . $seperator_qu;
                                    }
                                }
                            } else {
                                // 上海交通大学
                                $location['province'] = $value;
                                $location['org_area'] = $location['org_country'] . $location['org_area'];
                            }
                        } else {
                            // 省
                            $location['province'] = $value;
                            //没有省份标志 只能替换
                            $_tmp_city = str_replace($location['province'], '', $location['country']);
                            //防止直辖市捣乱 上海市xxx区 =》 市xx区
                            $_tmp_shi_pos = mb_stripos($_tmp_city, $seperator_shi, 0, 'UTF-8');
                            if ($_tmp_shi_pos === 0) {
                                $_tmp_city = mb_substr($_tmp_city, 1, null, 'UTF-8');
                            }

                            // 内蒙古类型的获取市县信息
                            if (mb_strpos($_tmp_city, $seperator_shi) !== false) {
                                // 市
                                $_tmp_city = explode($seperator_shi, $_tmp_city);
                                $location['city'] = $_tmp_city[0] . $seperator_shi;
                                // 县
                                if (isset($_tmp_city[1])) {
                                    if (mb_strpos($_tmp_city[1], $seperator_xian) !== false) {
                                        $_tmp_county = explode($seperator_xian, $_tmp_city[1]);
                                        $location['county'] = $_tmp_county[0] . $seperator_xian;
                                    }
                                    // 区
                                    if (!$location['county'] && mb_strpos($_tmp_city[1], $seperator_qu) !== false) {
                                        $_tmp_qu = explode($seperator_qu, $_tmp_city[1]);
                                        $location['county'] = $_tmp_qu[0] . $seperator_qu;
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
            }
            // 标志国家为中国
            if ($is_china) {
                $location['country'] = '中国';
            }
            // 获取运营商信息
            $location['isp'] = $this->getIsp($location['area']);

            // $result['beginip'] = $location['beginip'];
            // $result['endip'] = $location['endip'];
            // $result['org_country'] = $location['org_country'];
            // $result['org_area'] = $location['org_area'];

            // 返回数据
            $result['ip'] = $location['ip'];
            $result['country'] = $location['country'];
            $result['province'] = $location['province'];
            $result['city'] = $location['city'];
            $result['county'] = $location['county'];
            $result['isp'] = $location['isp'];
            $result['area'] = $location['country'] . $location['province'] . $location['city'] . $location['county'] . $location['org_area'];
        }
        return $result;
    }

    /**
     * 根据所给IP地址或域名获取所在地区信息
     *
     * @param string $ip IP地址
     * @return array
     */
    private function getLocationFromIP(string $ip): array
    {
        // 如果数据文件没有被正确打开，则直接返回空
        if (!$this->fp) {
            throw new IPLocationException('数据文件未初始化', IPLocationException::ERROR_NOT_INIT);
        }
        $location['ip'] = $ip;
        // 将输入的IP地址转化为可比较的IP地址，不合法的IP地址会被转化为255.255.255.255
        $ip = $this->packip($location['ip']);
        // 搜索的下边界
        $l = 0;
        // 搜索的上边界
        $u = $this->totalip;
        // 如果没有找到就返回最后一条IP记录（qqwry.dat的版本信息）
        $findip = $this->lastip;
        // 当上边界小于下边界时，查找失败
        while ($l <= $u) {
            // 计算近似中间记录
            $i = floor(($l + $u) / 2);
            fseek($this->fp, $this->firstip + $i * 7);
            // 获取中间记录的开始IP地址
            $beginip = strrev(fread($this->fp, 4));
            // strrev函数在这里的作用是将little-endian的压缩IP地址转化为big-endian的格式，以便用于比较，后面相同。
            if ($ip < $beginip) {
                // 用户的IP小于中间记录的开始IP地址时，将搜索的上边界修改为中间记录减一
                $u = $i - 1;
            } else {
                fseek($this->fp, $this->getlong3());
                // 获取中间记录的结束IP地址
                $endip = strrev(fread($this->fp, 4));
                if ($ip > $endip) {
                    // 用户的IP大于中间记录的结束IP地址时，将搜索的下边界修改为中间记录加一
                    $l = $i + 1;
                } else {
                    // 用户的IP在中间记录的IP范围内时，则表示找到结果，退出循环
                    $findip = $this->firstip + $i * 7;
                    break;
                }
            }
        }
        // 获取查找到的IP地理位置信息
        fseek($this->fp, $findip);
        // 用户IP所在范围的开始地址
        $location['beginip'] = long2ip($this->getlong());
        $offset = $this->getlong3();
        fseek($this->fp, $offset);
        // 用户IP所在范围的结束地址
        $location['endip'] = long2ip($this->getlong());
        $byte = fread($this->fp, 1);
        switch (ord($byte)) {
            case 1:
                // 标志字节为1，表示国家和区域信息都被同时重定向
                $countryOffset = $this->getlong3();
                fseek($this->fp, $countryOffset);
                $byte = fread($this->fp, 1);
                switch (ord($byte)) {
                    case 2:
                        // 标志字节为2，表示国家信息被重定向
                        fseek($this->fp, $this->getlong3());
                        $location['country'] = $this->getString();
                        fseek($this->fp, $countryOffset + 4);
                        $location['area'] = $this->getArea();
                        break;
                    default:
                        // 否则，表示国家信息没有被重定向
                        $location['country'] = $this->getString($byte);
                        $location['area'] = $this->getArea();
                        break;
                }
                break;
            case 2:
                // 标志字节为2，表示国家信息被重定向
                fseek($this->fp, $this->getlong3());
                $location['country'] = $this->getString();
                fseek($this->fp, $offset + 8);
                $location['area'] = $this->getArea();
                break;
            default:
                // 否则，表示国家信息没有被重定向
                $location['country'] = $this->getString($byte);
                $location['area'] = $this->getArea();
                break;
        }
        // GBK转UTF8
        $location['country'] = iconv('GBK', 'UTF-8', $location['country']);
        $location['area'] = iconv('GBK', 'UTF-8', $location['area']);
        // CZ88.NET表示没有有效信息
        if ((mb_strpos($location['country'], 'CZ88.NET') !== false)) {
            $location['country'] = '未知';
        }
        if (mb_strpos($location['area'], 'CZ88.NET') !== false) {
            $location['area'] = '';
        }
        return $location;
    }

    /**
     * 返回读取的长整型数
     *
     * @return integer
     */
    private function getlong()
    {
        // 将读取的little-endian编码的4个字节转化为长整型数
        $result = unpack('Vlong', fread($this->fp, 4));

        return $result['long'];
    }

    /**
     * 返回读取的3个字节的长整型数
     *
     * @return integer
     */
    private function getlong3()
    {
        // 将读取的little-endian编码的3个字节转化为长整型数
        $result = unpack('Vlong', fread($this->fp, 3) . chr(0));

        return $result['long'];
    }

    /**
     * 验证IP地址是否为IPV4
     *
     * @param string $ip IP地址
     * @return boolean
     */
    private function isValidIpV4(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * 返回压缩后可进行比较的IP地址
     *
     * @param string $ip IP地址
     * @return string
     */
    private function packip(string $ip): string
    {
        // 将IP地址转化为长整型数，如果在PHP5中，IP地址错误，则返回False，
        // 这时intval将Flase转化为整数-1，之后压缩成big-endian编码的字符串
        return pack('N', intval(Common::instance()->mIp2long($ip)));
    }

    /**
     * 返回读取的字符串
     *
     * @param string $data
     * @return string
     */
    private function getString(string $data = ''): string
    {
        $char = fread($this->fp, 1);
        while (ord($char) > 0) {
            // 字符串按照C格式保存，以\0结束
            $data .= $char;
            // 将读取的字符连接到给定字符串之后
            $char = fread($this->fp, 1);
        }

        return $data;
    }

    /**
     * 获取地区信息
     *
     * @return string
     */
    private function getArea(): string
    {
        // 标志字节
        $byte = fread($this->fp, 1);
        switch (ord($byte)) {
            case 0:
                // 没有区域信息
                $area = '';
                break;
            case 1:
            case 2:
                // 标志字节为1或2，表示区域信息被重定向
                fseek($this->fp, $this->getlong3());
                $area = $this->getString();
                break;
            default:
                // 否则，表示区域信息没有被重定向
                $area = $this->getString($byte);
                break;
        }

        return $area;
    }

    /**
     * 获取运营商信息
     * 
     * @param string $str
     * @return string
     */
    private function getIsp(string $str): string
    {
        $ret = '';
        foreach ($this->dict_isp as $k => $v) {
            if (false !== mb_strpos($str, $v)) {
                $ret = $v;
                break;
            }
        }

        return $ret;
    }
}
