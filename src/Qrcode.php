<?php

declare(strict_types=1);

namespace mon\util;

use Exception;
use mon\util\exception\QRcodeException;

/**
 * 二维码枚举值
 */
class QRcodeEnum
{
    const QR_MODE_NUL = -1;
    const QR_MODE_NUM = 0;
    const QR_MODE_AN = 1;
    const QR_MODE_8 = 2;
    const QR_MODE_KANJI = 3;
    const QR_MODE_STRUCTURE = 4;

    const QR_ECLEVEL_L = 0;
    const QR_ECLEVEL_M = 1;
    const QR_ECLEVEL_Q = 2;
    const QR_ECLEVEL_H = 3;

    const QR_FORMAT_TEXT = 0;
    const QR_FORMAT_PNG = 1;

    const QR_CACHEABLE = false;
    const QR_CACHE_DIR = false;
    const QR_FIND_BEST_MASK = true;
    const QR_FIND_FROM_RANDOM = 2;
    const QR_DEFAULT_MASK = 2;
    const QR_PNG_MAXIMUM_SIZE = 1024;

    const QRSPEC_VERSION_MAX = 40;
    const QRSPEC_WIDTH_MAX = 177;

    const QRCAP_WIDTH = 0;
    const QRCAP_WORDS = 1;
    const QRCAP_REMINDER = 2;
    const QRCAP_EC = 3;

    const STRUCTURE_HEADER_BITS = 20;
    const MAX_STRUCTURED_SYMBOLS = 16;

    const QR_IMAGE = true;

    const N1 = 3;
    const N2 = 3;
    const N3 = 40;
    const N4 = 10;
}

/**
 * 二维码实体类
 */
class QRcode
{
    public $version;

    public $width;

    public $data;

    public function encodeMask(QRinput $input, $mask)
    {
        if ($input->getVersion() < 0 || $input->getVersion() > QRcodeEnum::QRSPEC_VERSION_MAX) {
            throw new QRcodeException('wrong version');
        }
        if ($input->getErrorCorrectionLevel() > QRcodeEnum::QR_ECLEVEL_H) {
            throw new QRcodeException('wrong level');
        }

        $raw = new QRrawcode($input);
        $version = $raw->version;
        $width = QRspec::getWidth($version);
        $frame = QRspec::newFrame($version);

        $filler = new FrameFiller($width, $frame);
        if (is_null($filler)) {
            return NULL;
        }

        for ($i = 0; $i < $raw->dataLength + $raw->eccLength; $i++) {
            $code = $raw->getCode();
            $bit = 0x80;
            for ($j = 0; $j < 8; $j++) {
                $addr = $filler->next();
                $filler->setFrameAt($addr, 0x02 | (($bit & $code) != 0));
                $bit = $bit >> 1;
            }
        }

        unset($raw);
        $j = QRspec::getRemainder($version);
        for ($i = 0; $i < $j; $i++) {
            $addr = $filler->next();
            $filler->setFrameAt($addr, 0x02);
        }

        $frame = $filler->frame;
        unset($filler);

        // masking
        $maskObj = new QRmask();
        if ($mask < 0) {
            if (QRcodeEnum::QR_FIND_BEST_MASK) {
                $masked = $maskObj->mask($width, $frame, $input->getErrorCorrectionLevel());
            } else {
                $masked = $maskObj->makeMask($width, $frame, (intval(QRcodeEnum::QR_DEFAULT_MASK) % 8), $input->getErrorCorrectionLevel());
            }
        } else {
            $masked = $maskObj->makeMask($width, $frame, $mask, $input->getErrorCorrectionLevel());
        }

        if ($masked == NULL) {
            return NULL;
        }

        $this->version = $version;
        $this->width = $width;
        $this->data = $masked;

        return $this;
    }

    public function encodeInput(QRinput $input)
    {
        return $this->encodeMask($input, -1);
    }

    public function encodeString8bit($string, $version, $level)
    {
        if ($string == NULL) {
            throw new QRcodeException('empty string!');
        }

        $input = new QRinput($version, $level);
        if ($input == NULL) {
            return NULL;
        }

        $ret = $input->append($input, QRcodeEnum::QR_MODE_8, strlen($string), str_split($string));
        if ($ret < 0) {
            unset($input);
            return NULL;
        }
        return $this->encodeInput($input);
    }

    public function encodeString($string, $version, $level, $hint, $casesensitive)
    {
        if ($hint != QRcodeEnum::QR_MODE_8 && $hint != QRcodeEnum::QR_MODE_KANJI) {
            throw new QRcodeException('bad hint');
        }

        $input = new QRinput($version, $level);
        if ($input == NULL) {
            return NULL;
        }

        $ret = QRsplit::splitStringToQRinput($string, $input, $hint, $casesensitive);
        if ($ret < 0) {
            return NULL;
        }

        return $this->encodeInput($input);
    }

    /**
     * 生成png图像二维码
     *
     * @param string $text  二维码内容
     * @param integer $level    密度等级
     * @param integer $size     大小
     * @param integer $margin   外边距
     * @return string   图片内容
     */
    public static function png($text, $level = QRcodeEnum::QR_ECLEVEL_L, $size = 6, $margin = 2)
    {
        $enc = QRencode::factory($level, $size, $margin);
        return $enc->encodePNG($text);
    }

    public static function text($text, $level = QRcodeEnum::QR_ECLEVEL_L, $size = 6, $margin = 2)
    {
        $enc = QRencode::factory($level, $size, $margin);
        return $enc->encode($text);
    }

    public static function raw($text, $level = QRcodeEnum::QR_ECLEVEL_L, $size = 6, $margin = 2)
    {
        $enc = QRencode::factory($level, $size, $margin);
        return $enc->encodeRAW($text);
    }
}

class QRinput
{
    public static $anTable = [
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        36,
        -1,
        -1,
        -1,
        37,
        38,
        -1,
        -1,
        -1,
        -1,
        39,
        40,
        -1,
        41,
        42,
        43,
        0,
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        44,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        10,
        11,
        12,
        13,
        14,
        15,
        16,
        17,
        18,
        19,
        20,
        21,
        22,
        23,
        24,
        25,
        26,
        27,
        28,
        29,
        30,
        31,
        32,
        33,
        34,
        35,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1
    ];

    public $items;

    private $version;

    private $level;

    public static function checkModeNum($size, $data)
    {
        for ($i = 0; $i < $size; $i++) {
            if ((ord($data[$i]) < ord('0')) || (ord($data[$i]) > ord('9'))) {
                return false;
            }
        }

        return true;
    }

    public static function estimateBitsModeNum($size)
    {
        $w = (int)$size / 3;
        $bits = $w * 10;
        switch ($size - $w * 3) {
            case 1:
                $bits += 4;
                break;
            case 2:
                $bits += 7;
                break;
            default:
                break;
        }

        return $bits;
    }

    public static function lookAnTable($c)
    {
        return (($c > 127) ? -1 : self::$anTable[$c]);
    }

    public static function checkModeAn($size, $data)
    {
        for ($i = 0; $i < $size; $i++) {
            if (self::lookAnTable(ord($data[$i])) == -1) {
                return false;
            }
        }

        return true;
    }

    public static function estimateBitsModeAn($size)
    {
        $w = (int)($size / 2);
        $bits = $w * 11;
        if ($size & 1) {
            $bits += 6;
        }

        return $bits;
    }

    public static function estimateBitsMode8($size)
    {
        return $size * 8;
    }

    public static function estimateBitsModeKanji($size)
    {
        return (int)(($size / 2) * 13);
    }

    public static function checkModeKanji($size, $data)
    {
        if ($size & 1) {
            return false;
        }

        for ($i = 0; $i < $size; $i += 2) {
            $val = (ord($data[$i]) << 8) | ord($data[$i + 1]);
            if ($val < 0x8140 || ($val > 0x9ffc && $val < 0xe040) || $val > 0xebbf) {
                return false;
            }
        }

        return true;
    }

    public static function check($mode, $size, $data)
    {
        if ($size <= 0) {
            return false;
        }

        switch ($mode) {
            case QRcodeEnum::QR_MODE_NUM:
                return self::checkModeNum($size, $data);
            case QRcodeEnum::QR_MODE_AN:
                return self::checkModeAn($size, $data);
            case QRcodeEnum::QR_MODE_KANJI:
                return self::checkModeKanji($size, $data);
            case QRcodeEnum::QR_MODE_8:
                return true;
            case QRcodeEnum::QR_MODE_STRUCTURE:
                return true;
            default:
                break;
        }

        return false;
    }

    public static function lengthOfCode($mode, $version, $bits)
    {
        $payload = $bits - 4 - QRspec::lengthIndicator($mode, $version);
        switch ($mode) {
            case QRcodeEnum::QR_MODE_NUM:
                $chunks = (int)($payload / 10);
                $remain = $payload - $chunks * 10;
                $size = $chunks * 3;
                if ($remain >= 7) {
                    $size += 2;
                } else if ($remain >= 4) {
                    $size += 1;
                }
                break;
            case QRcodeEnum::QR_MODE_AN:
                $chunks = (int)($payload / 11);
                $remain = $payload - $chunks * 11;
                $size = $chunks * 2;
                if ($remain >= 6) {
                    $size++;
                }
                break;
            case QRcodeEnum::QR_MODE_8:
                $size = (int)($payload / 8);
                break;
            case QRcodeEnum::QR_MODE_KANJI:
                $size = (int)(($payload / 13) * 2);
                break;
            case QRcodeEnum::QR_MODE_STRUCTURE:
                $size = (int)($payload / 8);
                break;
            default:
                $size = 0;
                break;
        }

        $maxsize = QRspec::maximumWords($mode, $version);
        if ($size < 0) {
            $size = 0;
        }
        if ($size > $maxsize) {
            $size = $maxsize;
        }

        return $size;
    }

    public function __construct($version = 0, $level = QRcodeEnum::QR_ECLEVEL_L)
    {
        if ($version < 0 || $version > QRcodeEnum::QRSPEC_VERSION_MAX || $level > QRcodeEnum::QR_ECLEVEL_H) {
            throw new QRcodeException('Invalid version no');
        }

        $this->version = $version;
        $this->level = $level;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        if ($version < 0 || $version > QRcodeEnum::QRSPEC_VERSION_MAX) {
            throw new QRcodeException('Invalid version no');
        }

        $this->version = $version;
        return 0;
    }

    public function getErrorCorrectionLevel()
    {
        return $this->level;
    }

    public function setErrorCorrectionLevel($level)
    {
        if ($level > QRcodeEnum::QR_ECLEVEL_H) {
            throw new QRcodeException('Invalid ECLEVEL');
        }

        $this->level = $level;
        return 0;
    }

    public function appendEntry(QRinputItem $entry)
    {
        $this->items[] = $entry;
    }

    public function append($mode, $size, $data)
    {
        try {
            $entry = new QRinputItem($mode, $size, $data);
            $this->items[] = $entry;
            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function insertStructuredAppendHeader($size, $index, $parity)
    {
        if ($size > QRcodeEnum::MAX_STRUCTURED_SYMBOLS) {
            throw new QRcodeException('insertStructuredAppendHeader wrong size');
        }
        if ($index <= 0 || $index > QRcodeEnum::MAX_STRUCTURED_SYMBOLS) {
            throw new QRcodeException('insertStructuredAppendHeader wrong index');
        }

        $buf = [$size, $index, $parity];
        try {
            $entry = new QRinputItem(QRcodeEnum::QR_MODE_STRUCTURE, 3, $buf);
            array_unshift($this->items, $entry);
            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function calcParity()
    {
        $parity = 0;
        foreach ($this->items as $item) {
            if ($item->mode != QRcodeEnum::QR_MODE_STRUCTURE) {
                for ($i = $item->size - 1; $i >= 0; $i--) {
                    $parity ^= $item->data[$i];
                }
            }
        }

        return $parity;
    }

    public function estimateBitStreamSize($version)
    {
        $bits = 0;
        foreach ($this->items as $item) {
            $bits += $item->estimateBitStreamSizeOfEntry($version);
        }

        return $bits;
    }

    public function estimateVersion()
    {
        $version = 0;
        $prev = 0;
        do {
            $prev = $version;
            $bits = $this->estimateBitStreamSize($prev);
            $version = QRspec::getMinimumVersion((int)(($bits + 7) / 8), $this->level);
            if ($version < 0) {
                return -1;
            }
        } while ($version > $prev);

        return $version;
    }

    public function createBitStream()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $bits = $item->encodeBitStream($this->version);
            if ($bits < 0) {
                return -1;
            }

            $total += $bits;
        }

        return $total;
    }

    public function convertData()
    {
        $ver = $this->estimateVersion();
        if ($ver > $this->getVersion()) {
            $this->setVersion($ver);
        }

        for (;;) {
            $bits = $this->createBitStream();
            if ($bits < 0) {
                return -1;
            }

            $ver = QRspec::getMinimumVersion((int)(($bits + 7) / 8), $this->level);
            if ($ver < 0) {
                throw new QRcodeException('WRONG VERSION');
            } else if ($ver > $this->getVersion()) {
                $this->setVersion($ver);
            } else {
                break;
            }
        }

        return 0;
    }

    public function appendPaddingBit(&$bstream)
    {
        $bits = $bstream->size();
        $maxwords = QRspec::getDataLength($this->version, $this->level);
        $maxbits = $maxwords * 8;
        if ($maxbits == $bits) {
            return 0;
        }
        if ($maxbits - $bits < 5) {
            return $bstream->appendNum($maxbits - $bits, 0);
        }

        $bits += 4;
        $words = (int)(($bits + 7) / 8);
        $padding = new QRbitstream();
        $ret = $padding->appendNum($words * 8 - $bits + 4, 0);
        if ($ret < 0) {
            return $ret;
        }

        $padlen = $maxwords - $words;
        if ($padlen > 0) {
            $padbuf = [];
            for ($i = 0; $i < $padlen; $i++) {
                $padbuf[$i] = ($i & 1) ? 0x11 : 0xec;
            }

            $ret = $padding->appendBytes($padlen, $padbuf);
            if ($ret < 0) {
                return $ret;
            }
        }

        return $bstream->append($padding);
    }

    public function mergeBitStream()
    {
        if ($this->convertData() < 0) {
            return null;
        }

        $bstream = new QRbitstream();
        foreach ($this->items as $item) {
            $ret = $bstream->append($item->bstream);
            if ($ret < 0) {
                return null;
            }
        }

        return $bstream;
    }

    public function getBitStream()
    {
        $bstream = $this->mergeBitStream();
        if ($bstream == null) {
            return null;
        }

        $ret = $this->appendPaddingBit($bstream);
        if ($ret < 0) {
            return null;
        }

        return $bstream;
    }

    public function getByteStream()
    {
        $bstream = $this->getBitStream();
        if ($bstream == null) {
            return null;
        }

        return $bstream->toByte();
    }
}

class QRrawcode
{
    public $version;

    public $datacode = [];

    public $ecccode = [];

    public $blocks;

    public $rsblocks = [];

    public $count;

    public $dataLength;

    public $eccLength;

    public $b1;

    public function __construct(QRinput $input)
    {
        $spec = [0, 0, 0, 0, 0];

        $this->datacode = $input->getByteStream();
        if (is_null($this->datacode)) {
            throw new QRcodeException('null imput string');
        }

        QRspec::getEccSpec($input->getVersion(), $input->getErrorCorrectionLevel(), $spec);

        $this->version = $input->getVersion();
        $this->b1 = QRspec::rsBlockNum1($spec);
        $this->dataLength = QRspec::rsDataLength($spec);
        $this->eccLength = QRspec::rsEccLength($spec);
        $this->ecccode = array_fill(0, $this->eccLength, 0);
        $this->blocks = QRspec::rsBlockNum($spec);

        $ret = $this->init($spec);
        if ($ret < 0) {
            throw new QRcodeException('block alloc error');
        }

        $this->count = 0;
    }

    public function init(array $spec)
    {
        $dl = QRspec::rsDataCodes1($spec);
        $el = QRspec::rsEccCodes1($spec);
        $rs = QRrs::init_rs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);

        $blockNo = 0;
        $dataPos = 0;
        $eccPos = 0;
        for ($i = 0; $i < QRspec::rsBlockNum1($spec); $i++) {
            $ecc = array_slice($this->ecccode, $eccPos);
            $this->rsblocks[$blockNo] = new QRrsblock($dl, array_slice($this->datacode, $dataPos), $el,  $ecc, $rs);
            $this->ecccode = array_merge(array_slice($this->ecccode, 0, $eccPos), $ecc);

            $dataPos += $dl;
            $eccPos += $el;
            $blockNo++;
        }

        if (QRspec::rsBlockNum2($spec) == 0) {
            return 0;
        }

        $dl = QRspec::rsDataCodes2($spec);
        $el = QRspec::rsEccCodes2($spec);
        $rs = QRrs::init_rs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);

        if ($rs == NULL) {
            return -1;
        }

        for ($i = 0; $i < QRspec::rsBlockNum2($spec); $i++) {
            $ecc = array_slice($this->ecccode, $eccPos);
            $this->rsblocks[$blockNo] = new QRrsblock($dl, array_slice($this->datacode, $dataPos), $el, $ecc, $rs);
            $this->ecccode = array_merge(array_slice($this->ecccode, 0, $eccPos), $ecc);

            $dataPos += $dl;
            $eccPos += $el;
            $blockNo++;
        }

        return 0;
    }

    public function getCode()
    {
        $ret = null;
        if ($this->count < $this->dataLength) {
            $row = $this->count % $this->blocks;
            $col = $this->count / $this->blocks;
            if ($col >= $this->rsblocks[0]->dataLength) {
                $row += $this->b1;
            }
            $ret = $this->rsblocks[$row]->data[$col];
        } else if ($this->count < $this->dataLength + $this->eccLength) {
            $row = ($this->count - $this->dataLength) % $this->blocks;
            $col = ($this->count - $this->dataLength) / $this->blocks;
            $ret = $this->rsblocks[$row]->ecc[$col];
        } else {
            return 0;
        }
        $this->count++;

        return $ret;
    }
}

class QRstr
{
    public static function set(&$srctab, $x, $y, $repl, $replLen = false)
    {
        $srctab[$y] = substr_replace($srctab[$y], ($replLen !== false) ? substr($repl, 0, (int)$replLen) : $repl, $x, ($replLen !== false) ? $replLen : strlen($repl));
    }
}


class QRtools
{
    public static function binarize($frame)
    {
        $len = count($frame);
        foreach ($frame as &$frameLine) {
            for ($i = 0; $i < $len; $i++) {
                $frameLine[$i] = (ord($frameLine[$i]) & 1) ? '1' : '0';
            }
        }

        return $frame;
    }
}

class QRspec
{
    public static $capacity = [
        [0, 0, 0, [0, 0, 0, 0]],
        [21, 26, 0, [7, 10, 13, 17]], // 1
        [25, 44, 7, [10, 16, 22, 28]],
        [29, 70, 7, [15, 26, 36, 44]],
        [33, 100, 7, [20, 36, 52, 64]],
        [37, 134, 7, [26, 48, 72, 88]], // 5
        [41, 172, 7, [36, 64, 96, 112]],
        [45, 196, 0, [40, 72, 108, 130]],
        [49, 242, 0, [48, 88, 132, 156]],
        [53, 292, 0, [60, 110, 160, 192]],
        [57, 346, 0, [72, 130, 192, 224]], //10
        [61, 404, 0, [80, 150, 224, 264]],
        [65, 466, 0, [96, 176, 260, 308]],
        [69, 532, 0, [104, 198, 288, 352]],
        [73, 581, 3, [120, 216, 320, 384]],
        [77, 655, 3, [132, 240, 360, 432]], //15
        [81, 733, 3, [144, 280, 408, 480]],
        [85, 815, 3, [168, 308, 448, 532]],
        [89, 901, 3, [180, 338, 504, 588]],
        [93, 991, 3, [196, 364, 546, 650]],
        [97, 1085, 3, [224, 416, 600, 700]], //20
        [101, 1156, 4, [224, 442, 644, 750]],
        [105, 1258, 4, [252, 476, 690, 816]],
        [109, 1364, 4, [270, 504, 750, 900]],
        [113, 1474, 4, [300, 560, 810, 960]],
        [117, 1588, 4, [312, 588, 870, 1050]], //25
        [121, 1706, 4, [336, 644, 952, 1110]],
        [125, 1828, 4, [360, 700, 1020, 1200]],
        [129, 1921, 3, [390, 728, 1050, 1260]],
        [133, 2051, 3, [420, 784, 1140, 1350]],
        [137, 2185, 3, [450, 812, 1200, 1440]], //30
        [141, 2323, 3, [480, 868, 1290, 1530]],
        [145, 2465, 3, [510, 924, 1350, 1620]],
        [149, 2611, 3, [540, 980, 1440, 1710]],
        [153, 2761, 3, [570, 1036, 1530, 1800]],
        [157, 2876, 0, [570, 1064, 1590, 1890]], //35
        [161, 3034, 0, [600, 1120, 1680, 1980]],
        [165, 3196, 0, [630, 1204, 1770, 2100]],
        [169, 3362, 0, [660, 1260, 1860, 2220]],
        [173, 3532, 0, [720, 1316, 1950, 2310]],
        [177, 3706, 0, [750, 1372, 2040, 2430]] //40
    ];

    public static $lengthTableBits = [
        [10, 12, 14],
        [9, 11, 13],
        [8, 16, 16],
        [8, 10, 12]
    ];

    public static $eccTable = [
        [[0, 0], [0, 0], [0, 0], [0, 0]],
        [[1, 0], [1, 0], [1, 0], [1, 0]], // 1
        [[1, 0], [1, 0], [1, 0], [1, 0]],
        [[1, 0], [1, 0], [2, 0], [2, 0]],
        [[1, 0], [2, 0], [2, 0], [4, 0]],
        [[1, 0], [2, 0], [2, 2], [2, 2]], // 5
        [[2, 0], [4, 0], [4, 0], [4, 0]],
        [[2, 0], [4, 0], [2, 4], [4, 1]],
        [[2, 0], [2, 2], [4, 2], [4, 2]],
        [[2, 0], [3, 2], [4, 4], [4, 4]],
        [[2, 2], [4, 1], [6, 2], [6, 2]], //10
        [[4, 0], [1, 4], [4, 4], [3, 8]],
        [[2, 2], [6, 2], [4, 6], [7, 4]],
        [[4, 0], [8, 1], [8, 4], [12, 4]],
        [[3, 1], [4, 5], [11, 5], [11, 5]],
        [[5, 1], [5, 5], [5, 7], [11, 7]], //15
        [[5, 1], [7, 3], [15, 2], [3, 13]],
        [[1, 5], [10, 1], [1, 15], [2, 17]],
        [[5, 1], [9, 4], [17, 1], [2, 19]],
        [[3, 4], [3, 11], [17, 4], [9, 16]],
        [[3, 5], [3, 13], [15, 5], [15, 10]], //20
        [[4, 4], [17, 0], [17, 6], [19, 6]],
        [[2, 7], [17, 0], [7, 16], [34, 0]],
        [[4, 5], [4, 14], [11, 14], [16, 14]],
        [[6, 4], [6, 14], [11, 16], [30, 2]],
        [[8, 4], [8, 13], [7, 22], [22, 13]], //25
        [[10, 2], [19, 4], [28, 6], [33, 4]],
        [[8, 4], [22, 3], [8, 26], [12, 28]],
        [[3, 10], [3, 23], [4, 31], [11, 31]],
        [[7, 7], [21, 7], [1, 37], [19, 26]],
        [[5, 10], [19, 10], [15, 25], [23, 25]], //30
        [[13, 3], [2, 29], [42, 1], [23, 28]],
        [[17, 0], [10, 23], [10, 35], [19, 35]],
        [[17, 1], [14, 21], [29, 19], [11, 46]],
        [[13, 6], [14, 23], [44, 7], [59, 1]],
        [[12, 7], [12, 26], [39, 14], [22, 41]], //35
        [[6, 14], [6, 34], [46, 10], [2, 64]],
        [[17, 4], [29, 14], [49, 10], [24, 46]],
        [[4, 18], [13, 32], [48, 14], [42, 32]],
        [[20, 4], [40, 7], [43, 22], [10, 67]],
        [[19, 6], [18, 31], [34, 34], [20, 61]], //40
    ];

    public static $alignmentPattern = [
        [0, 0],
        [0, 0],
        [18, 0],
        [22, 0],
        [26, 0],
        [30, 0], // 1- 5
        [34, 0],
        [22, 38],
        [24, 42],
        [26, 46],
        [28, 50], // 6-10
        [30, 54],
        [32, 58],
        [34, 62],
        [26, 46],
        [26, 48], //11-15
        [26, 50],
        [30, 54],
        [30, 56],
        [30, 58],
        [34, 62], //16-20
        [28, 50],
        [26, 50],
        [30, 54],
        [28, 54],
        [32, 58], //21-25
        [30, 58],
        [34, 62],
        [26, 50],
        [30, 54],
        [26, 52], //26-30
        [30, 56],
        [34, 60],
        [30, 58],
        [34, 62],
        [30, 54], //31-35
        [24, 50],
        [28, 54],
        [32, 58],
        [26, 54],
        [30, 58], //35-40
    ];

    public static $versionPattern = [
        0x07c94,
        0x085bc,
        0x09a99,
        0x0a4d3,
        0x0bbf6,
        0x0c762,
        0x0d847,
        0x0e60d,
        0x0f928,
        0x10b78,
        0x1145d,
        0x12a17,
        0x13532,
        0x149a6,
        0x15683,
        0x168c9,
        0x177ec,
        0x18ec4,
        0x191e1,
        0x1afab,
        0x1b08e,
        0x1cc1a,
        0x1d33f,
        0x1ed75,
        0x1f250,
        0x209d5,
        0x216f0,
        0x228ba,
        0x2379f,
        0x24b0b,
        0x2542e,
        0x26a64,
        0x27541,
        0x28c69
    ];

    public static $formatInfo = [
        [0x77c4, 0x72f3, 0x7daa, 0x789d, 0x662f, 0x6318, 0x6c41, 0x6976],
        [0x5412, 0x5125, 0x5e7c, 0x5b4b, 0x45f9, 0x40ce, 0x4f97, 0x4aa0],
        [0x355f, 0x3068, 0x3f31, 0x3a06, 0x24b4, 0x2183, 0x2eda, 0x2bed],
        [0x1689, 0x13be, 0x1ce7, 0x19d0, 0x0762, 0x0255, 0x0d0c, 0x083b]
    ];

    public static $frames = [];

    public static function getDataLength($version, $level)
    {
        return self::$capacity[$version][QRcodeEnum::QRCAP_WORDS] - self::$capacity[$version][QRcodeEnum::QRCAP_EC][$level];
    }

    public static function getECCLength($version, $level)
    {
        return self::$capacity[$version][QRcodeEnum::QRCAP_EC][$level];
    }

    public static function getWidth($version)
    {
        return self::$capacity[$version][QRcodeEnum::QRCAP_WIDTH];
    }

    public static function getRemainder($version)
    {
        return self::$capacity[$version][QRcodeEnum::QRCAP_REMINDER];
    }

    public static function getMinimumVersion($size, $level)
    {
        for ($i = 1; $i <= QRcodeEnum::QRSPEC_VERSION_MAX; $i++) {
            $words  = self::$capacity[$i][QRcodeEnum::QRCAP_WORDS] - self::$capacity[$i][QRcodeEnum::QRCAP_EC][$level];
            if ($words >= $size) {
                return $i;
            }
        }

        return -1;
    }

    public static function lengthIndicator($mode, $version)
    {
        if ($mode == QRcodeEnum::QR_MODE_STRUCTURE) {
            return 0;
        }

        if ($version <= 9) {
            $l = 0;
        } else if ($version <= 26) {
            $l = 1;
        } else {
            $l = 2;
        }

        return self::$lengthTableBits[$mode][$l];
    }

    public static function maximumWords($mode, $version)
    {
        if ($mode == QRcodeEnum::QR_MODE_STRUCTURE) {
            return 3;
        }

        if ($version <= 9) {
            $l = 0;
        } else if ($version <= 26) {
            $l = 1;
        } else {
            $l = 2;
        }

        $bits = self::$lengthTableBits[$mode][$l];
        $words = (1 << $bits) - 1;
        if ($mode == QRcodeEnum::QR_MODE_KANJI) {
            $words *= 2;
        }

        return $words;
    }

    public static function getEccSpec($version, $level, array &$spec)
    {
        if (count($spec) < 5) {
            $spec = [0, 0, 0, 0, 0];
        }

        $b1   = self::$eccTable[$version][$level][0];
        $b2   = self::$eccTable[$version][$level][1];
        $data = self::getDataLength($version, $level);
        $ecc  = self::getECCLength($version, $level);

        if ($b2 == 0) {
            $spec[0] = $b1;
            $spec[1] = (int)($data / $b1);
            $spec[2] = (int)($ecc / $b1);
            $spec[3] = 0;
            $spec[4] = 0;
        } else {
            $spec[0] = $b1;
            $spec[1] = (int)($data / ($b1 + $b2));
            $spec[2] = (int)($ecc  / ($b1 + $b2));
            $spec[3] = $b2;
            $spec[4] = $spec[1] + 1;
        }
    }

    public static function putAlignmentMarker(array &$frame, $ox, $oy)
    {
        $finder = [
            "\xa1\xa1\xa1\xa1\xa1",
            "\xa1\xa0\xa0\xa0\xa1",
            "\xa1\xa0\xa1\xa0\xa1",
            "\xa1\xa0\xa0\xa0\xa1",
            "\xa1\xa1\xa1\xa1\xa1"
        ];

        $yStart = $oy - 2;
        $xStart = $ox - 2;
        for ($y = 0; $y < 5; $y++) {
            QRstr::set($frame, $xStart, $yStart + $y, $finder[$y]);
        }
    }

    public static function putAlignmentPattern($version, &$frame, $width)
    {
        if ($version < 2) {
            return;
        }

        $d = self::$alignmentPattern[$version][1] - self::$alignmentPattern[$version][0];
        if ($d < 0) {
            $w = 2;
        } else {
            $w = (int)(($width - self::$alignmentPattern[$version][0]) / $d + 2);
        }

        if ($w * $w - 3 == 1) {
            $x = self::$alignmentPattern[$version][0];
            $y = self::$alignmentPattern[$version][0];
            self::putAlignmentMarker($frame, $x, $y);
            return;
        }

        $cx = self::$alignmentPattern[$version][0];
        for ($x = 1; $x < $w - 1; $x++) {
            self::putAlignmentMarker($frame, 6, $cx);
            self::putAlignmentMarker($frame, $cx,  6);
            $cx += $d;
        }

        $cy = self::$alignmentPattern[$version][0];
        for ($y = 0; $y < $w - 1; $y++) {
            $cx = self::$alignmentPattern[$version][0];
            for ($x = 0; $x < $w - 1; $x++) {
                self::putAlignmentMarker($frame, $cx, $cy);
                $cx += $d;
            }
            $cy += $d;
        }
    }

    public static function getVersionPattern($version)
    {
        if ($version < 7 || $version > QRcodeEnum::QRSPEC_VERSION_MAX) {
            return 0;
        }

        return self::$versionPattern[$version - 7];
    }

    public static function getFormatInfo($mask, $level)
    {
        if ($mask < 0 || $mask > 7) {
            return 0;
        }

        if ($level < 0 || $level > 3) {
            return 0;
        }

        return self::$formatInfo[$level][$mask];
    }

    public static function putFinderPattern(&$frame, $ox, $oy)
    {
        $finder = [
            "\xc1\xc1\xc1\xc1\xc1\xc1\xc1",
            "\xc1\xc0\xc0\xc0\xc0\xc0\xc1",
            "\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
            "\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
            "\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
            "\xc1\xc0\xc0\xc0\xc0\xc0\xc1",
            "\xc1\xc1\xc1\xc1\xc1\xc1\xc1"
        ];

        for ($y = 0; $y < 7; $y++) {
            QRstr::set($frame, $ox, $oy + $y, $finder[$y]);
        }
    }

    public static function createFrame($version)
    {
        $width = self::$capacity[$version][QRcodeEnum::QRCAP_WIDTH];
        $frameLine = str_repeat("\0", $width);
        $frame = array_fill(0, $width, $frameLine);

        self::putFinderPattern($frame, 0, 0);
        self::putFinderPattern($frame, $width - 7, 0);
        self::putFinderPattern($frame, 0, $width - 7);

        $yOffset = $width - 7;

        for ($y = 0; $y < 7; $y++) {
            $frame[$y][7] = "\xc0";
            $frame[$y][$width - 8] = "\xc0";
            $frame[$yOffset][7] = "\xc0";
            $yOffset++;
        }

        $setPattern = str_repeat("\xc0", 8);

        QRstr::set($frame, 0, 7, $setPattern);
        QRstr::set($frame, $width - 8, 7, $setPattern);
        QRstr::set($frame, 0, $width - 8, $setPattern);

        $setPattern = str_repeat("\x84", 9);
        QRstr::set($frame, 0, 8, $setPattern);
        QRstr::set($frame, $width - 8, 8, $setPattern, 8);

        $yOffset = $width - 8;

        for ($y = 0; $y < 8; $y++, $yOffset++) {
            $frame[$y][8] = "\x84";
            $frame[$yOffset][8] = "\x84";
        }

        for ($i = 1; $i < $width - 15; $i++) {
            $frame[6][7 + $i] = chr(0x90 | ($i & 1));
            $frame[7 + $i][6] = chr(0x90 | ($i & 1));
        }

        self::putAlignmentPattern($version, $frame, $width);

        if ($version >= 7) {
            $vinf = self::getVersionPattern($version);

            $v = $vinf;
            for ($x = 0; $x < 6; $x++) {
                for ($y = 0; $y < 3; $y++) {
                    $frame[($width - 11) + $y][$x] = chr(0x88 | ($v & 1));
                    $v = $v >> 1;
                }
            }

            $v = $vinf;
            for ($y = 0; $y < 6; $y++) {
                for ($x = 0; $x < 3; $x++) {
                    $frame[$y][$x + ($width - 11)] = chr(0x88 | ($v & 1));
                    $v = $v >> 1;
                }
            }
        }

        $frame[$width - 8][8] = "\x81";

        return $frame;
    }

    public static function serial($frame)
    {
        return gzcompress(implode("\n", $frame), 9);
    }

    public static function unserial($code)
    {
        return explode("\n", gzuncompress($code));
    }

    public static function newFrame($version)
    {
        if ($version < 1 || $version > QRcodeEnum::QRSPEC_VERSION_MAX) {
            return null;
        }

        if (!isset(self::$frames[$version])) {
            $fileName = QRcodeEnum::QR_CACHE_DIR . 'frame_' . $version . '.dat';
            if (QRcodeEnum::QR_CACHEABLE) {
                if (file_exists($fileName)) {
                    self::$frames[$version] = self::unserial(file_get_contents($fileName));
                } else {
                    self::$frames[$version] = self::createFrame($version);
                    file_put_contents($fileName, self::serial(self::$frames[$version]));
                }
            } else {
                self::$frames[$version] = self::createFrame($version);
            }
        }

        if (is_null(self::$frames[$version])) {
            return null;
        }

        return self::$frames[$version];
    }

    public static function rsBlockNum($spec)
    {
        return $spec[0] + $spec[3];
    }

    public static function rsBlockNum1($spec)
    {
        return $spec[0];
    }

    public static function rsDataCodes1($spec)
    {
        return $spec[1];
    }

    public static function rsEccCodes1($spec)
    {
        return $spec[2];
    }

    public static function rsBlockNum2($spec)
    {
        return $spec[3];
    }

    public static function rsDataCodes2($spec)
    {
        return $spec[4];
    }

    public static function rsEccCodes2($spec)
    {
        return $spec[2];
    }

    public static function rsDataLength($spec)
    {
        return ($spec[0] * $spec[1]) + ($spec[3] * $spec[4]);
    }

    public static function rsEccLength($spec)
    {
        return ($spec[0] + $spec[3]) * $spec[2];
    }
}

class FrameFiller
{
    public $width;

    public $frame;

    public $x;

    public $y;

    public $dir;

    public $bit;

    public function __construct($width, &$frame)
    {
        $this->width = $width;
        $this->frame = $frame;
        $this->x = $width - 1;
        $this->y = $width - 1;
        $this->dir = -1;
        $this->bit = -1;
    }

    public function setFrameAt($at, $val)
    {
        $this->frame[$at['y']][$at['x']] = chr($val);
    }

    public function getFrameAt($at)
    {
        return ord($this->frame[$at['y']][$at['x']]);
    }

    public function next()
    {
        do {
            if ($this->bit == -1) {
                $this->bit = 0;
                return array('x' => $this->x, 'y' => $this->y);
            }

            $x = $this->x;
            $y = $this->y;
            $w = $this->width;

            if ($this->bit == 0) {
                $x--;
                $this->bit++;
            } else {
                $x++;
                $y += $this->dir;
                $this->bit--;
            }

            if ($this->dir < 0) {
                if ($y < 0) {
                    $y = 0;
                    $x -= 2;
                    $this->dir = 1;
                    if ($x == 6) {
                        $x--;
                        $y = 9;
                    }
                }
            } else {
                if ($y == $w) {
                    $y = $w - 1;
                    $x -= 2;
                    $this->dir = -1;
                    if ($x == 6) {
                        $x--;
                        $y -= 8;
                    }
                }
            }
            if ($x < 0 || $y < 0) {
                return null;
            }

            $this->x = $x;
            $this->y = $y;
        } while (ord($this->frame[$y][$x]) & 0x80);

        return ['x' => $x, 'y' => $y];
    }
}

class QRmask
{
    public $runLength = [];

    public function __construct()
    {
        $this->runLength = array_fill(0, QRcodeEnum::QRSPEC_WIDTH_MAX + 1, 0);
    }

    public function writeFormatInformation($width, &$frame, $mask, $level)
    {
        $blacks = 0;
        $format =  QRspec::getFormatInfo($mask, $level);

        for ($i = 0; $i < 8; $i++) {
            if ($format & 1) {
                $blacks += 2;
                $v = 0x85;
            } else {
                $v = 0x84;
            }

            $frame[8][$width - 1 - $i] = chr($v);
            if ($i < 6) {
                $frame[$i][8] = chr($v);
            } else {
                $frame[$i + 1][8] = chr($v);
            }
            $format = $format >> 1;
        }

        for ($i = 0; $i < 7; $i++) {
            if ($format & 1) {
                $blacks += 2;
                $v = 0x85;
            } else {
                $v = 0x84;
            }

            $frame[$width - 7 + $i][8] = chr($v);
            if ($i == 0) {
                $frame[8][7] = chr($v);
            } else {
                $frame[8][6 - $i] = chr($v);
            }

            $format = $format >> 1;
        }

        return $blacks;
    }

    public function mask0($x, $y)
    {
        return ($x + $y) & 1;
    }

    public function mask1($x, $y)
    {
        return ($y & 1);
    }

    public function mask2($x, $y)
    {
        return ($x % 3);
    }

    public function mask3($x, $y)
    {
        return ($x + $y) % 3;
    }

    public function mask4($x, $y)
    {
        return (((int)($y / 2)) + ((int)($x / 3))) & 1;
    }

    public function mask5($x, $y)
    {
        return (($x * $y) & 1) + ($x * $y) % 3;
    }

    public function mask6($x, $y)
    {
        return ((($x * $y) & 1) + ($x * $y) % 3) & 1;
    }

    public function mask7($x, $y)
    {
        return ((($x * $y) % 3) + (($x + $y) & 1)) & 1;
    }

    private function generateMaskNo($maskNo, $width, $frame)
    {
        $bitMask = array_fill(0, $width, array_fill(0, $width, 0));
        for ($y = 0; $y < $width; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (ord($frame[$y][$x]) & 0x80) {
                    $bitMask[$y][$x] = 0;
                } else {
                    $maskFunc = call_user_func([$this, 'mask' . $maskNo], $x, $y);
                    $bitMask[$y][$x] = ($maskFunc == 0) ? 1 : 0;
                }
            }
        }

        return $bitMask;
    }

    public static function serial($bitFrame)
    {
        $codeArr = [];
        foreach ($bitFrame as $line) {
            $codeArr[] = implode('', $line);
        }

        return gzcompress(implode("\n", $codeArr), 9);
    }

    public static function unserial($code)
    {
        $codeArr = [];
        $codeLines = explode("\n", gzuncompress($code));
        foreach ($codeLines as $line) {
            $codeArr[] = str_split($line);
        }

        return $codeArr;
    }

    public function makeMaskNo($maskNo, $width, $s, &$d, $maskGenOnly = false)
    {
        $b = 0;
        $bitMask = [];
        $fileName = QRcodeEnum::QR_CACHE_DIR . 'mask_' . $maskNo . DIRECTORY_SEPARATOR . 'mask_' . $width . '_' . $maskNo . '.dat';

        if (QRcodeEnum::QR_CACHEABLE) {
            if (file_exists($fileName)) {
                $bitMask = self::unserial(file_get_contents($fileName));
            } else {
                $bitMask = $this->generateMaskNo($maskNo, $width, $s, $d);
                if (!file_exists(QRcodeEnum::QR_CACHE_DIR . 'mask_' . $maskNo)) {
                    mkdir(QRcodeEnum::QR_CACHE_DIR . 'mask_' . $maskNo);
                }
                file_put_contents($fileName, self::serial($bitMask));
            }
        } else {
            $bitMask = $this->generateMaskNo($maskNo, $width, $s, $d);
        }

        if ($maskGenOnly) {
            return;
        }

        $d = $s;
        for ($y = 0; $y < $width; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($bitMask[$y][$x] == 1) {
                    $d[$y][$x] = chr(ord($s[$y][$x]) ^ (int)$bitMask[$y][$x]);
                }
                $b += (int)(ord($d[$y][$x]) & 1);
            }
        }

        return $b;
    }

    public function makeMask($width, $frame, $maskNo, $level)
    {
        $masked = array_fill(0, $width, str_repeat("\0", $width));
        $this->makeMaskNo($maskNo, $width, $frame, $masked);
        $this->writeFormatInformation($width, $masked, $maskNo, $level);

        return $masked;
    }

    public function calcN1N3($length)
    {
        $demerit = 0;
        for ($i = 0; $i < $length; $i++) {
            if ($this->runLength[$i] >= 5) {
                $demerit += (QRcodeEnum::N1 + ($this->runLength[$i] - 5));
            }
            if ($i & 1) {
                if (($i >= 3) && ($i < ($length - 2)) && ($this->runLength[$i] % 3 == 0)) {
                    $fact = (int)($this->runLength[$i] / 3);
                    if (($this->runLength[$i - 2] == $fact) && ($this->runLength[$i - 1] == $fact) && ($this->runLength[$i + 1] == $fact) && ($this->runLength[$i + 2] == $fact)) {
                        if (($this->runLength[$i - 3] < 0) || ($this->runLength[$i - 3] >= (4 * $fact))) {
                            $demerit += QRcodeEnum::N3;
                        } else if ((($i + 3) >= $length) || ($this->runLength[$i + 3] >= (4 * $fact))) {
                            $demerit += QRcodeEnum::N3;
                        }
                    }
                }
            }
        }
        return $demerit;
    }

    public function evaluateSymbol($width, $frame)
    {
        $head = 0;
        $demerit = 0;
        for ($y = 0; $y < $width; $y++) {
            $head = 0;
            $this->runLength[0] = 1;
            $frameY = $frame[$y];

            if ($y > 0) {
                $frameYM = $frame[$y - 1];
            }
            for ($x = 0; $x < $width; $x++) {
                if (($x > 0) && ($y > 0)) {
                    $b22 = ord($frameY[$x]) & ord($frameY[$x - 1]) & ord($frameYM[$x]) & ord($frameYM[$x - 1]);
                    $w22 = ord($frameY[$x]) | ord($frameY[$x - 1]) | ord($frameYM[$x]) | ord($frameYM[$x - 1]);
                    if (($b22 | ($w22 ^ 1)) & 1) {
                        $demerit += QRcodeEnum::N2;
                    }
                }
                if (($x == 0) && (ord($frameY[$x]) & 1)) {
                    $this->runLength[0] = -1;
                    $head = 1;
                    $this->runLength[$head] = 1;
                } else if ($x > 0) {
                    if ((ord($frameY[$x]) ^ ord($frameY[$x - 1])) & 1) {
                        $head++;
                        $this->runLength[$head] = 1;
                    } else {
                        $this->runLength[$head]++;
                    }
                }
            }

            $demerit += $this->calcN1N3($head + 1);
        }

        for ($x = 0; $x < $width; $x++) {
            $head = 0;
            $this->runLength[0] = 1;
            for ($y = 0; $y < $width; $y++) {
                if ($y == 0 && (ord($frame[$y][$x]) & 1)) {
                    $this->runLength[0] = -1;
                    $head = 1;
                    $this->runLength[$head] = 1;
                } else if ($y > 0) {
                    if ((ord($frame[$y][$x]) ^ ord($frame[$y - 1][$x])) & 1) {
                        $head++;
                        $this->runLength[$head] = 1;
                    } else {
                        $this->runLength[$head]++;
                    }
                }
            }

            $demerit += $this->calcN1N3($head + 1);
        }

        return $demerit;
    }

    public function mask($width, $frame, $level)
    {
        $minDemerit = PHP_INT_MAX;
        $bestMask = [];

        $checked_masks = [0, 1, 2, 3, 4, 5, 6, 7];

        if (QRcodeEnum::QR_FIND_FROM_RANDOM !== false) {

            $howManuOut = 8 - (QRcodeEnum::QR_FIND_FROM_RANDOM % 9);
            for ($i = 0; $i <  $howManuOut; $i++) {
                $remPos = rand(0, count($checked_masks) - 1);
                unset($checked_masks[$remPos]);
                $checked_masks = array_values($checked_masks);
            }
        }

        $bestMask = $frame;
        foreach ($checked_masks as $i) {
            $mask = array_fill(0, $width, str_repeat("\0", $width));
            $demerit = 0;
            $blacks = 0;
            $blacks  = $this->makeMaskNo($i, $width, $frame, $mask);
            $blacks += $this->writeFormatInformation($width, $mask, $i, $level);
            $blacks  = (int)(100 * $blacks / ($width * $width));
            $demerit = (int)((int)(abs($blacks - 50) / 5) * QRcodeEnum::N4);
            $demerit += $this->evaluateSymbol($width, $mask);

            if ($demerit < $minDemerit) {
                $minDemerit = $demerit;
                $bestMask = $mask;
                $bestMaskNum = $i;
            }
        }

        return $bestMask;
    }
}

class QRsplit
{
    public $dataStr = '';

    public $input;

    public $modeHint;

    public function __construct($dataStr, $input, $modeHint)
    {
        $this->dataStr  = $dataStr;
        $this->input    = $input;
        $this->modeHint = $modeHint;
    }

    public static function isdigitat($str, $pos)
    {
        if ($pos >= strlen($str)) {
            return false;
        }

        return ((ord($str[$pos]) >= ord('0')) && (ord($str[$pos]) <= ord('9')));
    }

    public static function isalnumat($str, $pos)
    {
        if ($pos >= strlen($str)) {
            return false;
        }

        return (QRinput::lookAnTable(ord($str[$pos])) >= 0);
    }

    public function identifyMode($pos)
    {
        if ($pos >= strlen($this->dataStr)) {
            return QRcodeEnum::QR_MODE_NUL;
        }

        $c = $this->dataStr[$pos];

        if (self::isdigitat($this->dataStr, $pos)) {
            return QRcodeEnum::QR_MODE_NUM;
        } else if (self::isalnumat($this->dataStr, $pos)) {
            return QRcodeEnum::QR_MODE_AN;
        } else if ($this->modeHint == QRcodeEnum::QR_MODE_KANJI) {
            if ($pos + 1 < strlen($this->dataStr)) {
                $d = $this->dataStr[$pos + 1];
                $word = (ord($c) << 8) | ord($d);
                if (($word >= 0x8140 && $word <= 0x9ffc) || ($word >= 0xe040 && $word <= 0xebbf)) {
                    return QRcodeEnum::QR_MODE_KANJI;
                }
            }
        }

        return QRcodeEnum::QR_MODE_8;
    }

    public function eatNum()
    {
        $ln = QRspec::lengthIndicator(QRcodeEnum::QR_MODE_NUM, $this->input->getVersion());
        $p = 0;
        while (self::isdigitat($this->dataStr, $p)) {
            $p++;
        }

        $run = $p;
        $mode = $this->identifyMode($p);
        if ($mode == QRcodeEnum::QR_MODE_8) {
            $dif = QRinput::estimateBitsModeNum($run) + 4 + $ln + QRinput::estimateBitsMode8(1) - QRinput::estimateBitsMode8($run + 1);
            if ($dif > 0) {
                return $this->eat8();
            }
        }
        if ($mode == QRcodeEnum::QR_MODE_AN) {
            $dif = QRinput::estimateBitsModeNum($run) + 4 + $ln + QRinput::estimateBitsModeAn(1) - QRinput::estimateBitsModeAn($run + 1);
            if ($dif > 0) {
                return $this->eatAn();
            }
        }

        $ret = $this->input->append(QRcodeEnum::QR_MODE_NUM, $run, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }

    public function eatAn()
    {
        $la = QRspec::lengthIndicator(QRcodeEnum::QR_MODE_AN,  $this->input->getVersion());
        $ln = QRspec::lengthIndicator(QRcodeEnum::QR_MODE_NUM, $this->input->getVersion());
        $p = 0;

        while (self::isalnumat($this->dataStr, $p)) {
            if (self::isdigitat($this->dataStr, $p)) {
                $q = $p;
                while (self::isdigitat($this->dataStr, $q)) {
                    $q++;
                }

                $dif = QRinput::estimateBitsModeAn($p) + QRinput::estimateBitsModeNum($q - $p) + 4 + $ln - QRinput::estimateBitsModeAn($q);

                if ($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }
            } else {
                $p++;
            }
        }

        $run = $p;
        if (!self::isalnumat($this->dataStr, $p)) {
            $dif = QRinput::estimateBitsModeAn($run) + 4 + $la + QRinput::estimateBitsMode8(1) - QRinput::estimateBitsMode8($run + 1);
            if ($dif > 0) {
                return $this->eat8();
            }
        }

        $ret = $this->input->append(QRcodeEnum::QR_MODE_AN, $run, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }

    public function eatKanji()
    {
        $p = 0;
        $run = null;
        while ($this->identifyMode($p) == QRcodeEnum::QR_MODE_KANJI) {
            $p += 2;
        }

        $ret = $this->input->append(QRcodeEnum::QR_MODE_KANJI, $p, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }

    public function eat8()
    {
        $la = QRspec::lengthIndicator(QRcodeEnum::QR_MODE_AN, $this->input->getVersion());
        $ln = QRspec::lengthIndicator(QRcodeEnum::QR_MODE_NUM, $this->input->getVersion());
        $p = 1;
        $dataStrLen = strlen($this->dataStr);

        while ($p < $dataStrLen) {
            $mode = $this->identifyMode($p);
            if ($mode == QRcodeEnum::QR_MODE_KANJI) {
                break;
            }
            if ($mode == QRcodeEnum::QR_MODE_NUM) {
                $q = $p;
                while (self::isdigitat($this->dataStr, $q)) {
                    $q++;
                }
                $dif = QRinput::estimateBitsMode8($p) + QRinput::estimateBitsModeNum($q - $p) + 4 + $ln - QRinput::estimateBitsMode8($q);
                if ($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }
            } else if ($mode == QRcodeEnum::QR_MODE_AN) {
                $q = $p;
                while (self::isalnumat($this->dataStr, $q)) {
                    $q++;
                }
                $dif = QRinput::estimateBitsMode8($p) + QRinput::estimateBitsModeAn($q - $p) + 4 + $la - QRinput::estimateBitsMode8($q);
                if ($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }
            } else {
                $p++;
            }
        }

        $run = $p;
        $ret = $this->input->append(QRcodeEnum::QR_MODE_8, $run, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }

    public function splitString()
    {
        while (strlen($this->dataStr) > 0) {
            if ($this->dataStr == '') {
                return 0;
            }
            $hint = null;
            $mode = $this->identifyMode(0);
            switch ($mode) {
                case QRcodeEnum::QR_MODE_NUM:
                    $length = $this->eatNum();
                    break;
                case QRcodeEnum::QR_MODE_AN:
                    $length = $this->eatAn();
                    break;
                case QRcodeEnum::QR_MODE_KANJI:
                    if ($hint == QRcodeEnum::QR_MODE_KANJI) {
                        $length = $this->eatKanji();
                    } else {
                        $length = $this->eat8();
                    }
                    break;
                default:
                    $length = $this->eat8();
                    break;
            }

            if ($length == 0) {
                return 0;
            }
            if ($length < 0) {
                return -1;
            }

            $this->dataStr = substr($this->dataStr, $length);
        }
    }

    public function toUpper()
    {
        $stringLen = strlen($this->dataStr);
        $p = 0;
        while ($p < $stringLen) {
            $mode = $this->identifyMode(substr($this->dataStr, $p), $this->modeHint);
            if ($mode == QRcodeEnum::QR_MODE_KANJI) {
                $p += 2;
            } else {
                if (ord($this->dataStr[$p]) >= ord('a') && ord($this->dataStr[$p]) <= ord('z')) {
                    $this->dataStr[$p] = chr(ord($this->dataStr[$p]) - 32);
                }
                $p++;
            }
        }

        return $this->dataStr;
    }

    public static function splitStringToQRinput($string, QRinput $input, $modeHint, $casesensitive = true)
    {
        if (is_null($string) || $string == '\0' || $string == '') {
            throw new QRcodeException('empty string!!!');
        }

        $split = new QRsplit($string, $input, $modeHint);
        if (!$casesensitive) {
            $split->toUpper();
        }

        return $split->splitString();
    }
}

class QRinputItem
{
    public $mode;

    public $size;

    public $data;

    public $bstream;

    public function __construct($mode, $size, $data, $bstream = null)
    {
        $setData = array_slice($data, 0, $size);
        if (count($setData) < $size) {
            $setData = array_merge($setData, array_fill(0, $size - count($setData), 0));
        }

        if (!QRinput::check($mode, $size, $setData)) {
            throw new QRcodeException('Error m:' . $mode . ',s:' . $size . ',d:' . join(',', $setData));
        }

        $this->mode = $mode;
        $this->size = $size;
        $this->data = $setData;
        $this->bstream = $bstream;
    }

    public function encodeModeNum($version)
    {
        try {

            $words = (int)($this->size / 3);
            $bs = new QRbitstream();
            $val = 0x1;
            $bs->appendNum(4, $val);
            $bs->appendNum(QRspec::lengthIndicator(QRcodeEnum::QR_MODE_NUM, $version), $this->size);

            for ($i = 0; $i < $words; $i++) {
                $val = (ord($this->data[$i * 3]) - ord('0')) * 100;
                $val += (ord($this->data[$i * 3 + 1]) - ord('0')) * 10;
                $val += (ord($this->data[$i * 3 + 2]) - ord('0'));
                $bs->appendNum(10, $val);
            }

            if ($this->size - $words * 3 == 1) {
                $val = ord($this->data[$words * 3]) - ord('0');
                $bs->appendNum(4, $val);
            } else if ($this->size - $words * 3 == 2) {
                $val = (ord($this->data[$words * 3]) - ord('0')) * 10;
                $val += (ord($this->data[$words * 3 + 1]) - ord('0'));
                $bs->appendNum(7, $val);
            }

            $this->bstream = $bs;
            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function encodeModeAn($version)
    {
        try {
            $words = (int)($this->size / 2);
            $bs = new QRbitstream();

            $bs->appendNum(4, 0x02);
            $bs->appendNum(QRspec::lengthIndicator(QRcodeEnum::QR_MODE_AN, $version), $this->size);

            for ($i = 0; $i < $words; $i++) {
                $val  = (int)QRinput::lookAnTable(ord($this->data[$i * 2])) * 45;
                $val += (int)QRinput::lookAnTable(ord($this->data[$i * 2 + 1]));

                $bs->appendNum(11, $val);
            }

            if ($this->size & 1) {
                $val = QRinput::lookAnTable(ord($this->data[$words * 2]));
                $bs->appendNum(6, $val);
            }

            $this->bstream = $bs;
            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function encodeMode8($version)
    {
        try {
            $bs = new QRbitstream();
            $bs->appendNum(4, 0x4);
            $bs->appendNum(QRspec::lengthIndicator(QRcodeEnum::QR_MODE_8, $version), $this->size);
            for ($i = 0; $i < $this->size; $i++) {
                $bs->appendNum(8, ord($this->data[$i]));
            }

            $this->bstream = $bs;
            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function encodeModeKanji($version)
    {
        try {

            $bs = new QRbitstream();
            $bs->appendNum(4, 0x8);
            $bs->appendNum(QRspec::lengthIndicator(QRcodeEnum::QR_MODE_KANJI, $version), (int)($this->size / 2));
            for ($i = 0; $i < $this->size; $i += 2) {
                $val = (ord($this->data[$i]) << 8) | ord($this->data[$i + 1]);
                if ($val <= 0x9ffc) {
                    $val -= 0x8140;
                } else {
                    $val -= 0xc140;
                }

                $h = ($val >> 8) * 0xc0;
                $val = ($val & 0xff) + $h;

                $bs->appendNum(13, $val);
            }

            $this->bstream = $bs;
            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function encodeModeStructure()
    {
        try {
            $bs =  new QRbitstream();
            $bs->appendNum(4, 0x03);
            $bs->appendNum(4, ord($this->data[1]) - 1);
            $bs->appendNum(4, ord($this->data[0]) - 1);
            $bs->appendNum(8, ord($this->data[2]));

            $this->bstream = $bs;
            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function estimateBitStreamSizeOfEntry($version)
    {
        $bits = 0;
        if ($version == 0) {
            $version = 1;
        }

        switch ($this->mode) {
            case QRcodeEnum::QR_MODE_NUM:
                $bits = QRinput::estimateBitsModeNum($this->size);
                break;
            case QRcodeEnum::QR_MODE_AN:
                $bits = QRinput::estimateBitsModeAn($this->size);
                break;
            case QRcodeEnum::QR_MODE_8:
                $bits = QRinput::estimateBitsMode8($this->size);
                break;
            case QRcodeEnum::QR_MODE_KANJI:
                $bits = QRinput::estimateBitsModeKanji($this->size);
                break;
            case QRcodeEnum::QR_MODE_STRUCTURE:
                return QRcodeEnum::STRUCTURE_HEADER_BITS;
            default:
                return 0;
        }

        $l = QRspec::lengthIndicator($this->mode, $version);
        $m = 1 << $l;
        $num = (int)(($this->size + $m - 1) / $m);
        $bits += $num * (4 + $l);

        return $bits;
    }

    public function encodeBitStream($version)
    {
        try {
            unset($this->bstream);
            $words = QRspec::maximumWords($this->mode, $version);
            if ($this->size > $words) {
                $st1 = new QRinputItem($this->mode, $words, $this->data);
                $st2 = new QRinputItem($this->mode, $this->size - $words, array_slice($this->data, $words));

                $st1->encodeBitStream($version);
                $st2->encodeBitStream($version);

                $this->bstream = new QRbitstream();
                $this->bstream->append($st1->bstream);
                $this->bstream->append($st2->bstream);

                unset($st1);
                unset($st2);
            } else {
                $ret = 0;
                switch ($this->mode) {
                    case QRcodeEnum::QR_MODE_NUM:
                        $ret = $this->encodeModeNum($version);
                        break;
                    case QRcodeEnum::QR_MODE_AN:
                        $ret = $this->encodeModeAn($version);
                        break;
                    case QRcodeEnum::QR_MODE_8:
                        $ret = $this->encodeMode8($version);
                        break;
                    case QRcodeEnum::QR_MODE_KANJI:
                        $ret = $this->encodeModeKanji($version);
                        break;
                    case QRcodeEnum::QR_MODE_STRUCTURE:
                        $ret = $this->encodeModeStructure();
                        break;
                    default:
                        break;
                }

                if ($ret < 0) {
                    return -1;
                }
            }

            return $this->bstream->size();
        } catch (Exception $e) {
            return -1;
        }
    }
};

class QRbitstream
{
    public $data = [];

    public function size()
    {
        return count($this->data);
    }

    public function allocate($setLength)
    {
        $this->data = array_fill(0, $setLength, 0);
        return 0;
    }

    public static function newFromNum($bits, $num)
    {
        $bstream = new QRbitstream();
        $bstream->allocate($bits);

        $mask = 1 << ($bits - 1);
        for ($i = 0; $i < $bits; $i++) {
            if ($num & $mask) {
                $bstream->data[$i] = 1;
            } else {
                $bstream->data[$i] = 0;
            }
            $mask = $mask >> 1;
        }

        return $bstream;
    }

    public static function newFromBytes($size, $data)
    {
        $bstream = new QRbitstream();
        $bstream->allocate($size * 8);
        $p = 0;

        for ($i = 0; $i < $size; $i++) {
            $mask = 0x80;
            for ($j = 0; $j < 8; $j++) {
                if ($data[$i] & $mask) {
                    $bstream->data[$p] = 1;
                } else {
                    $bstream->data[$p] = 0;
                }
                $p++;
                $mask = $mask >> 1;
            }
        }

        return $bstream;
    }

    public function append(QRbitstream $arg)
    {
        if (is_null($arg)) {
            return -1;
        }

        if ($arg->size() == 0) {
            return 0;
        }

        if ($this->size() == 0) {
            $this->data = $arg->data;
            return 0;
        }

        $this->data = array_values(array_merge($this->data, $arg->data));

        return 0;
    }

    public function appendNum($bits, $num)
    {
        if ($bits == 0) {
            return 0;
        }

        $b = QRbitstream::newFromNum($bits, $num);
        if (is_null($b)) {
            return -1;
        }

        $ret = $this->append($b);
        unset($b);

        return $ret;
    }

    public function appendBytes($size, $data)
    {
        if ($size == 0) {
            return 0;
        }

        $b = QRbitstream::newFromBytes($size, $data);
        if (is_null($b)) {
            return -1;
        }

        $ret = $this->append($b);
        unset($b);

        return $ret;
    }

    public function toByte()
    {
        $size = $this->size();
        if ($size == 0) {
            return array();
        }

        $data = array_fill(0, (int)(($size + 7) / 8), 0);
        $bytes = (int)($size / 8);
        $p = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $v = 0;
            for ($j = 0; $j < 8; $j++) {
                $v = $v << 1;
                $v |= $this->data[$p];
                $p++;
            }
            $data[$i] = $v;
        }

        if ($size & 7) {
            $v = 0;
            for ($j = 0; $j < ($size & 7); $j++) {
                $v = $v << 1;
                $v |= $this->data[$p];
                $p++;
            }
            $data[$bytes] = $v;
        }

        return $data;
    }
}

class QRrsItem
{
    public $mm;

    public $nn;

    public $alpha_to = [];

    public $index_of = [];

    public $genpoly = [];

    public $nroots;

    public $fcr;

    public $prim;

    public $iprim;

    public $pad;

    public $gfpoly;

    public function modnn($x)
    {
        while ($x >= $this->nn) {
            $x -= $this->nn;
            $x = ($x >> $this->mm) + ($x & $this->nn);
        }

        return $x;
    }

    public static function init_rs_char($symsize, $gfpoly, $fcr, $prim, $nroots, $pad)
    {
        $rs = null;
        if (($symsize < 0 || $symsize > 8) || ($fcr < 0 || $fcr >= (1 << $symsize)) || ($prim <= 0 || $prim >= (1 << $symsize)) || ($nroots < 0 || $nroots >= (1 << $symsize)) || ($pad < 0 || $pad >= ((1 << $symsize) - 1 - $nroots))) {
            return $rs;
        }

        $rs = new QRrsItem();
        $rs->mm = $symsize;
        $rs->nn = (1 << $symsize) - 1;
        $rs->pad = $pad;
        $rs->alpha_to = array_fill(0, $rs->nn + 1, 0);
        $rs->index_of = array_fill(0, $rs->nn + 1, 0);

        $NN = &$rs->nn;
        $A0 = &$NN;

        $rs->index_of[0] = $A0;
        $rs->alpha_to[$A0] = 0;
        $sr = 1;
        for ($i = 0; $i < $rs->nn; $i++) {
            $rs->index_of[$sr] = $i;
            $rs->alpha_to[$i] = $sr;
            $sr <<= 1;
            if ($sr & (1 << $symsize)) {
                $sr ^= $gfpoly;
            }
            $sr &= $rs->nn;
        }

        if ($sr != 1) {
            $rs = NULL;
            return $rs;
        }

        $rs->genpoly = array_fill(0, $nroots + 1, 0);
        $rs->fcr = $fcr;
        $rs->prim = $prim;
        $rs->nroots = $nroots;
        $rs->gfpoly = $gfpoly;

        for ($iprim = 1; ($iprim % $prim) != 0; $iprim += $rs->nn);

        $rs->iprim = (int)($iprim / $prim);
        $rs->genpoly[0] = 1;
        for ($i = 0, $root = $fcr * $prim; $i < $nroots; $i++, $root += $prim) {
            $rs->genpoly[$i + 1] = 1;
            for ($j = $i; $j > 0; $j--) {
                if ($rs->genpoly[$j] != 0) {
                    $rs->genpoly[$j] = $rs->genpoly[$j - 1] ^ $rs->alpha_to[$rs->modnn($rs->index_of[$rs->genpoly[$j]] + $root)];
                } else {
                    $rs->genpoly[$j] = $rs->genpoly[$j - 1];
                }
            }
            $rs->genpoly[0] = $rs->alpha_to[$rs->modnn($rs->index_of[$rs->genpoly[0]] + $root)];
        }

        for ($i = 0; $i <= $nroots; $i++) {
            $rs->genpoly[$i] = $rs->index_of[$rs->genpoly[$i]];
        }

        return $rs;
    }

    public function encode_rs_char($data, &$parity)
    {
        $NN       = &$this->nn;
        $ALPHA_TO = &$this->alpha_to;
        $INDEX_OF = &$this->index_of;
        $GENPOLY  = &$this->genpoly;
        $NROOTS   = &$this->nroots;
        $PAD      = &$this->pad;
        $A0       = &$NN;
        $parity = array_fill(0, $NROOTS, 0);
        for ($i = 0; $i < ($NN - $NROOTS - $PAD); $i++) {
            $feedback = $INDEX_OF[$data[$i] ^ $parity[0]];
            if ($feedback != $A0) {
                $feedback = $this->modnn($NN - $GENPOLY[$NROOTS] + $feedback);
                for ($j = 1; $j < $NROOTS; $j++) {
                    $parity[$j] ^= $ALPHA_TO[$this->modnn($feedback + $GENPOLY[$NROOTS - $j])];
                }
            }

            array_shift($parity);
            if ($feedback != $A0) {
                array_push($parity, $ALPHA_TO[$this->modnn($feedback + $GENPOLY[0])]);
            } else {
                array_push($parity, 0);
            }
        }
    }
}

class QRrs
{
    public static $items = [];

    public static function init_rs($symsize, $gfpoly, $fcr, $prim, $nroots, $pad)
    {
        foreach (self::$items as $rs) {
            if (($rs->pad != $pad) || ($rs->nroots != $nroots) || ($rs->mm != $symsize) || ($rs->gfpoly != $gfpoly) || ($rs->fcr != $fcr) || ($rs->prim != $prim)) {
                continue;
            }

            return $rs;
        }

        $rs = QRrsItem::init_rs_char($symsize, $gfpoly, $fcr, $prim, $nroots, $pad);
        array_unshift(self::$items, $rs);

        return $rs;
    }
}

class QRrsblock
{
    public $dataLength;

    public $data = [];

    public $eccLength;

    public $ecc = [];

    public function __construct($dl, $data, $el, &$ecc, QRrsItem $rs)
    {
        $rs->encode_rs_char($data, $ecc);

        $this->dataLength = $dl;
        $this->data = $data;
        $this->eccLength = $el;
        $this->ecc = $ecc;
    }
};

class QRimage
{
    /**
     * 获取二维码png图片资源
     *
     * @param mixed $frame
     * @param integer $pixelPerPoint
     * @param integer $outerFrame
     * @return string
     */
    public static function png($frame, $pixelPerPoint = 4, $outerFrame = 4)
    {
        // 获取图像资源
        $image = self::image($frame, $pixelPerPoint, $outerFrame);
        // 获取输出图像
        ob_start();
        imagepng($image);
        $img = ob_get_clean();
        imagedestroy($image);

        return $img;
    }

    /**
     * 生成二维码图像
     *
     * @param mixed $frame
     * @param integer $pixelPerPoint
     * @param integer $outerFrame
     * @return mixed
     */
    private static function image($frame, $pixelPerPoint = 4, $outerFrame = 4)
    {
        $h = count($frame);
        $w = strlen($frame[0]);
        $imgW = $w + 2 * $outerFrame;
        $imgH = $h + 2 * $outerFrame;
        $base_image = imagecreate($imgW, $imgH);
        $col[0] = imagecolorallocate($base_image, 255, 255, 255);
        $col[1] = imagecolorallocate($base_image, 0, 0, 0);
        imagefill($base_image, 0, 0, $col[0]);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($frame[$y][$x] == '1') {
                    imagesetpixel($base_image, $x + $outerFrame, $y + $outerFrame, $col[1]);
                }
            }
        }

        $target_image = imagecreate($imgW * $pixelPerPoint, $imgH * $pixelPerPoint);
        imagecopyresized($target_image, $base_image, 0, 0, 0, 0, $imgW * $pixelPerPoint, $imgH * $pixelPerPoint, $imgW, $imgH);
        imagedestroy($base_image);

        return $target_image;
    }
}

class QRencode
{
    public $casesensitive = true;

    public $eightbit = false;

    public $version = 0;

    public $size = 3;

    public $margin = 4;

    public $structured = 0;

    public $level = QRcodeEnum::QR_ECLEVEL_L;

    public $hint = QRcodeEnum::QR_MODE_8;

    public static function factory($level = QRcodeEnum::QR_ECLEVEL_L, $size = 12, $margin = 1)
    {
        $enc = new QRencode();
        $enc->size = $size;
        $enc->margin = $margin;
        switch ($level . '') {
            case '0':
            case '1':
            case '2':
            case '3':
                $enc->level = $level;
                break;
            case 'l':
            case 'L':
                $enc->level = QRcodeEnum::QR_ECLEVEL_L;
                break;
            case 'm':
            case 'M':
                $enc->level = QRcodeEnum::QR_ECLEVEL_M;
                break;
            case 'q':
            case 'Q':
                $enc->level = QRcodeEnum::QR_ECLEVEL_Q;
                break;
            case 'h':
            case 'H':
                $enc->level = QRcodeEnum::QR_ECLEVEL_H;
                break;
        }

        return $enc;
    }

    public function encodeRAW($intext)
    {
        $code = new QRcode();
        if ($this->eightbit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString($intext, $this->version, $this->level, $this->hint, $this->casesensitive);
        }

        return $code->data;
    }

    public function encode($intext)
    {
        $code = new QRcode();
        if ($this->eightbit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString($intext, $this->version, $this->level, $this->hint, $this->casesensitive);
        }

        return QRtools::binarize($code->data);
    }

    /**
     * 获取二维码PNG图像资源
     *
     * @param mixed $intext
     * @return string
     */
    public function encodePNG($intext)
    {
        try {
            ob_start();
            $tab = $this->encode($intext);
            // $err = ob_get_contents();
            ob_end_clean();
            $maxSize = (int)(QRcodeEnum::QR_PNG_MAXIMUM_SIZE / (count($tab) + 2 * $this->margin));

            return QRimage::png($tab, min(max(1, $this->size), $maxSize), $this->margin);
        } catch (Exception $e) {
            throw new QRcodeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
