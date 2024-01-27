<?php

declare(strict_types=1);

namespace mon\util;

use Throwable;
use ArrayAccess;
use RuntimeException;

/**
 * 视图引擎
 * 
 * @author Mon <985558837@qq.com>
 * @version 2.1.0   2023-06-20  增加布局定义
 */
class View implements ArrayAccess
{
    /**
     * 视图数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 视图目录路径
     *
     * @var string
     */
    protected $path = '';

    /**
     * 视图文件后缀
     *
     * @var string
     */
    protected $ext = 'html';

    /**
     * 视图嵌套级别
     *
     * @var integer
     */
    protected $offset = 0;

    /**
     * 继承的父视图
     *
     * @var array
     */
    protected $extends = [];

    /**
     * 视图的片段
     *
     * @var array
     */
    protected $sections = [];

    /**
     * 视图片段名
     *
     * @var array
     */
    protected $sectionStacks = [];

    /**
     * 未发现的视图片段
     *
     * @var array
     */
    protected $sectionsNotFound = [];

    /**
     * 布局
     *
     * @var array
     */
    protected $layouts = [];

    /**
     * 设置视图目录路径
     *
     * @param string $path 视图目录根路径
     * @return View
     */
    public function setPath(string $path): View
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 获取视图目录路径
     *
     * @return string 获取视图目录根路径
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * 设置视图文件后缀
     *
     * @param string $ext
     * @return View
     */
    public function setExt(string $ext): View
    {
        $this->ext = $ext;
        return $this;
    }

    /**
     * 获取视图文件后缀
     *
     * @param boolean $dot  是否加上 . 符号
     * @return string
     */
    public function getExt(bool $dot = true): string
    {
        return ($dot ? '.' : '') . $this->ext;
    }

    /**
     * 设置布局
     *
     * @param string $name      布局名称
     * @param string $layout    布局视图路径
     * @return View
     */
    public function setLayout(string $name, string $layout): View
    {
        $this->layouts[$name] = $layout;
        return $this;
    }

    /**
     * 获取布局设置
     *
     * @return array
     */
    public function getLayout(): array
    {
        return $this->layouts;
    }

    /**
     * 模版赋值
     *
     * @param  string|array $key   string: 模版变量名;array: 批量赋值
     * @param  mixed        $value 模版变量值
     * @return View
     */
    public function assign($key, $value = null): View
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, (array) $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * 获取视图内容
     *
     * @param string $view  视图名称
     * @param array $data   视图数据
     * @return string
     */
    public function fetch(string $view, array $data = []): string
    {
        return $this->render($this->getViewPath($view), $data);
    }

    /**
     * 返回视图内容(不补全视图路径)
     *
     * @param string $view  完整的视图路径
     * @param array $data   视图数据
     * @param boolean $ext  是否加上后缀名
     * @return string
     */
    public function display(string $view, array $data = [], bool $ext = true): string
    {
        $tmp = $ext ? ($view . $this->getExt()) : $view;
        return $this->render($tmp, $data);
    }

    /**
     * 包含视图文件
     *
     * @param string $view  视图名称
     * @param array $data   视图数据
     * @param boolean $echo 是否直接输出
     * @return string
     */
    public function load(string $view, array $data = [], bool $echo = true): string
    {
        $view = $this->getContent($this->getViewPath($view), $data);

        if ($echo) {
            echo $view;
        }

        return $view;
    }

    /**
     * 视图继承
     *
     * @param string $view  视图名称
     * @return void
     */
    public function extend(string $view): void
    {
        $this->extends[$this->offset] = $this->getViewPath($view);
    }

    /**
     * 设置布局
     *
     * @param string $layout
     * @return void
     */
    public function layout(string $layout): void
    {
        if (!isset($this->layouts[$layout])) {
            throw new RuntimeException('Layout not found: ' . $layout);
        }

        $this->extends[$this->offset] = $this->layouts[$layout] . $this->getExt();
    }

    /**
     * 开始定义一个视图片段, 用户继承时的父级视图$this->with()输出
     * 一般 block() 与 blockEnd() 成对出现, 但传递第二个参数，则不需要 sectionEnd()
     *
     * @param string $name  视图名称
     * @param string $content   视图内容
     * @return void
     */
    public function block(string $name, string $content = ''): void
    {
        ob_start();
        $this->sectionStacks[$this->offset][] = $name;

        if ($content) {
            $lastname = array_pop($this->sectionStacks[$this->offset]);
            $this->setSections($lastname, $content);
            ob_end_clean();
        }
    }

    /**
     * 结束定义一个视图片段
     *
     * @return string 视图片段标识符
     */
    public function blockEnd(): string
    {
        $lastname = array_pop($this->sectionStacks[$this->offset]);
        $this->setSections($lastname, ob_get_clean());
        return $lastname;
    }

    /**
     * 定义视图片段输出位置, 用于继承是父级视图输出子视图上报的视图片段
     *
     * @param string $name  视图名称
     * @param string $content   视图内容
     * @return void
     */
    public function putBlock(string $name, string $content = ''): void
    {
        if (isset($this->sections[$this->offset][$name])) {
            echo $this->sections[$this->offset][$name];
        } else {
            $this->sectionsNotFound[$this->offset][] = $name;
            echo '<!--@section_' . $name . '-->';
        }

        if ($content) {
            $this->setSections($name, $content);
        }
    }

    /**
     * 输出视图数据 - content
     * 用于继承时，父级视图$this->content()输出使用
     *
     * @param  string  $node 获取内容的节点
     * @param  boolean $echo 是否直接输出
     * @return string  如果不是直接输出则返回内容
     */
    public function content(string $node = 'content', bool $echo = true): string
    {
        $content = $this->getSections($node);

        if ($echo) {
            echo $content;
        }

        return $content;
    }

    /**
     * 视图数据是否存在
     *
     * @param  string  $key 视图变量名称
     * @return boolean
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * 获取视图数据(支持'.'获取多级数据)
     *
     * @param  string $key     变量名，支持.分割数组
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $name = explode(".", $key);
        $result = $this->data;
        for ($i = 0, $len = count($name); $i < $len; $i++) {
            // 不存在配置节点，返回默认值
            if (!isset($result[$name[$i]])) {
                $result = $default;
                break;
            }
            $result = $result[$name[$i]];
        }

        return $result;
    }

    /**
     * 设置视图数据
     *
     * @param string|array $key   变量名
     * @param mixed  $value 变量值
     * @return View
     */
    public function set($key, $value = null): View
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * 删除视图数据
     *
     * @param  string $key 变量名
     * @return View
     */
    public function delete(string $key): View
    {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * 清空视图数据
     *
     * @return View
     */
    public function clear(): View
    {
        $this->data = [];
        return $this;
    }

    /**
     * 核心方法，渲染视图
     *
     * @param  string $view 视图名称
     * @param  array  $data 视图数据
     * @return string
     */
    protected function render(string $view, array $data = []): string
    {
        // 设置当前视图数据
        $this->set($data);
        // 提升视图嵌套级别
        $this->increase();
        // 获取视图内容
        $content = trim($this->getContent($view));

        // 判断当前视图级别是否存在继承, 存在继承，处理继承
        if (isset($this->extends[$this->offset])) {
            // 记录视图内容，到视图片段中
            $this->setSections('content', $content);
            // 获取父级视图
            $parent = $this->extends[$this->offset];
            $content = trim($this->getContent($parent));
        }

        // 判断是否存在未输出的变量
        if (isset($this->sectionsNotFound[$this->offset])) {
            // 对模板中未输出的变量进行替换
            foreach ($this->sectionsNotFound[$this->offset] as $name) {
                $content = str_replace('<!--@section_' . $name . '-->', $this->getSections($name), $content);
            }
        }

        // 重置视图片段
        $this->flush();
        // 降低嵌套级别
        $this->decrement();

        return $content;
    }

    /**
     * 添加视图片段
     *
     * @param  string $name     视图片段名称
     * @param  string $content  视图片段内容
     * @return void
     */
    protected function setSections(string $name, string $content): void
    {
        $this->sections[$this->offset][$name] = $content;
    }

    /**
     * 获取视图片段
     *
     * @param  string $name     视图片段名称
     * @return string
     */
    protected function getSections(string $name): string
    {
        if (isset($this->sections[$this->offset][$name])) {
            return $this->sections[$this->offset][$name];
        }
        return '';
    }

    /**
     * 处理获取视图内容
     *
     * @param string $view  视图名称
     * @param array $data   视图数据
     * @throws Throwable
     * @throws RuntimeException
     * @return string
     */
    protected function getContent(string $view, array $data = []): string
    {
        if (file_exists($view)) {
            // 开启缓存，利用缓存获取视图内容
            ob_start();
            // $data = array_merge($this->data, (array) $data);
            $data = !empty($data) ?: $this->data;
            // 数组变量分割
            extract($data);

            // 校验是否出现异常，出现异常清空缓存，防止污染程序
            try {
                include $view;
            } catch (Throwable $e) {
                // 获取缓存，清空缓存
                ob_get_clean();
                throw $e;
            }
            // 返回视图内容，并清空缓存
            return ob_get_clean();
        }

        throw new RuntimeException("Can not find the requested view: " . $view);
    }

    /**
     * 获取视图路径
     *
     * @param  string $view 视图名称
     * @return string
     */
    protected function getViewPath(string $view): string
    {
        if ($this->path) {
            $view = $this->path . ltrim($view, DIRECTORY_SEPARATOR);
        }

        return $view . $this->getExt();
    }

    /**
     * 提升嵌套级别
     *
     * @return void
     */
    protected function increase(): void
    {
        $this->offset++;
    }

    /**
     * 降低嵌套级别
     *
     * @return void
     */
    protected function decrement(): void
    {
        $this->offset--;
    }

    /**
     * 重置视图片段
     *
     * @return void
     */
    protected function flush(): void
    {
        unset(
            $this->sections[$this->offset],
            $this->sectionStacks[$this->offset],
            $this->sectionsNotFound[$this->offset]
        );
    }

    // +----------------------------------------------------------------------------
    // | 接口方法定义
    // +----------------------------------------------------------------------------

    /**
     * 接口方法，视图数据是否存在
     *
     * @param  string  $key 变量名称
     * @return boolean
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * 接口方法，获取视图数据，不存在则返回null
     *
     * @param  string $key     变量名称
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * 接口方法，设置视图数据
     *
     * @param string $key   变量名称
     * @param mixed $value  变量值
     * @return mixed
     */
    public function offsetSet($key, $value = null): void
    {
        $this->set($key, $value);
    }

    /**
     * 接口方法，删除视图数据
     *
     * @param  string $key 变量名称
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->delete($key);
    }

    /**
     * 获取视图数据
     *
     * @param  string $key  变量名
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * 添加视图数据
     *
     * @param string $key   变量名
     * @param mixed $value  变量值
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * 视图数据是否存在
     *
     * @param  string  $key 变量名
     * @return boolean
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * 删除视图数据
     *
     * @param string $key 变量名
     * @return void
     */
    public function __unset($key)
    {
        $this->delete($key);
    }
}
