<?php

declare(strict_types=1);

namespace mon\util;

use mon\util\Instance;

/**
 * 树结构数据操作类
 *
 * @author Mon <985558837@qq.com>
 * @version 1.1 优化代码
 */
class Tree
{
    use Instance;

    /**
     * 生成树型结构所需要的2维数组
     *
     * @var array
     */
    public $data = [];

    /**
     * 配置信息
     * 
     * @var array
     */
    protected $config = [
        // 空格替换
        'nbsp'      => "　",
        // 主键 
        'id'        => 'id',
        // 子ID键       
        'pid'       => 'pid',
        // 顶级子ID
        'root'      => 0,
        // 分级前缀
        'icon'      => ['│', '├', '└'],
        // html生成模板
        'tpl'       => [
            'ul'    => '<li value="@id" @selected @disabled>@name @childlist</li>',
            'option' => '<option value="@id" @selected @disabled>@spacer@name</option>',
        ],
    ];

    /**
     * id => node 映射，便于快速查找父子关系
     *
     * @var array
     */
    protected $map = [];

    /**
     * pid => [nodes...] 索引，便于快速获取子节点
     *
     * @var array
     */
    protected $index = [];

    /**
     * 构造方法
     *
     * @param array $config 配置信息
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 树初始化配置，支持链式操作
     *
     * @param  array $config 配置信息
     * @return Tree
     */
    public function init(array $config = []): Tree
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
        return $this;
    }

    /**
     * 设置操作的data数组, 支持链式操作
     *
     * @param  array $arr 操作的数据
     * @return Tree
     */
    public function data(array $arr): Tree
    {
        $this->data = $arr;
        // 重建索引：map 和 parent->children 索引
        $this->map = [];
        $this->index = [];
        foreach ($this->data as $k => &$v) {
            $id = $v[$this->config['id']] ?? null;
            $pid = $v[$this->config['pid']] ?? null;
            if ($id !== null) {
                $this->map[$id] = &$this->data[$k];
            }
            // 支持 pid 为 0 或 '0'
            $this->index[$pid][] = &$this->data[$k];
        }
        return $this;
    }

    /**
     * 获取对应子级数据
     *
     * @param  integer|string  $pid  子级对应父级的PID
     * @return array
     */
    public function getChild($pid): array
    {
        return isset($this->index[$pid]) ? $this->index[$pid] : [];
    }

    /**
     * 递归获取对应所有子级后代的数据
     *
     * @param  integer|string $pid  子级对应父级的PID
     * @param  boolean $self 是否包含自身
     * @return array
     */
    public function getChildren($pid, bool $self = false): array
    {
        $result = [];
        // 包含自身
        if ($self && isset($this->map[$pid])) {
            $result[] = $this->map[$pid];
        }
        $children = $this->getChild($pid);
        if (empty($children)) {
            return $result;
        }
        foreach ($children as $child) {
            $result[] = $child;
            $cid = $child[$this->config['id']] ?? null;
            if ($cid !== null) {
                $result = array_merge($result, $this->getChildren($cid));
            }
        }

        return $result;
    }

    /**
     * 获取对应所有后代ID
     *
     * @param  integer|string $pid  子级对应父级的PID
     * @param  boolean $self 是否包含自身
     * @return array
     */
    public function getChildrenIds($pid, bool $self = false): array
    {
        $result = [];
        $list = $this->getChildren($pid, $self);
        foreach ($list as $item) {
            if (isset($item[$this->config['id']])) {
                $result[] = $item[$this->config['id']];
            }
        }

        return $result;
    }

    /**
     * 获取当前节点对应父级数据
     *
     * @param  integer|string $id 节点ID
     * @return array
     */
    public function getParent($id): array
    {
        if (!isset($this->map[$id])) {
            return [];
        }
        $pid = $this->map[$id][$this->config['pid']] ?? null;
        if ($pid === null) {
            return [];
        }
        return $this->map[$pid] ?? [];
    }

    /**
     * 递归获取当前节点所有父级数据
     *
     * @param  integer|string $id   节点ID
     * @param  boolean $self 是否包含自身
     * @return array
     */
    public function getParents($id, bool $self = false): array
    {
        $result = [];
        if (!isset($this->map[$id])) {
            return $result;
        }
        if ($self) {
            $result[] = $this->map[$id];
        }
        $pid = $this->map[$id][$this->config['pid']] ?? null;
        while ($pid !== null && isset($this->map[$pid])) {
            array_unshift($result, $this->map[$pid]);
            $pid = $this->map[$pid][$this->config['pid']] ?? null;
        }

        return $result;
    }

    /**
     * 递归获取当前节点所有父级ID
     *
     * @param  integer|string $id   节点ID
     * @param  boolean $self 是否包含自身
     * @return array
     */
    public function getParentsIds($id, bool $self = false): array
    {
        $result = [];
        $list = $this->getParents($id, $self);
        foreach ($list as $item) {
            if (isset($item[$this->config['id']])) {
                $result[] = $item[$this->config['id']];
            }
        }

        return $result;
    }

    /**
     * 组成树结构
     *
     * @param  string $childName    子级标志位
     * @param  boolean $mark        是否显示mark标志符号
     * @return array
     */
    public function getTree(string $childName = 'children', bool $mark = false): array
    {
        // 创建Tree
        $tree = [];
        // 创建基于主键的数组引用
        $refer = [];
        foreach ($this->data as $key => $data) {
            $this->data[$key][$childName] = [];
            $refer[$data[$this->config['id']]] = &$this->data[$key];
        }
        foreach ($this->data as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$this->config['pid']];
            if ($this->config['root'] == $parentId) {
                $tree[] = &$this->data[$key];
            } elseif (isset($refer[$parentId])) {
                $parent = &$refer[$parentId];
                if ($mark) {
                    $this->data[$key]['_mark_'] = $this->config['icon'][2];
                }
                $parent[$childName][] = &$this->data[$key];
            }
        }

        return $tree;
    }

    /**
     * 回滚由getTree方法生成的树结果为二维数组
     *
     * @param  array  $data 树结构数据
     * @param  string $mark 子级标志位
     * @return array
     */
    public function rollbackTree(array $data, string $mark = 'children'): array
    {
        $result = [];
        foreach ($data as $v) {
            // 判断是否存在子集
            $child = isset($v[$mark]) ? $v[$mark] : [];
            unset($v[$mark]);
            $result[] = $v;
            if ($child) {
                // 递归合并
                $result = array_merge($result, $this->rollbackTree($child, $mark));
            }
        }

        return $result;
    }

    /**
     * 生成Option树型结构
     *
     * @param integer|string $pid   表示获得这个ID下的所有子级
     * @param string  $itemtpl      条目模板 如："<option value=@id @selected @disabled>@spacer@name</option>"
     * @param mixed   $selectedids  被选中的ID，比如在做树型下拉框的时候需要用到
     * @param mixed   $disabledids  被禁用的ID，比如在做树型下拉框的时候需要用到
     * @param string  $itemprefix   每一项前缀
     * @param string  $toptpl       顶级栏目的模板
     * @return string
     */
    public function getTreeOption($pid, ?string $itemtpl = null, string $selectedids = '', string $disabledids = '', string $itemprefix = '', string $toptpl = ''): string
    {
        $itemtpl = is_null($itemtpl) ? $this->config['tpl']['option'] : $itemtpl;
        $ret = '';
        $number = 1;
        $childs = $this->getChild($pid);
        if ($childs) {
            $total = count($childs);
            foreach ($childs as $value) {
                $id = $value[$this->config['id']];
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->config['icon'][2];
                    $k = $itemprefix ? $this->config['nbsp'] : '';
                } else {
                    $j .= $this->config['icon'][1];
                    $k = $itemprefix ? $this->config['icon'][0] : '';
                }
                $spacer = $itemprefix ? $itemprefix . $j : '';
                // 判断是否需要选中
                $selected = '';
                if ($selectedids) {
                    $in = (is_array($selectedids)) ? $selectedids : explode(",", $selectedids);
                    $selected = in_array($id, $in) ? 'selected' : '';
                }
                // 判断是否需要禁用
                $disabled = '';
                if ($disabledids) {
                    $in = (is_array($disabledids)) ? $disabledids : explode(",", $disabledids);
                    $disabled = in_array($id, $in) ? 'disabled' : '';
                }
                $value = array_merge($value, ['selected' => $selected, 'disabled' => $disabled, 'spacer' => $spacer]);
                $value = array_combine(array_map(function ($k) {
                    return '@' . $k;
                }, array_keys($value)), $value);
                $nstr = strtr((($value["@{$this->config['pid']}"] == 0 || $this->getChild($id)) && $toptpl ? $toptpl : $itemtpl), $value);
                $ret .= $nstr;
                $ret .= $this->getTreeOption($id, $itemtpl, $selectedids, $disabledids, $itemprefix . $k . $this->config['nbsp'], $toptpl);
                $number++;
            }
        }
        return $ret;
    }

    /**
     * 树型结构UL
     *
     * @param integer|string $pid   表示获得这个ID下的所有子级
     * @param string  $itemtpl      条目模板 如："<li value=@id @selected @disabled>@name @childlist</li>"
     * @param string  $selectedids  选中的ID
     * @param string  $disabledids  禁用的ID
     * @param string  $wraptag      子列表包裹标签
     * @return string
     */
    public function getTreeUl($pid, ?string $itemtpl = null, string $selectedids = '', string $disabledids = '', string $wraptag = 'ul', string $wrapattr = ''): string
    {
        $itemtpl = is_null($itemtpl) ? $this->config['tpl']['ul'] : $itemtpl;
        $str = '';
        $childs = $this->getChild($pid);
        if ($childs) {
            foreach ($childs as $value) {
                $id = $value[$this->config['id']];
                unset($value['children']);
                // 判断是否需要选中
                $selected = '';
                if ($selectedids) {
                    $in = (is_array($selectedids)) ? $selectedids : explode(",", $selectedids);
                    $selected = in_array($id, $in) ? 'selected' : '';
                }
                // 判断是否需要禁用
                $disabled = '';
                if ($disabledids) {
                    $in = (is_array($disabledids)) ? $disabledids : explode(",", $disabledids);
                    $disabled = in_array($id, $in) ? 'disabled' : '';
                }
                $value = array_merge($value, ['selected' => $selected, 'disabled' => $disabled]);
                $value = array_combine(array_map(function ($k) {
                    return '@' . $k;
                }, array_keys($value)), $value);
                $nstr = strtr($itemtpl, $value);
                $childdata = $this->getTreeUl($id, $itemtpl, $selectedids, $disabledids, $wraptag, $wrapattr);
                $childlist = $childdata ? "<{$wraptag} {$wrapattr}>" . $childdata . "</{$wraptag}>" : "";
                $str .= strtr($nstr, ['@childlist' => $childlist]);
            }
        }
        return $str;
    }

    /**
     * 获取树状数组
     *
     * @param integer|string $myid  要查询的ID
     * @param string $itemprefix    前缀
     * @param string $mark          后代标识
     * @return array
     */
    public function getTreeArray($myid, string $itemprefix = '', string $mark = 'children'): array
    {
        $childs = $this->getChild($myid);
        $n = 0;
        $data = [];
        $number = 1;
        if ($childs) {
            $total = count($childs);
            foreach ($childs as $value) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->config['icon'][2];
                    $k = $itemprefix ? $this->config['nbsp'] : '';
                } else {
                    $j .= $this->config['icon'][1];
                    $k = $itemprefix ? $this->config['icon'][0] : '';
                }
                $spacer = $itemprefix ? $itemprefix . $j : '';
                $value['spacer'] = $spacer;
                $data[$n] = $value;
                $cid = $value[$this->config['id']] ?? null;
                $data[$n][$mark] = $cid !== null ? $this->getTreeArray($cid, $itemprefix . $k . $this->config['nbsp'], $mark) : [];
                $n++;
                $number++;
            }
        }
        return $data;
    }

    /**
     * 将getTreeArray的结果返回为二维数组
     *
     * @param array $data  树结构数据
     * @param string $field 字段名称
     * @param string $mark          后代标识
     * @return array
     */
    public function getTreeList(array $data = [], string $field = 'name', string $mark = 'children'): array
    {
        $arr = [];
        foreach ($data as $v) {
            $childlist = isset($v[$mark]) ? $v[$mark] : [];
            unset($v[$mark]);
            $v[$field] = $v['spacer'] . ' ' . $v[$field];
            $v['haschild'] = ($childlist || $v['pid'] == 0) ? 1 : 0;
            if ($v['id']) {
                $arr[] = $v;
            }
            if ($childlist) {
                $arr = array_merge($arr, $this->getTreeList($childlist, $field, $mark));
            }
        }
        return $arr;
    }
}
