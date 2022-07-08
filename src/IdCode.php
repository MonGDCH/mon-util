<?php

namespace mon\util;

/**
 * id转换为code字符串
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1 优化代码 2022-07-08
 */
class IdCode
{
    use Instance;

    /**
     * 起始数
     *
     * @var integer
     */
    private $initNum = 4015732869;

    /**
     * 进制的基本字符串
     *
     * @var string
     */
    private $baseChar = '8745692031ZYXWVTSRQPNMKJHGFEDCBA';

    /**
     * 使用32进制运算
     *
     * @var integer
     */
    private $type = 32;

    /**
     * 补足第3位
     *
     * @var string
     */
    private $three = 'K';

    /**
     * 补足第4位
     *
     * @var string
     */
    private $four = 'X';

    /**
     * 初始化
     *
     * @param integer $initNum  起始数
     * @param string $baseChar  进制的基本字符串
     * @return IdCode
     */
    public function init($initNum = 4015732869, $baseChar = '8745692031ZYXWVTSRQPNMKJHGFEDCBA', $three = 'K', $four = 'X')
    {
        $this->initNum = $initNum;
        $this->baseChar = $baseChar;
        $this->three = $three;
        $this->four = $four;
        return $this;
    }

    /**
     * ID转code(10位内id 返回7位字母数字)
     *
     * @see 10进制数转换成32进制数
     * @param integer $id   id值
     * @return string
     */
    public function id2code($id)
    {
        //数组 增加备用数值
        $id += $this->initNum;
        //左补0 补齐10位
        $str = str_pad($id, 10, '0', STR_PAD_LEFT);
        //按位 拆分 4 6位（32进制 4 6位划分）
        $num1 = intval($str[0] . $str[2] . $str[6] . $str[9]);
        $num2 = intval($str[1] . $str[3] . $str[4] . $str[5] . $str[7] . $str[8]);
        $str1 = $str2 = '';
        $str1 = $this->id2String($num1);
        $str1 = strrev($str1);
        $str2 = $this->id2String($num2);
        $str2 = strrev($str2);
        // 补足第3、4位
        return str_pad($str1, 3, $this->three, STR_PAD_RIGHT) . str_pad($str2, 4, $this->four, STR_PAD_RIGHT);
    }

    /**
     * code转ID
     *
     * @see 三十二进制数转换成十机制数
     * @param integer $id   id值
     * @return integer
     */
    public function code2id($code)
    {
        // 清除3、4位补足位
        $str1 = trim(mb_substr($code, 0, 3), $this->three);
        $str2 = trim(mb_substr($code, 3, 4), $this->four);
        // 转换数值
        $num1 = $this->string2Id($str1);
        $num2 = $this->string2Id($str2);
        // 补位拼接
        $str1 = str_pad($num1, 4, '0', STR_PAD_LEFT);
        $str2 = str_pad($num2, 6, '0', STR_PAD_LEFT);
        $id = ltrim($str1[0] . $str2[0] . $str1[1] . $str2[1] . $str2[2] . $str2[3] . $str1[2] . $str2[4] . $str2[5] . $str1[3], '0');
        if ($id === '') {
            return $id;
        }
        // 减去初始数值
        $id -= $this->initNum;
        return $id;
    }

    /**
     * 数字进行进制转换
     * 
     * @param integer $num  数值
     * @return string
     */
    protected function id2String($num)
    {
        $str = '';
        while ($num != 0) {
            $tmp = $num % $this->type;
            $str .= $this->baseChar[$tmp];
            $num = intval($num / $this->type);
        }

        return $str;
    }

    /**
     * 字符串转数字
     *
     * @param string $str
     * @return float|int|string
     */
    protected function string2Id($str)
    {
        //转换为数组
        $charArr = array_flip((array)str_split($this->baseChar));
        $num = 0;
        for ($i = 0, $l = mb_strlen($str); $i <= $l - 1; $i++) {
            $linshi = mb_substr($str, $i, 1);
            if (!isset($charArr[$linshi])) {
                return '';
            }
            $num += $charArr[$linshi] * pow($this->type, mb_strlen($str) - $i - 1);
        }

        return $num;
    }
}
