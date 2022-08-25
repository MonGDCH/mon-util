<?php

namespace mon\util;

use RuntimeException;

/**
 * 中文转拼音
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1   优化业务    2022-08-25
 */
class Pinyin
{
    use Instance;

    /**
     * 数据源文件路径
     *
     * @var string
     */
    protected $dataFile = __DIR__ . '/data/pinyins.php';

    /**
     * 拼音库
     * 
     * @var array
     */
    protected $pinyins = null;

    /**
     * 设置拼音库
     *
     * @param array $pinyins
     * @return Pinyin
     */
    public function setData(array $pinyins)
    {
        $this->pinyins = $pinyins;
        return $this;
    }

    /**
     * 设置数据源文件路径，拼音库未设置时有效
     *
     * @param string $file
     * @return Pinyin
     */
    public function setDataFile($file)
    {
        $this->dataFile = $file;
        return $this;
    }

    /**
     * 中文转拼音
     * 
     * @param string $str         utf8字符串
     * @param string $type        返回格式 [all:全拼音|first:首字母|one:仅第一字符首字母]
     * @param string $placeholder 无法识别的字符占位符
     * @param string $allow_chars 允许的非中文字符
     * @return string             拼音字符串
     */
    public function format($str, $type = 'all', $placeholder = '_', $allow_chars = '/[a-zA-Z\d .]/')
    {
        if (is_null($this->pinyins)) {
            $this->pinyins = include($this->dataFile);
            if (empty($this->pinyins) || !is_array($this->pinyins)) {
                throw new RuntimeException('Failed to get extended pinyins information data!');
            }
        }
        $str = trim($str);
        $len = mb_strlen($str, 'UTF-8');
        $rs = '';
        for ($i = 0; $i < $len; $i++) {
            $chr = mb_substr($str, $i, 1, 'UTF-8');
            $asc = ord($chr);
            // 0-127
            if ($asc < 0x80) {
                // 用参数控制正则
                if (preg_match($allow_chars, $chr)) {
                    // 0-9 a-z A-Z 空格
                    $rs .= $chr;
                } else {
                    // 其他字符用填充符代替
                    $rs .= $placeholder;
                }
            } else {
                // 128-255
                if (isset($this->pinyins[$chr])) {
                    $rs .= 'first' === $type ? $this->pinyins[$chr][0] . ' ' : ($this->pinyins[$chr] . ' ');
                } else {
                    $rs .= $placeholder;
                }
            }

            if ('one' === $type && '' !== $rs) {
                return $rs[0];
            }
        }

        return rtrim($rs, ' ');
    }
}
