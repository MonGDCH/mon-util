<?php

namespace mon\util;

/**
 * HTML工具类
 * 
 * @author monLam <985558837@qq.com>
 * @version 1.0.0
 */
class Html
{
    /**
     * 压缩 HTML 内容，去除多余空格、换行符、注释等
     *
     * @param string $html_source HTML 内容
     * @return string 压缩后的 HTML 内容
     */
    public static function compressHtml(string $html_source): string
    {
        $chunks   = preg_split('/(<!--<nocompress>-->.*?<!--<\/nocompress>-->|<nocompress>.*?<\/nocompress>|<pre.*?\/pre>|<textarea.*?\/textarea>|<script.*?\/script>)/msi', $html_source, -1, PREG_SPLIT_DELIM_CAPTURE);
        $compress = '';
        foreach ($chunks as $c) {
            if (strtolower(substr($c, 0, 19)) == '<!--<nocompress>-->') {
                $c        = substr($c, 19, strlen($c) - 19 - 20);
                $compress .= $c;
                continue;
            } elseif (strtolower(substr($c, 0, 12)) == '<nocompress>') {
                $c        = substr($c, 12, strlen($c) - 12 - 13);
                $compress .= $c;
                continue;
            } elseif (strtolower(substr($c, 0, 4)) == '<pre' || strtolower(substr($c, 0, 9)) == '<textarea') {
                $compress .= $c;
                continue;
            } elseif (strtolower(substr($c, 0, 7)) == '<script' && strpos($c, '//') != false && (strpos($c, "\r") !== false || strpos($c, "\n") !== false)) { // JS代码，包含“//”注释的，单行代码不处理
                $tmps = preg_split('/(\r|\n)/ms', $c, -1, PREG_SPLIT_NO_EMPTY);
                $c    = '';
                foreach ($tmps as $tmp) {
                    if (strpos($tmp, '//') !== false) { // 对含有“//”的行做处理
                        if (substr(trim($tmp), 0, 2) == '//') { // 开头是“//”的就是注释
                            continue;
                        }
                        $chars   = preg_split('//', $tmp, -1, PREG_SPLIT_NO_EMPTY);
                        $is_quot = $is_apos = false;
                        foreach ($chars as $key => $char) {
                            if ($char == '"' && !$is_apos && $key > 0 && $chars[$key - 1] != '\\') {
                                $is_quot = !$is_quot;
                            } elseif ($char == '\'' && !$is_quot && $key > 0 && $chars[$key - 1] != '\\') {
                                $is_apos = !$is_apos;
                            } elseif ($char == '/' && $chars[$key + 1] == '/' && !$is_quot && !$is_apos) {
                                $tmp = substr($tmp, 0, $key); // 不是字符串内的就是注释
                                break;
                            }
                        }
                    }
                    $c .= $tmp;
                }
            }
            $c = preg_replace('/[\\n\\r\\t]+/', ' ', $c); // 清除换行符，清除制表符
            $c = preg_replace('/\\s{2,}/', ' ', $c); // 清除额外的空格
            $c = preg_replace('/>\\s</', '> <', $c); // 清除标签间的空格
            $c = preg_replace('/\\/\\*.*?\\*\\//i', '', $c); // 清除 CSS & JS 的注释
            $c = preg_replace('/<!--[^!]*-->/', '', $c); // 清除 HTML 的注释
            $compress .= $c;
        }
        return $compress;
    }

    /**
     * 将 HTML 内容转换为微信小程序标签
     *
     * @param string $content HTML 内容
     * @return string 微信小程序富文本格式内容
     */
    public static function htmlReplaceXcx(string $content): string
    {
        $content = str_replace("\r\n", "", $content);
        $content = preg_replace("/style=\".*?\"/si", '', $content); //style样式
        $content = preg_replace(["/<strong.*?>/si", "/<\/strong>/si"], ['<text class="wx-strong">', '</text>'], $content); //strong
        $content = preg_replace(["/<p.*?>/si", "/<\/p>/si"], ['<view class="wx-p">', '</view>'], $content); //p
        $content = preg_replace(["/<a.*?>/si", "/<\/a>/si"], ['<text class="wx-a">', '</text>'], $content); //a
        $content = preg_replace(["/<span.*?>/si", "/<\/span>/si"], ['<text class="wx-span">', '</text>'], $content); //span
        $content = preg_replace(["/<h[1-6].*?>/si", "/<\/h[1-6]>/si"], ['<view class="wx-h">', '</view>'], $content); //h
        $content = preg_replace("/<img.*?/si", '<image class="wx-img"', $content); //img
        return $content;
    }
}
