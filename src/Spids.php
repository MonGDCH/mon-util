<?php

declare(strict_types=1);

namespace mon\util;

use InvalidArgumentException;

/**
 * 生成简短唯一ID类库(用户ID转换加解密)
 * 由于 Spids\Spids 库没有PHP7的版本，故自行移植改库支持PHP7
 * 
 * @author Mon <985558837@qq.com>
 * @see https://github.com/sqids/sqids-php
 * @version 0.4.1
 */
class Spids
{
    use Instance;

    /**
     * 加密字符表
     *
     * @var string
     */
    protected $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * 生成字符串最小长度
     *
     * @var integer
     */
    protected $minLength = 0;

    /**
     * 不可用的编码块列表
     *
     * @var array
     */
    protected $blocklist = [];

    /**
     * 构造方法
     *
     * @param string $alphabet  加密字符表
     * @param integer $minLength    生成字符串最小长度
     * @param array $blocklist  不可用的编码块列表
     */
    public function __construct(string $alphabet = '', int $minLength = 0, array $blocklist = [])
    {
        // 字符表
        if ($alphabet != '') {
            if (mb_strlen($alphabet) != strlen($alphabet)) {
                throw new InvalidArgumentException('字母表不能包含多字节字符');
            }
            if (strlen($alphabet) < 3) {
                throw new InvalidArgumentException('字母表长度必须至少为3');
            }
            if (count(array_unique(str_split($alphabet))) !== strlen($alphabet)) {
                throw new InvalidArgumentException('字母表必须包含唯一字符');
            }

            $this->alphabet = $alphabet;
        }

        // 生成字符串长度
        if ($minLength > 0) {
            $minLengthLimit = 255;
            if (!is_int($minLength) || $minLength < 0 || $minLength > $minLengthLimit) {
                throw new InvalidArgumentException('最小长度必须介于0和' . $minLengthLimit . '之间');
            }

            $this->minLength = $minLength;
        }

        $blocklist = empty($blocklist) ? require_once(__DIR__ . '/data/spids.php') : $blocklist;
        $filteredBlocklist = [];
        $alphabetChars = str_split(strtolower($this->alphabet));
        foreach ((array) $blocklist as $word) {
            $word = strval($word);
            if (strlen($word) >= 3) {
                $wordLowercased = strtolower($word);
                $wordChars = str_split($wordLowercased);
                // $intersection = array_filter($wordChars, fn ($c) => in_array($c, $alphabetChars));
                $intersection = array_filter($wordChars, function ($c) use ($alphabetChars) {
                    return in_array($c, $alphabetChars);
                });
                if (count($intersection) == count($wordChars)) {
                    $filteredBlocklist[] = strtolower($wordLowercased);
                }
            }
        }
        $this->blocklist = $filteredBlocklist;
    }

    /**
     * 编码，将正整数的id列表转为字符串
     *
     * @param array $numbers ID列表
     * @return string
     */
    public function encode(array $numbers): string
    {
        if (count($numbers) == 0) {
            return '';
        }

        $inRangeNumbers = array_filter($numbers, function ($n) {
            return $n >= 0 && $n <= PHP_INT_MAX;
        });
        if (count($inRangeNumbers) != count($numbers)) {
            throw new InvalidArgumentException('编码值只支持0至' . PHP_INT_MAX);
        }

        return $this->encodeNumbers($numbers);
    }

    /**
     * 解码，将字符串转为正整数的id列表
     *
     * @param string $id
     * @return array
     */
    public function decode(string $id): array
    {
        $ret = [];
        if ($id == '') {
            return $ret;
        }

        $alphabetChars = str_split($this->alphabet);
        foreach (str_split($id) as $c) {
            if (!in_array($c, $alphabetChars)) {
                return $ret;
            }
        }

        $prefix = $id[0];
        $offset = strpos($this->alphabet, $prefix);
        $alphabet = substr($this->alphabet, $offset) . substr($this->alphabet, 0, $offset);
        $alphabet = strrev($alphabet);
        $id = substr($id, 1);

        while (strlen($id) > 0) {
            $separator = $alphabet[0];
            $chunks = explode($separator, $id, 2);
            if (!empty($chunks)) {
                if ($chunks[0] == '') {
                    return $ret;
                }

                $ret[] = $this->toNumber($chunks[0], substr($alphabet, 1));
                if (count($chunks) > 1) {
                    $alphabet = $this->shuffle($alphabet);
                }
            }

            $id = $chunks[1] ?? '';
        }

        return $ret;
    }

    /**
     * 生成编码
     *
     * @param array $numbers    ID列表
     * @param integer $increment ID索引
     * @return string
     */
    protected function encodeNumbers(array $numbers, int $increment = 0): string
    {
        if ($increment > strlen($this->alphabet)) {
            throw new InvalidArgumentException('已达到重新生成ID的最大尝试次数');
        }

        $offset = count($numbers);
        foreach ($numbers as $i => $v) {
            $offset += ord($this->alphabet[$v % strlen($this->alphabet)]) + $i;
        }
        $offset %= strlen($this->alphabet);
        $offset = ($offset + $increment) % strlen($this->alphabet);

        $alphabet = substr($this->alphabet, $offset) . substr($this->alphabet, 0, $offset);
        $prefix = $alphabet[0];
        $alphabet = strrev($alphabet);
        $ret = [$prefix];

        for ($i = 0; $i != count($numbers); $i++) {
            $num = $numbers[$i];

            $ret[] = $this->toId($num, substr($alphabet, 1));
            if ($i < count($numbers) - 1) {
                $ret[] = $alphabet[0];
                $alphabet = $this->shuffle($alphabet);
            }
        }

        $id = implode('', $ret);
        if ($this->minLength > strlen($id)) {
            $id .= $alphabet[0];

            while ($this->minLength - strlen($id) > 0) {
                $alphabet = $this->shuffle($alphabet);
                $id .= substr($alphabet, 0, min($this->minLength - strlen($id), strlen($alphabet)));
            }
        }

        if ($this->isBlockedId($id)) {
            $id = $this->encodeNumbers($numbers, $increment + 1);
        }

        return $id;
    }

    /**
     * ID 转 字符串
     *
     * @param integer $num
     * @param string $alphabet
     * @return string
     */
    protected function toId(int $num, string $alphabet): string
    {
        $id = [];
        $chars = str_split($alphabet);
        $result = $num;
        do {
            array_unshift($id, $chars[intval(bcmod((string)$result, (string)count($chars)))]);
            $result = bcdiv((string)$result, (string)count($chars), 0);
        } while (
            bccomp($result, '0', 0) > 0
        );

        return implode('', $id);
    }

    /**
     * 字符串 转 ID
     *
     * @param string $id
     * @param string $alphabet
     * @return integer
     */
    protected function toNumber(string $id, string $alphabet): int
    {
        $chars = str_split($alphabet);
        return intval(array_reduce(str_split($id), function ($a, $v) use ($chars) {
            $number = bcmul((string)$a, (string)count($chars), 0);
            $number = bcadd($number, (string)array_search($v, $chars), 0);

            return $number;
        }, 0));
    }

    /**
     * 打乱字符表
     *
     * @param string $alphabet
     * @return string
     */
    protected function shuffle(string $alphabet): string
    {
        $chars = str_split($alphabet);
        for ($i = 0, $j = count($chars) - 1; $j > 0; $i++, $j--) {
            $r = ($i * $j + ord($chars[$i]) + ord($chars[$j])) % count($chars);
            [$chars[$i], $chars[$r]] = [$chars[$r], $chars[$i]];
        }

        return implode('', $chars);
    }

    /**
     * 是否为禁用的编码块
     *
     * @param string $id
     * @return boolean
     */
    protected function isBlockedId(string $id): bool
    {
        $id = strtolower($id);
        foreach ($this->blocklist as $word) {
            if (strlen((string) $word) <= strlen($id)) {
                if (strlen($id) <= 3 || strlen((string) $word) <= 3) {
                    if ($id == $word) {
                        return true;
                    }
                } elseif (preg_match('/~[0-9]+~/', (string) $word)) {
                    if (str_starts_with($id, (string) $word) || strrpos($id, (string) $word) === strlen($id) - strlen((string) $word)) {
                        return true;
                    }
                } elseif ($this->str_contains($id, (string) $word)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 确定字符串是否包含指定子串
     * PHP7版本的 str_contains 方法
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    protected function str_contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
