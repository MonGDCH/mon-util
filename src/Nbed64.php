<?php

declare(strict_types=1);

namespace mon\util;

/**
 * nbed64算法的php实现，由于算法作者未支持composer，这里在原基础上进行优化，并发布composer
 * 
 * Apache License 2.0 开源协议 && Apache License 2.0  open source agreement
 * @see original author Gitee: https://gitee.com/love915sss/php-nbed64-base64/
 * @see original author GitHub： https://github.com/love915sss/php-nbed64-base64/
 * @see original author Author Blog: https://blog.csdn.net/qq_16661383?type=blog
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Nbed64
{
    use Instance;

    /**
     * Base64对字符串加密的升级版，简称：字符串动态加密（ 本函数与 stringDecryptEx() 为一对 ）
     * 注意：采用位运算掩码进行动态加密，复杂的数据可能会出现解码失败，复杂数据请使用 stringDecrypt() 
     *
     * @param string $str   原数据。
     * @param string $key   密钥。理论上密钥的长度与逆向的难度成正比关系。
     * @param boolean $isUtf8   是否采用UTF-8编码格式。默认为：true。若设置为false，则使用UTF-16编码。
     * 注意：此处指的是加密前的编码，而非加密后的base64编码，base64是无须编码的。换句话来说，本参数指的是解密后的字符串编码。
     * JS的默认编码为UTF-16，但UTF-16并不友好，很多编程语言和服务端环境都不支持UTF-16。
     * @param integer $maskNumber   掩码的数量。缺省为：32，范围：32 - 65535。当值小于32时为32，大于65535时为65535。
     * @return string   加密结果，Base64格式的字符串
     */
    public function stringEncryptEx(string $str, string $key, bool $isUtf8 = true, int $maskNumber = 32): string
    {
        $byteArr = $isUtf8 ? $this->_Utf8DirectToByteArray($str) : $this->_strUtf8ToUtf16ToByteArray($str);
        return $this->binaryEncryptEx($byteArr, $key, $maskNumber);
    }

    /**
     * Base64解密成字符串的升级版，简称：字符串动态解密（ 本函数与 stringEncryptEx() 为一对 ）
     * 注意：结果为UTF-8编码格式。为方便使用，PHP语言统一为UTF-8编码。换句话说，在PHP中，本函数返回的必定是UTF-8。
     *
     * @param string $base64str base64格式的加密字符串
     * @param string $key   密钥。本参数请保持与加密时的设置完全一致。
     * @param boolean $isUtf8   是否采用UTF-8编码格式。本参数请保持与加密时的设置完全一致。（注意：这里指的是加密前的编码，并非解密后的编码）
     * @return string   解密结果
     */
    public function stringDecryptEx(string $base64str, string  $key, bool $isUtf8 = true): string
    {
        $retArr = $this->binaryDecryptEx($base64str, $key);
        $dataStr = $isUtf8 ? $this->_byteArrayDirectToUtf8($retArr) : $this->_byteArrayToUtf16ToUtf8($retArr);
        return $dataStr;
    }

    /**
     * Base64对字符串加密（ 本函数与 stringDecrypt() 为一对 ）
     *
     * @param string $str           原数据。
     * @param string $key           密钥。理论上密钥的长度与逆向的难度成正比关系。
     * @param boolean $isUtf8       是否采用UTF-8编码格式。默认为：true。若设置为false，则使用UTF-16编码。
     * 注意：此处指的是加密前的编码，而非加密后的base64编码，base64是无须编码的。换句话来说，本参数指的是解密后的字符串编码。
     * JS的默认编码为UTF-16，但UTF-16并不友好，很多编程语言和服务端环境都不支持UTF-16。
     * @param boolean $isRFC4648    是否采用RFC4648编码映射规范，默认为：true。采用RFC4648规范编码的Base64符合URL安全，可用于HTTP协议与Ajax请求。
     * @return string   加密结果，Base64格式的字符串
     */
    public function stringEncrypt(string $str, string $key, bool $isUtf8 = true, bool $isRFC4648 = true): string
    {
        $byteArr = $isUtf8 ? $this->_Utf8DirectToByteArray($str) : $this->_strUtf8ToUtf16ToByteArray($str);
        return $this->binaryEncrypt($byteArr, $key, $isRFC4648);
    }

    /**
     * Base64解密成字符串（ 本函数与 stringEncrypt()为一对 ）
     * 注意：结果为UTF-8编码格式。为方便使用，PHP语言统一为UTF-8编码。换句话说，在PHP中，本函数返回的必定是UTF-8。
     *
     * @param string $base64str base64格式的加密字符串
     * @param string $key       密钥。本参数请保持与加密时的设置完全一致。
     * @param boolean $isUtf8   是否采用UTF-8编码格式。本参数请保持与加密时的设置完全一致。（注意：这里指的是加密前的编码，并非解密后的编码）
     * @return string   解密结果
     */
    public function stringDecrypt(string $base64str, string $key, bool $isUtf8 = true): string
    {
        $retArr = $this->binaryDecrypt($base64str, $key);
        $dataStr = $isUtf8 ? $this->_byteArrayDirectToUtf8($retArr) : $this->_byteArrayToUtf16ToUtf8($retArr);
        return $dataStr;
    }

    /**
     * Base64对字符串编码（注意：这是编码而非加密， 本函数与 stringDecode() 为一对）
     * 注意：此处指的是编码前的编码，而非编码后的base64编码，base64是无须编码的。换句话来说，本参数指的是解码后的字符串编码。
     *
     * @param string $str   原数据
     * @param boolean $isUtf8   是否采用UTF-8编码格式。默认为：true。若设置为false，则使用UTF-16编码
     * @param boolean $isRFC4648    是否采用RFC4648编码映射规范，默认为：true。采用RFC4648规范编码的Base64符合URL安全，可用于HTTP协议与Ajax请求
     * @return string
     */
    public function stringEncode(string $str, bool $isUtf8 = true, bool $isRFC4648 = true): string
    {
        $byteArr = $isUtf8 ? $this->_Utf8DirectToByteArray($str) : $this->_strUtf8ToUtf16ToByteArray($str);
        return $this->binaryEncode($byteArr, $isRFC4648);
    }

    /**
     * Base64解码成字符串（注意：这是解码而非解密， 本函数与 stringEncode() 为一对）
     * 注意：结果为UTF-16编码格式。为方便使用，解码结果会自动转换成当前程序语言的默认编码，以便开箱即用，省略二次编码。JS默认编码：UTF-16
     *
     * @param string $base64str base64格式编码的字符串
     * @param boolean $isUtf8   是否采用UTF-8编码格式。本参数请保持与编码时的设置完全一致。
     * @return string 解码结果
     */
    public function stringDecode(string $base64str, bool $isUtf8 = true): string
    {
        $retArr = $this->binaryDecode($base64str);
        $dataStr = $isUtf8 ? $this->_byteArrayDirectToUtf8($retArr) : $this->_byteArrayToUtf16ToUtf8($retArr);
        return $dataStr;
    }

    /**
     * Base64对二进制数据加密的升级版，简称：二进制动态加密（ 本函数与 binaryDecryptEx()为一对 ）
     *
     * @param array $byteArr    原数据。二进制字节数组，如：视频、音频、图片、文件等。
     * @param string $key   密钥。理论上密钥的长度与逆向的难度成正比关系。
     * @param integer $maskNumber   掩码的数量。缺省为：32，范围：32 - 65535。当值小于32时为32，大于65535时为65535。
     * @return string   加密结果，Base64格式的字符串
     */
    public function binaryEncryptEx(array $byteArr, string $key, int $maskNumber = 32): string
    {
        $maskArr = $this->_maskToByteArray($maskNumber);
        $mapArr = $this->_mapToByteArray(true);
        $keyArr = $this->_keyToByteArray($key);
        $kl = sizeof($keyArr);
        $bl = sizeof($byteArr);
        $ml = sizeof($maskArr);
        $rem = $bl % 3;
        $num = $bl % 3 === 0 ? (int)($bl / 3) : (int)($bl / 3) + 1;
        $base64Len = $num * 4;
        $tempArr = [];
        $ba64Arr = [];
        $i = 0;
        $k = 0;
        $m = 0;
        $v = 0;
        $mk = 0;
        /* 加密并转换为字节数组 */
        for ($j = 0; $base64Len > $i; $j++) {
            $k = $j % $kl;
            $m = $j % $ml;
            $mk = ($keyArr[$k] + $maskArr[$m] | $keyArr[$k] | $maskArr[$m]) % 0xFF;
            $tempArr[0] = $byteArr[$v + 0] ^ $mk;
            $tempArr[1] = $byteArr[$v + 1] ^ $mk;
            $tempArr[2] = $byteArr[$v + 2] ^ $mk;
            $ba64Arr[$i + 0] = $mapArr[$tempArr[0] >> 2];
            $ba64Arr[$i + 1] = $mapArr[(($tempArr[0] & 0x03) << 4) + ($tempArr[1] >> 4)];
            $ba64Arr[$i + 2] = $mapArr[(($tempArr[1] & 0x0F) << 2) + ($tempArr[2] >> 6)];
            $ba64Arr[$i + 3] = $mapArr[$tempArr[2] & 0x3F];
            $i = $i + 4;
            $v = $v + 3;
        }
        /* 有余数时的尾部处理 */
        $rfc = $rem === 1 ? 2 : ($rem === 2 ? 1 : 0);
        /* byteArray转成String */
        $ba64String = '';
        for ($i = 0; $i < $base64Len - $rfc; $i++) {
            $ba64String .= chr($ba64Arr[$i]);
        }
        /* 编码掩码并插入到头部 */
        $topArr = [];
        array_push($topArr, 0, 0);
        for ($n = 0; $n < sizeof($maskArr); $n++) {
            $topArr[2 + $n] = $maskArr[$n];
        }
        /* 有余数时的补包处理(减掉首部长度标记包) */
        $tl = sizeof($topArr);
        $tf = $tl % 3 === 0 ? $tl : 3 - ($tl % 3);
        for ($n = 0; $n < $tf; $n++) {
            array_push($topArr, 0);
        }
        /* 合并数组后返回 */
        $lenArr = $this->_shortToByteArray(sizeof($topArr) - 2);
        $topArr[1] = $lenArr[1];
        $topArr[0] = $lenArr[0];
        $ba64Top = $this->binaryEncrypt($topArr, $key, true);
        return $ba64Top . $ba64String;
    }

    /**
     * Base64解密成二进制数据的升级版，简称：二进制动态解密（ 本函数与 binaryEncryptEx()为一对 ）
     *
     * @param string $base64str base64格式的加密字符串
     * @param string $key   密钥。本参数请保持与加密时的设置完全一致。
     * @return array    解密结果，为字节数组（也就是二进制数据流）
     */
    public function binaryDecryptEx(string $base64str, string $key): array
    {
        $topArr = $this->binaryDecrypt(substr($base64str, 0, 4), $key);
        $maskLen = (int)$this->_byteArrayGetShort($topArr);
        $maskRem = $maskLen % 3;
        $maskMax = $maskRem === 0 ? $maskLen / 3 * 4 : $maskLen / 3 * 4 + (3 - $maskRem);
        $maskMax = intval($maskMax);
        $leftArr = $this->binaryDecrypt(substr($base64str, 0, $maskMax + 4), $key);
        /* 提取掩饰的字节数组 */
        $maskArr = [];
        for ($i = 0; $i < $maskLen; $i++) {
            $maskArr[$i] = $leftArr[$i + 2];
        }
        $shift = $maskRem === 1 ? 1 : 3;
        $dataStart = $maskMax + $shift;
        $dataStr = substr($base64str, $dataStart, strlen($base64str));
        $bl = strlen($dataStr);
        $kl = strlen($key);
        $ml = sizeof($maskArr);
        $num = $bl % 4;
        $rem = $num === 0 ? 0 : 4 - $num;
        $loop = $rem === 0 ? (int)($bl / 4) : (int)($bl / 4) + 1;
        $nl = $loop * 3;
        /* 填充被省略的'='字符'----为了遵循严谨的编程精神（JS中可选，其它语言中必须） */
        $fill = '';
        for ($i = 0; $i < $rem; $i++) {
            $fill .= '=';
        }
        $dataStr .= $fill;
        /* 将字符串换为字节数组 */
        $keyArr = $this->_keyToByteArray($key);
        $baseUint8Arr = $this->_base64strToByteArray($dataStr);
        $newArr = [];
        $h = 0;
        $i = 0;
        $k = 0;
        $j = -1;
        $m = -1;
        $mk = 0;
        /* 解密并转换为字节数组 */
        for ($w = 0; $w < $loop; $w++) {
            $j++;
            $k = $j % $kl;
            $m = $j % $ml;
            $mk = ($keyArr[$k] + $maskArr[$m] | $keyArr[$k] | $maskArr[$m]) % 0xFF;
            $tempArr = [];
            /* 本方式性能卓越，无需遍历base64映射表，直接计算映射关系 */
            for ($y = 0; $y < 4; $y++) {
                $n = 0;
                $p = $w * 4 + $y;
                $b = $baseUint8Arr[$p];
                if ($b >= 65 && $b <= 90) {
                    /* ABCDEFGHIJKLMNOPQRSTUVWXYZ */
                    $n = $b - 65;
                } else if ($b >= 97 && $b <= 122) {
                    /* abcdefghijklmnopqrstuvwxyz */
                    $shifting = 26;
                    $n = $b - 97 + $shifting;
                } else if ($b >= 48 && $b <= 57) {
                    /* 0123456789 */
                    $shifting = 52;
                    $n = $b - 48 + $shifting;
                } else if ($b === 43 || $b === 45) {
                    /* '+' === 43 || '-' ==== 45 */
                    $n = 62;
                } else if ($b === 47 || $b === 95) {
                    /* '/' === 47 || '_' === 95 */
                    $n = 63;
                } else {
                    $h++;
                }
                $tempArr[$y] = $n;
            }
            $d1 = $tempArr[0] << 2 | $tempArr[1] >> 4;
            $d2 = ($tempArr[1] & 15) << 4 | $tempArr[2] >> 2;
            $d3 = ($tempArr[2] & 3) << 6 | $tempArr[3];
            $newArr[$i + 0] = $d1 ^ $mk;
            $newArr[$i + 1] = $d2 ^ $mk;
            $newArr[$i + 2] = $d3 ^ $mk;
            $i += 3;
        }
        /* byteArray转成String----为跨平台的兼容性不使用Array.slice */
        $retLen = sizeof($newArr) - $h;
        $retArr = [];
        for ($n = 0; $n < $retLen; $n++) {
            $retArr[$n] = $newArr[$n];
        }
        return $retArr;
    }

    /**
     * Base64对二进制数据加密（ 本函数与 binaryDecrypt()为一对 ）
     *
     * @param array $byteArr    原数据。二进制字节数组，如：视频、音频、图片、文件等。
     * @param string $key   密钥。理论上密钥的长度与逆向的难度成正比关系。
     * @param boolean $isRFC4648    是否采用isRFC4648编码映射规范，默认为：true。采用isRFC4648规范编码的Base64符合URL安全，可用于HTTP协议与Ajax请求。
     * @return string   加密结果，Base64格式的字符串
     */
    public function binaryEncrypt(array $byteArr, string $key, bool $isRFC4648 = true): string
    {
        $mapArr = $this->_mapToByteArray($isRFC4648);
        $keyArr = $this->_keyToByteArray($key);
        $kl = sizeof($keyArr);
        $bl = sizeof($byteArr);
        $rem = $bl % 3;
        $num = $bl % 3 === 0 ? (int)($bl / 3) : (int)($bl / 3) + 1;
        $base64Len = $num * 4;
        $tempArr = [];
        $ba64Arr = [];
        $i = 0;
        $k = 0;
        $v = 0;
        /* 加密并转换为字节数组 */
        for ($j = 0; $base64Len > $i; $j++) {
            $k = $j % $kl;
            $tempArr[0] = ($byteArr[$v + 0] ^ $keyArr[$k]) % 0xFF;
            $tempArr[1] = ($byteArr[$v + 1] ^ $keyArr[$k]) % 0xFF;
            $tempArr[2] = ($byteArr[$v + 2] ^ $keyArr[$k]) % 0xFF;
            $ba64Arr[$i + 0] = $mapArr[$tempArr[0] >> 2];
            $ba64Arr[$i + 1] = $mapArr[(($tempArr[0] & 0x03) << 4) + ($tempArr[1] >> 4)];
            $ba64Arr[$i + 2] = $mapArr[(($tempArr[1] & 0x0F) << 2) + ($tempArr[2] >> 6)];
            $ba64Arr[$i + 3] = $mapArr[$tempArr[2] & 0x3F];
            $i = $i + 4;
            $v = $v + 3;
        }
        /* 有余数时的尾部处理 */
        $rfc = 0;
        if (!$isRFC4648) {
            if ($rem === 1) {
                $ba64Arr[$base64Len - 2] = 0x3D;
                $ba64Arr[$base64Len - 1] = 0x3D;
            } else if ($rem === 2) {
                $ba64Arr[$base64Len - 1] = 0x3D;
            }
        } else {
            if ($rem === 1) {
                $rfc = 2;
            } else if ($rem === 2) {
                $rfc = 1;
            } else {
                $rfc = 0;
            }
        }
        /* $byteArray转成String */
        $ba64String = '';
        for ($i = 0; $i < $base64Len - $rfc; $i++) {
            $ba64String .= chr($ba64Arr[$i]);
        }
        return $ba64String;
    }

    /**
     * Base64解密成二进制数据（ 本函数与 binaryEncrypt()为一对 ）
     *
     * @param string $base64str base64格式的加密字符串
     * @param string $key   密钥。本参数请保持与加密时的设置完全一致。
     * @return array    解密结果，为字节数组（也就是二进制数据流）
     */
    public function binaryDecrypt(string $base64str, string $key): array
    {
        $bl = strlen($base64str);
        $kl = strlen($key);
        $num = $bl % 4;
        $rem = $num === 0 ? 0 : 4 - $num;
        $loop = $rem === 0 ? (int)($bl / 4) : (int)($bl / 4) + 1;
        $nl = $loop * 3;
        /* 填充被省略的'='字符'----为了遵循严谨的编程精神（JS中可选，其它语言中必须） */
        $fill = '';
        for ($i = 0; $i < $rem; $i++) {
            $fill .= '=';
        }
        $base64str .= $fill;
        /* 将字符串换为字节数组 */
        $keyArr = $this->_keyToByteArray($key);
        $baseUint8Arr = $this->_base64strToByteArray($base64str);
        $newArr = [];
        $h = 0;
        $i = 0;
        $j = -1;
        $k = 0;
        /* 解密并转换为字节数组 */
        for ($w = 0; $w < $loop; $w++) {
            $j++;
            $k = $j % $kl;
            $tempArr = [];
            /* 本方式性能卓越，无需遍历base64映射表，直接计算映射关系 */
            for ($y = 0; $y < 4; $y++) {
                $n = 0;
                $p = $w * 4 + $y;
                $b = $baseUint8Arr[$p];
                if ($b >= 65 && $b <= 90) {
                    /* ABCDEFGHIJKLMNOPQRSTUVWXYZ */
                    $n = $b - 65;
                } else if ($b >= 97 && $b <= 122) {
                    /* abcdefghijklmnopqrstuvwxyz */
                    $shifting = 26;
                    $n = $b - 97 + $shifting;
                } else if ($b >= 48 && $b <= 57) {
                    /* 0123456789 */
                    $shifting = 52;
                    $n = $b - 48 + $shifting;
                } else if ($b === 43 || $b === 45) {
                    /* '+' === 43 || '-' ==== 45 */
                    $n = 62;
                } else if ($b === 47 || $b === 95) {
                    /* '/' === 47 || '_' === 95 */
                    $n = 63;
                } else {
                    $h++;
                }
                $tempArr[$y] = $n;
            }
            $d1 = $tempArr[0] << 2 | $tempArr[1] >> 4;
            $d2 = ($tempArr[1] & 15) << 4 | $tempArr[2] >> 2;
            $d3 = ($tempArr[2] & 3) << 6 | $tempArr[3];
            $newArr[$i + 0] = $d1 ^ $keyArr[$k];
            $newArr[$i + 1] = $d2 ^ $keyArr[$k];
            $newArr[$i + 2] = $d3 ^ $keyArr[$k];
            $i += 3;
        }
        /* byteArray转成String----为跨平台的兼容性不使用Array.slice */
        $retLen = sizeof($newArr) - $h;
        $retArr = [];
        for ($n = 0; $n < $retLen; $n++) {
            $retArr[$n] = $newArr[$n];
        }
        return $retArr;
    }

    /**
     * Base64对二进制数据编码
     * 注意：这是编码而非加密， 本函数与 binaryDecode()为一对
     *
     * @param array $byteArr    原数据。二进制字节数组，如：视频、音频、图片、文件等。
     * @param boolean $isRFC4648    是否采用RFC4648编码映射规范，默认为：true。采用RFC4648规范编码的Base64符合URL安全，可用于HTTP协议与Ajax请求。
     * @return string   编码结果，标准Base64格式的字符串
     */
    public function binaryEncode(array $byteArr, bool $isRFC4648 = true): string
    {
        $mapArr = $this->_mapToByteArray($isRFC4648);
        $bl = sizeof($byteArr);
        $rem = $bl % 3;
        $num = $bl % 3 === 0 ? (int)($bl / 3) : (int)($bl / 3) + 1;
        $base64Len = $num * 4;
        $ba64Arr = [];
        $i = 0;
        $v = 0;
        /* 编码并转换为字节数组 */
        for (; $base64Len > $i; $i = $i + 4) {
            $ba64Arr[$i + 0] = $mapArr[$byteArr[$v + 0] >> 2];
            $ba64Arr[$i + 1] = $mapArr[(($byteArr[$v + 0] & 0x03) << 4) + ($byteArr[$v + 1] >> 4)];
            $ba64Arr[$i + 2] = $mapArr[(($byteArr[$v + 1] & 0x0F) << 2) + ($byteArr[$v + 2] >> 6)];
            $ba64Arr[$i + 3] = $mapArr[$byteArr[$v + 2] & 0x3F];
            $v = $v + 3;
        }
        /* 有余数时的尾部处理 */
        $rfc = 0;
        if (!$isRFC4648) {
            if ($rem === 1) {
                $ba64Arr[$base64Len - 2] = 0x3D;
                $ba64Arr[$base64Len - 1] = 0x3D;
            } else if ($rem === 2) {
                $ba64Arr[$base64Len - 1] = 0x3D;
            }
        } else {
            if ($rem === 1) {
                $rfc = 2;
            } else if ($rem === 2) {
                $rfc = 1;
            } else {
                $rfc = 0;
            }
        }
        /* byteArray转成String */
        $ba64String = '';
        for ($i = 0; $i < $base64Len - $rfc; $i++) {
            $ba64String .= chr($ba64Arr[$i]);
        }
        return $ba64String;
    }

    /**
     * Base64解码成二进制数据（ 注意：这是解码而非解密， 本函数与 binaryEncode()为一对 ）
     *
     * @param string $base64str base64格式编码的字符串
     * @return array    解码结果，为字节数组（也就是二进制数据流）
     */
    public function binaryDecode(string $base64str): array
    {
        $bl = strlen($base64str);
        $num = $bl % 4;
        $rem = $num === 0 ? 0 : 4 - $num;
        $loop = $rem === 0 ? (int)($bl / 4) : (int)($bl / 4) + 1;
        $nl = $loop * 3;
        /* 填充被省略的'='字符'----为了遵循严谨的编程精神（JS中可选，其它语言中必须） */
        $fill = '';
        for ($i = 0; $i < $rem; $i++) {
            $fill .= '=';
        }
        $base64str .= $fill;
        /* 将字符串换为字节数组 */
        $baseUint8Arr = $this->_base64strToByteArray($base64str);
        $newArr = [];
        $h = 0;
        $i = 0;
        /* 解码并转换为字节数组 */
        for ($w = 0; $w < $loop; $w++) {
            $tempArr = [];
            /* 本方式性能卓越，无需遍历base64映射表，直接计算映射关系 */
            for ($y = 0; $y < 4; $y++) {
                $n = 0;
                $p = $w * 4 + $y;
                $b = $baseUint8Arr[$p];
                if ($b >= 65 && $b <= 90) {
                    /* ABCDEFGHIJKLMNOPQRSTUVWXYZ */
                    $n = $b - 65;
                } else if ($b >= 97 && $b <= 122) {
                    /* abcdefghijklmnopqrstuvwxyz */
                    $shifting = 26;
                    $n = $b - 97 + $shifting;
                } else if ($b >= 48 && $b <= 57) {
                    /* 0123456789 */
                    $shifting = 52;
                    $n = $b - 48 + $shifting;
                } else if ($b === 43 || $b === 45) {
                    /* '+' === 43 || '-' ==== 45 */
                    $n = 62;
                } else if ($b === 47 || $b === 95) {
                    /* '/' === 47 || '_' === 95 */
                    $n = 63;
                } else {
                    $h++;
                }
                $tempArr[$y] = $n;
            }
            $newArr[$i + 0] = $tempArr[0] << 2 | $tempArr[1] >> 4;
            $newArr[$i + 1] = ($tempArr[1] & 15) << 4 | $tempArr[2] >> 2;
            $newArr[$i + 2] = ($tempArr[2] & 3) << 6 | $tempArr[3];
            $i += 3;
        }
        /* byteArray转成String----为跨平台的兼容性不使用Array.slice */
        $retLen = sizeof($newArr);
        $retArr = [];
        for ($n = 0; $n < $retLen; $n++) {
            $retArr[$n] = $newArr[$n];
        }
        return $retArr;
    }

    /**
     * string按Utf-16编码方式转为byteArray（字符串按Utf-16编码转为字节数组）。
     * 编码过程步骤：1.str（按UTF8）转为UTF-16，2.再按UTF-16转字节数组。
     * @summary PHP的默认编码是ISO-8859-1，但由于PHP的编码可以通过header()设置，因此大家常用的都是UTF8。但JS不同，JS的默认编码是UTF-16，而且无法修改设置默认编码！
     *
     * @param string $str   原字符串
     * @return array    转换结果为字节数组（也就是二进制数据流）
     */
    private function _strUtf8ToUtf16ToByteArray(string $str): array
    {
        $i = 0;
        $k = 0;
        $short = 0;
        $byteArr = [];
        $strLen = strlen($str);
        while ($strLen > $i) {
            $t = ord($str[$i]);
            if ($t >> 3 === 0x1E) {
                $a = ord($str[$i + 0]) % 0xF0;
                $b = ord($str[$i + 1]) % 0x80;
                $c = ord($str[$i + 2]) % 0x80;
                $d = ord($str[$i + 3]) % 0x80;
                $short = ($a << 18) + ($b << 12) + ($c << 6) + $d;
                $i += 4;
            } else if ($t >> 4 === 0x0E) {
                $a = ord($str[$i + 0]) % 0xE0;
                $b = ord($str[$i + 1]) % 0x80;
                $c = ord($str[$i + 2]) % 0x80;
                $short = ($a << 12) + ($b << 6) + $c;
                $i += 3;
            } else if ($t >> 5 === 0x06) {
                $a = ord($str[$i + 0]) % 0xC0;
                $b = ord($str[$i + 1]) % 0x80;
                $short = ($a << 6) + $b;
                $i += 2;
            } else {
                $short = ord($str[$i + 0]);
                $i++;
            }
            $uft16Bytes = $this->_shortToByteArray($short);
            $byteArr[$k++] = $uft16Bytes[0];
            $byteArr[$k++] = $uft16Bytes[1];
        }
        /* 直接修正不能被3正除 */
        $rem = sizeof($byteArr) % 3;
        $add = $rem === 0 ? 0 : 3 - $rem;
        for ($i = 0; $i < $add; $i++) {
            array_push($byteArr, 0);
        }
        return $byteArr;
    }

    /**
     * string按Utf-8编码方式转为byteArray（字符串按Utf-8编码转为字节数组）。
     * @summary PHP的默认编码是ISO-8859-1，但由于PHP的编码可以通过header()设置，因此大家常用的都是UTF8。但JS不同，JS的默认编码是UTF-16，而且无法修改设置默认编码！
     *
     * @param string $str   原字符串
     * @return array    转换结果为字节数组（也就是二进制数据流）
     */
    private function _Utf8DirectToByteArray(string $str): array
    {
        $i = 0;
        $byteArr = [];
        $strLen = strlen($str);
        for ($i = 0; $i < $strLen; $i++) {
            array_push($byteArr, ord($str[$i]));
        }
        /* 直接修正不能被3正除 */
        $rem = $strLen % 3;
        $add = $rem === 0 ? 0 : 3 - $rem;
        for ($i = 0; $i < $add; $i++) {
            array_push($byteArr, 0);
        }
        return $byteArr;
    }

    /**
     * byteArray按UTF8解码转成String（字节数组按UTF16解码成字符串 ）
     * 解码过程步骤：1.byteArray（按UTF16）转为UTF-16，2.UTF-16转字符串。
     *
     * @param array $byteArr 原字符串数组
     * @return string 转换结果为字符串
     */
    private function _byteArrayToUtf16(array $byteArr): string
    {
        $i = 0;
        $utf16Str = '';
        $strLen = sizeof($byteArr);
        while ($strLen > $i) {
            $utf16Str .= chr($byteArr[$i++]) . chr($byteArr[$i++]);
        }
        return $utf16Str;
    }

    /**
     * byteArray按UTF8解码转成String（字节数组按UTF8解码成字符串，注意：JS中字符串默认编码为UTF-16， 而不是UTF-8 ）
     * 解码过程步骤：1.byteArray（按UTF8）转为UTF-16，2.UTF-16转字符串。
     *
     * @param array $byteArr    原字符串数组
     * @return string   转换结果
     */
    private function _byteArrayToUtf16ToUtf8(array $byteArr): string
    {
        $utf16Str = '';
        $strLen = count($byteArr);
        $strLen = $strLen % 2 === 0 ? $strLen : $strLen - 1;
        if ($strLen < 2) {
            // echo 'error.....' . $strLen;
            return '';
        }
        for ($i = 0; $strLen > $i; $i += 2) {
            $str = '';
            $x = $byteArr[$i + 0];
            $y = $byteArr[$i + 1];
            $short = $x + ($y << 8);
            if ($short > 0xFFFF) {
                $a = (0xF0 | (0x07 & ($short >> 18)));
                $b = (0x80 | (0x3F & ($short >> 12)));
                $c = (0x80 | (0x3F & ($short >> 6)));
                $d = (0x80 | (0x3F & $short));
                $str = chr($a) . chr($b) . chr($c) . chr($d);
            } else if ($short > 0x7FF) {
                $a = (0xE0 | (0x0F & ($short >> 12)));
                $b = (0x80 | (0x3F & ($short >> 6)));
                $c = (0x80 | (0x3F & $short));
                $str = chr($a) . chr($b) . chr($c);
            } else if ($short > 0x7F) {
                $a = (0xC0 | (0x1F & ($short >> 6)));
                $b = (0x80 | (0x3F & $short));
                $str = chr($a) . chr($b);
            } else {
                $str = chr($short);
            }
            $utf16Str .= $str;
        }
        return $utf16Str;
    }

    /**
     * byteArray按UTF8解码转成String（字节数组按UTF8解码成字符串，通常PHP中用UTF-8编码，所以和JS不同，需要用此函数 ）
     * 解码过程步骤：1.byteArray（按UTF8）转为UTF-8，2.UTF-8转字符串。（这个在PHP里可以直接转换，很简单）
     *
     * @param array $byteArr   原字符串数组
     * @return string   转换结果为字符串
     */
    private function _byteArrayDirectToUtf8(array $byteArr): string
    {
        $i = 0;
        $utf16Str = '';
        $strLen = count($byteArr);
        while ($strLen > $i) {
            $str = '';
            $t = $byteArr[$i];
            if ($t >> 3 === 0x1E) {
                $a = $byteArr[$i + 0];
                $b = $byteArr[$i + 1];
                $c = $byteArr[$i + 2];
                $d = $byteArr[$i + 3];
                $str = chr($a) . chr($b) . chr($c) . chr($d);
                $i += 4;
            } else if ($t >> 4 === 0x0E) {
                $a = $byteArr[$i + 0];
                $b = $byteArr[$i + 1];
                $c = $byteArr[$i + 2];
                $str = chr($a) . chr($b) . chr($c);
                $i += 3;
            } else if ($t >> 5 === 0x06) {
                $a = $byteArr[$i + 0];
                $b = $byteArr[$i + 1];
                $str = chr($a) . chr($b);
                $i += 2;
            } else {
                $a = $byteArr[$i + 0];
                $str = chr($a);
                $i++;
            }
            $utf16Str .= $str;
        }
        return $utf16Str;
    }

    /**
     * ba64String转为byteArray（字符串转字节数组, base64专用）
     *
     * @param string $base64str base64格式的字符串
     * @return array 转换结果为字节数组（也就是二进制数据流）
     */
    private function _base64strToByteArray(string $base64str): array
    {
        $realLen = strlen($base64str);
        $byteArr = [];
        for ($i = 0; $i < $realLen; $i++) {
            $byteArr[$i] = ord($base64str[$i]);
        }
        return $byteArr;
    }

    /**
     * key转为byteArray（字符串转字节数组, 密钥专用）
     *
     * @param string key {string} 密钥
     * @return array 转换结果为字节数组（也就是二进制数据流） {ByteArray} 
     */
    private function _keyToByteArray(string $key): array
    {
        $byteArr = [];
        for ($i = 0, $l = strlen($key); $i < $l; $i++) {
            $byteArr[$i] = ord($key[$i]);
        }
        return $byteArr;
    }

    /**
     * short转为byteArray（短整数转字节数组）
     *
     * @param integer $twoByte  短整数。
     * @return array 转换结果为字节数组（也就是二进制数据流）
     */
    private function _shortToByteArray(int $twoByte): array
    {
        $byteArr = [];
        $byteArr[0] = $twoByte & 0xFF;;
        $byteArr[1] = ($twoByte - $byteArr[0]) / 0x100;
        return $byteArr;
    }

    /**
     * 从base64中提取short（ 提取mask的长度 ）
     *
     * @param array $byteArr    base64头部字节数组。
     * @return integer  表示mask的长度
     */
    private function _byteArrayGetShort(array $byteArr)
    {
        $maskLen = ($byteArr[1] << 8) + $byteArr[0];
        return $maskLen;
    }

    /**
     * 取随机掩码
     *
     * @param integer $maskNumber   掩码的数量。缺省为：32，范围：32 - 65535。当值小于32时为32，大于65535时为65535。
     * @return array    转换结果为字节数组（也就是二进制数据流） {ByteArray} 
     */
    private function _maskToByteArray(int $maskNumber): array
    {
        $maskNumber = $maskNumber < 32 ? 32 : $maskNumber;
        $maskNumber = $maskNumber > 65535 ? 65535 : $maskNumber;
        $byteArr = [];
        for ($i = 0; $i < $maskNumber; $i++) {
            $byteArr[$i] = rand(0, 255);
        }
        return $byteArr;
    }

    /**
     * key转为byteArray（字符串转字节数组, base64编码映射表专用）
     *
     * @param boolean $rfc4648 是否使用RFC4648映射标准，默认为：false
     * @return array
     */
    private function _mapToByteArray(bool $rfc4648 = false): array
    {
        $map = '';
        if ($rfc4648) {
            /* 以'-_'结尾的映射表为RFC4648标准的安全URL国际规范，主要用与HTTP协议，如：Ajax请求 */
            $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        } else {
            /* 以'+/'结尾的映射表为国际统一的原生标准。但URL请求中：+会被转成空格，/会被解析成路径，因此不符合URL安全 */
            $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
        }
        $byteArr = [];
        for ($i = 0, $l = strlen($map); $i < $l; $i++) {
            //$byteArr[$i] = ord($map[$i]);
            array_push($byteArr, ord($map[$i]));
        }
        return $byteArr;
    }
}
