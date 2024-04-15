<?php

declare(strict_types=1);

namespace mon\util;

use mon\util\Instance;

/**
 * 多语言控制
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Lang
{
    use Instance;

    /**
     * 语言信息
     *
     * @var array
     */
    protected $lang = [];

    /**
     * 当前选择的默认语言
     *
     * @var string
     */
    protected $range = 'zh-cn';

    /**
     * 设定当前的语言
     *
     * @param  string $range 语言类型
     * @return string
     */
    public function range(string $range = ''): string
    {
        if ($range) {
            $this->range = $range;
        }

        return $this->range;
    }

    /**
     * 动态语言定义
     *
     * @param string|array  $name  键名
     * @param string        $value 值
     * @param string        $range 语言作用域
     * @return Lang
     */
    public function set($name, string $value = '', string $range = ''): Lang
    {
        $range = $range ?: $this->range;

        if (!isset($this->lang[$range])) {
            $this->lang[$range] = [];
        }

        if (is_array($name)) {
            $this->lang[$range] = array_change_key_case($name) + $this->lang[$range];
        } else {
            $this->lang[$range][strtolower($name)] = $value;
        }

        return $this;
    }

    /**
     * 加载语言包
     *
     * @param  string $file     语言文件
     * @param  string $range    语言作用域
     * @return Lang
     */
    public function load(string $file, string $range = ''): Lang
    {
        $range = $range ?: $this->range;

        if (!isset($this->lang[$range])) {
            $this->lang[$range] = [];
        }

        $lang = [];
        if (is_file($file)) {
            $_lang = include $file;
            if (is_array($_lang)) {
                $lang = array_change_key_case($_lang) + $lang;
            }
        }

        if (!empty($lang)) {
            $this->lang[$range] = $lang + $this->lang[$range];
        }

        return $this;
    }

    /**
     * 获取语言报
     *
     * @param string $range 语言类型
     * @return array
     */
    public function getLand(string $range = ''): array
    {
        $range = $range ?: $this->range;
        return $this->lang[$range] ?? [];
    }

    /**
     * 判断语言是否已定义
     *
     * @param  string  $name  键名
     * @param  string  $range 语言作用域
     * @return boolean
     */
    public function has(string $name, string $range = ''): bool
    {
        $range = $range ?: $this->range;

        return isset($this->lang[$range][strtolower($name)]);
    }

    /**
     * 获取语言定义
     *
     * @param  string $name  键名
     * @param  array  $vars  替换变量
     * @param  string $range 语言类型
     * @return mixed
     */
    public function get(string $name, array $vars = [], string $range = '')
    {
        $range = $range ?: $this->range;

        // 空参数返回所有定义
        if (empty($name)) {
            return $this->lang[$range];
        }

        $key = strtolower($name);
        $value = isset($this->lang[$range][$key]) ? $this->lang[$range][$key] : $name;

        // 变量解析
        if (!empty($vars) && is_array($vars)) {
            /**
             * Notes:
             * 为了检测的方便，数字索引的判断仅仅是参数数组的第一个元素的key为数字0
             * 数字索引采用的是系统的 sprintf 函数替换，用法请参考 sprintf 函数
             */
            if (key($vars) === 0) {
                // 数字索引解析
                array_unshift($vars, $value);
                $value = call_user_func_array('sprintf', $vars);
            } else {
                // 关联索引解析
                $replace = array_keys($vars);
                foreach ($replace as &$v) {
                    $v = "{$v}";
                }
                $value = str_replace($replace, $vars, $value);
            }
        }

        return $value;
    }

    /**
     * 检测设置获取当前语言
     *
     * @return string
     */
    public function detect(): string
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // 自动侦测浏览器语言
            preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
            $lang = strtolower($matches[1]);
            if (isset($this->lang[$lang])) {
                $this->range = $this->lang[$lang];
            }
        }

        return $this->range;
    }
}
