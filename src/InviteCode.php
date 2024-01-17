<?php

declare(strict_types=1);

namespace mon\util;

/**
 * 用户ID生成唯一邀请码
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1 优化代码 2023-07-05
 */
class InviteCode
{
    use Instance;

    /**
     * 转义key值，注意不能有0
     *
     * @var string
     */
    protected $key = 'TUV23ABC654DEFGHJK879LMNPQRSWXYZ';

    /**
     * 进制数
     *
     * @var integer
     */
    protected $num = 32;

    /**
     * 用户ID混淆
     *
     * @var integer
     */
    protected $mixins = 3060;

    /**
     * 重新初始化
     *
     * @param string $key   转义key值
     * @param integer $mixins   用户ID混淆
     * @return InviteCode
     */
    public function init(string $key, int $mixins = 3060): InviteCode
    {
        $this->key = $key;
        $this->mixins = $mixins;
        $this->num = strlen($key);
        return $this;
    }

    /**
     * 生成邀请码
     *
     * @param integer $uid  用户ID
     * @return string
     */
    public function encode(int $uid): string
    {
        $uid = $uid + $this->mixins;
        $code = '';
        // 转进制
        while ($uid > 0) {
            // 求模
            $mod = $uid % $this->num;
            $uid = ($uid - $mod) / $this->num;
            $code = $this->key[$mod] . $code;
        }

        // 不足用0补充
        $code = str_pad($code, 4, '0', \STR_PAD_LEFT);
        return $code;
    }

    /**
     * 邀请码获取用户id
     *
     * @param string $code  邀请码
     * @return integer
     */
    public function decode(string $code): int
    {
        if (strrpos($code, '0') !== false) {
            $code = substr($code, strrpos($code, '0') + 1);
        }
        $len = strlen($code);
        $code = strrev($code);
        $uid = 0;
        for ($i = 0; $i < $len; $i++) {
            $uid += strpos($this->key, $code[$i]) * pow($this->num, $i);
        }

        return $uid > 0 ? ($uid - $this->mixins) : 0;
    }
}
