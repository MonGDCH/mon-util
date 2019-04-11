<?php
namespace mon\util;

/**
* 树结构数据操作类
*
* @author Mon <985558837@qq.com>
* @version 1.0
*/
class Tree
{
    /**
     * 本类单例
     * 
     * @var [type]
     */
    protected static $instance;

    /**
     * 生成树型结构所需要的2维数组
     *
     * @var array
     */
    public $data = array();

    /**
     * 配置信息
     * 
     * @var array
     */
    protected $config = array(
        'nbsp'      => "&nbsp;",        // 空格替换
        'id'        => 'id',            // 主键
        'pid'       => 'pid',           // 子ID键
        'root'      => 0,               // 顶级子ID
        'icon'      => array('│', '├', '└'),    // 分级前缀
        'tpl'       => array(                   // html生成模板
            'ul'    => '<li value="@id" @selected @disabled>@name @childlist</li>',
            'option'=> '<option value="@id" @selected @disabled>@spacer@name</option>',
        ),
    );

    /**
     * 单例初始化
     *
     * @return Auth
     */
    public static function instance()
    {
        if(is_null(self::$instance)){
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 架构初始化
     */
    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 树初始化配置，支持链式操作
     *
     * @param  array $config   配置信息
     * @return [type]       [description]
     */
    public function init($config = array())
    {
        if($config){
            $this->config = array_merge($this->config, $config);
        }
        return $this;
    }

    /**
     * 设置操作的data数组, 支持链式操作
     *
     * @param  array  $arr [description]
     * @return [type]      [description]
     */
    public function data($arr)
    {
        $this->data = (array)$arr;
        return $this;
    }

    /**
     * 获取对应子级数据
     *
     * @param  int    $pid 子级对应父级的PID
     * @return [type]      [description]
     */
    public function getChild($pid)
    {
        $result = array();
        foreach($this->data as $value)
        {
            if(!isset($value[ $this->config['pid'] ])){
                // 不存在对应子键，跳过
                continue;
            }
            if($value[ $this->config['pid'] ] == $pid){
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * 递归获取对应所有子级后代的数据
     *
     * @param  int     $pid  子级对应父级的PID
     * @param  boolean $self 是否包含自身
     * @return [type]        [description]
     */
    public function getChildren($pid, $self = false)
    {
        $result = array();
        foreach($this->data as $value)
        {
            if(!isset($value[ $this->config['pid'] ])){
                // 不存在对应子键，跳过
                continue;
            }

            if($value[ $this->config['pid'] ] == $pid){
                $result[] = $value;
                // 递归获取
                $result = array_merge( $result, $this->getChildren( $value['id'] ) );
            }
            elseif($self && $value[ $this->config['id'] ] == $pid){
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * 获取对应所有后代ID
     *
     * @param  int     $pid  子级对应父级的PID
     * @param  boolean $self 是否包含自身
     * @return [type]        [description]
     */
    public function getChildrenIds($pid, $self = false)
    {
        $result = array();
        $list = $this->getChildren($pid, $self);
        foreach($list as $item)
        {
            $result[] = $item[ $this->config['id'] ];
        }

        return $result;
    }

    /**
     * 获取当前节点对应父级数据
     *
     * @param  int    $id 节点ID
     * @return boolean     [description]
     */
    public function getParent($id)
    {
        $result = array();
        $pid = 0;

        foreach($this->data as $value)
        {
            if(!isset($value[ $this->config['id'] ]) || !isset($value[ $this->config['pid'] ])){
                // 不存在对应节点，跳过
                continue;
            }
            if($value[ $this->config['id'] ] == $id){
                // 获取当前节点父节点ID
                $pid = $value[ $this->config['pid'] ];
                break;
            }
        }
        // 存在父级节点
        if($pid){
            foreach($this->data as $v)
            {
                if(!isset($value[ $this->config['id'] ])){
                    // 不存在对应节点，跳过
                    continue;
                }
                if($value[ $this->config['id'] ] == $pid){
                    // 获取当前节点父节点ID
                    $result = $value;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * 递归获取当前节点所有父级数据
     *
     * @param  int     $id   节点ID
     * @param  boolean $self 是否包含自身
     * @return [type]        [description]
     */
    public function getParents($id, $self = false)
    {
        $result = array();
        $pid = 0;
        foreach($this->data as $value)
        {
            if(!isset($value[ $this->config['id'] ]) || !isset($value[ $this->config['pid'] ])){
                // 不存在对应节点，跳过
                continue;
            }
            if($value[ $this->config['id'] ] == $id){
                if($self){
                    // 包含自身
                    $result[] = $value;
                }
                // 获取父级ID
                $pid = $value[ $this->config['pid'] ];
                break;
            }
        }

        // 存在父级节点
        if($pid){
            $result = array_merge($this->getParents($pid, true), $result);
        }

        return $result;
    }

    /**
     * 递归获取当前节点所有父级ID
     *
     * @param  int     $id   节点ID
     * @param  boolean $self 是否包含自身
     * @return [type]        [description]
     */
    public function getParentsIds($id, $self)
    {
        $result = array();
        $list = $this->getParents($id, $self);
        foreach($list as $item)
        {
            $result[] = $item[ $this->config['id'] ];
        }

        return $result;
    }

    /**
     * 组成树结构
     *
     * @param  string $mark 子级标志位
     * @return [type]       [description]
     */
    public function getTree($mark = 'child')
    {
        // 创建Tree
        $tree = array();
        // 创建基于主键的数组引用
        $refer = array();
        foreach($this->data as $key => $data)
        {
            $refer[ $data[ $this->config['id'] ] ] =& $this->data[$key];
        }
        foreach($this->data as $key => $data)
        {
            // 判断是否存在parent
            $parentId =  $data[ $this->config['pid'] ];
            if($this->config['root'] == $parentId){
                $this->data[$key]['haschild'] = 1;
                $tree[] =& $this->data[$key];
            }
            elseif(isset($refer[$parentId])){
                $parent =& $refer[$parentId];
                $this->data[$key]['_mark_'] = $this->config['icon'][2];
                $parent[$mark][] =& $this->data[$key];
            }
        }
        return $tree;
    }

    /**
     * 回滚由getTree方法生成的树结果为二维数组
     *
     * @param  array  $data [description]
     * @param  string $mark [description]
     * @return [type]       [description]
     */
    public function rollbackTree($data, $mark = 'child')
    {
        $result = array();
        foreach($data as $k => $v)
        {
            // 判断是否存在子集
            $child = isset($v[$mark]) ? $v[$mark] : array();
            unset($v[ $mark ]);
            $result[] = $v;
            if($child){
                // 递归合并
                $result = array_merge($result, $this->rollbackTree($child, $mark));
            }
        }

        return $result;
    }

    /**
     * 生成Option树型结构
     *
     * @param int    $pid 表示获得这个ID下的所有子级
     * @param string $itemtpl 条目模板 如："<option value=@id @selected @disabled>@spacer@name</option>"
     * @param mixed  $selectedids 被选中的ID，比如在做树型下拉框的时候需要用到
     * @param mixed  $disabledids 被禁用的ID，比如在做树型下拉框的时候需要用到
     * @param string $itemprefix 每一项前缀
     * @param string $toptpl 顶级栏目的模板
     * @return string
     */
    public function getTreeOption($pid, $itemtpl = null, $selectedids = '', $disabledids = '', $itemprefix = '', $toptpl = '')
    {
        $itemtpl = is_null($itemtpl) ? $this->config['tpl']['option'] : $itemtpl;
        $ret = '';
        $number = 1;
        $childs = $this->getChild($pid);
        if($childs){
            $total = count($childs);
            foreach($childs as $value)
            {
                $id = $value[ $this->config['id'] ];
                $j = $k = '';
                if($number == $total){
                    $j .= $this->config['icon'][2];
                    $k = $itemprefix ? $this->config['nbsp'] : '';
                }
                else{
                    $j .= $this->config['icon'][1];
                    $k = $itemprefix ? $this->config['icon'][0] : '';
                }
                $spacer = $itemprefix ? $itemprefix . $j : '';
                // 判断是否需要选中
                $selected = '';
                if($selectedids){
                    $in = (is_array($selectedids)) ? $selectedids : explode(",", $selectedids);
                    $selected = in_array($id, $in) ? "selected" : '';
                }
                // 判断是否需要禁用
                $disabled = '';
                if($disabledids){
                    $in = (is_array($disabledids)) ? $disabledids : explode(",", $disabledids);
                    $disabled = in_array($id, $in) ? "disabled" : '';
                }
                $value = array_merge($value, array('selected' => $selected, 'disabled' => $disabled, 'spacer' => $spacer));
                $value = array_combine(array_map(function($k){
                            return '@' . $k;
                        }, array_keys($value)), $value);
                $nstr = strtr((($value["@{$this->config['pid']}"] == 0 || $this->getChild($id) ) && $toptpl ? $toptpl : $itemtpl), $value);
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
     * @param int    $pid 表示获得这个ID下的所有子级
     * @param string $itemtpl 条目模板 如："<li value=@id @selected @disabled>@name @childlist</li>"
     * @param string $selectedids 选中的ID
     * @param string $disabledids 禁用的ID
     * @param string $wraptag 子列表包裹标签
     * @return string
     */
    public function getTreeUl($pid, $itemtpl = null, $selectedids = '', $disabledids = '', $wraptag = 'ul', $wrapattr = '')
    {
        $itemtpl = is_null($itemtpl) ? $this->config['tpl']['ul'] : $itemtpl;
        $str = '';
        $childs = $this->getChild($pid);
        if($childs){
            foreach ($childs as $value)
            {
                $id = $value[ $this->config['id'] ];
                unset($value['child']);
                // 判断是否需要选中
                $selected = '';
                if($selectedids){
                    $in = (is_array($selectedids)) ? $selectedids : explode(",", $selectedids);
                    $selected = in_array($id, $in) ? "selected" : '';
                }
                // 判断是否需要禁用
                $disabled = '';
                if($disabledids){
                    $in = (is_array($disabledids)) ? $disabledids : explode(",", $disabledids);
                    $disabled = in_array($id, $in) ? "disabled" : '';
                }
                $value = array_merge($value, array('selected' => $selected, 'disabled' => $disabled));
                $value = array_combine(array_map(function($k){
                            return '@' . $k;
                        }, array_keys($value)), $value);
                $nstr = strtr($itemtpl, $value);
                $childdata = $this->getTreeUl($id, $itemtpl, $selectedids, $disabledids, $wraptag, $wrapattr);
                $childlist = $childdata ? "<{$wraptag} {$wrapattr}>" . $childdata . "</{$wraptag}>" : "";
                $str .= strtr($nstr, array('@childlist' => $childlist));
            }
        }
        return $str;
    }

    /**
     * 菜单数据
     *
     * @param int $myid
     * @param string $itemtpl
     * @param mixed $selectedids
     * @param mixed $disabledids
     * @param string $wraptag
     * @param string $wrapattr
     * @param int $deeplevel
     * @return string
     */
    public function getTreeMenu($myid, $itemtpl, $selectedids = '', $disabledids = '', $wraptag = 'ul', $wrapattr = '', $deeplevel = 0)
    {
        $str = '';
        $childs = $this->getChild($myid);
        if($childs){
            foreach ($childs as $value)
            {
                $id = $value['id'];
                unset($value['child']);
                $selected = in_array($id, (is_array($selectedids) ? $selectedids : explode(',', $selectedids))) ? 'selected' : '';
                $disabled = in_array($id, (is_array($disabledids) ? $disabledids : explode(',', $disabledids))) ? 'disabled' : '';
                $value = array_merge($value, array('selected' => $selected, 'disabled' => $disabled));
                $value = array_combine(array_map(function($k){
                            return '@' . $k;
                        }, array_keys($value)), $value);
                $bakvalue = array_intersect_key($value, array_flip(['@url', '@caret', '@class']));
                $value = array_diff_key($value, $bakvalue);
                $nstr = strtr($itemtpl, $value);
                $value = array_merge($value, $bakvalue);
                $childdata = $this->getTreeMenu($id, $itemtpl, $selectedids, $disabledids, $wraptag, $wrapattr, $deeplevel + 1);
                $childlist = $childdata ? "<{$wraptag} {$wrapattr}>" . $childdata . "</{$wraptag}>" : "";
                $childlist = strtr($childlist, array('@class' => $childdata ? 'last' : ''));
                $value = array(
                    '@childlist' => $childlist,
                    '@url'       => $childdata || !isset($value['@url']) ? "javascript:;" : $value['@url'],
                    '@caret'     => ($childdata && (!isset($value['@badge']) || !$value['@badge']) ? '<i class="fa fa-angle-left"></i>' : ''),
                    '@badge'     => isset($value['@badge']) ? $value['@badge'] : '',
                    '@class'     => ($selected ? ' active' : '') . ($disabled ? ' disabled' : '') . ($childdata ? ' treeview' : ''),
                );
                $str .= strtr($nstr, $value);
            }
        }
        return $str;
    }

    /**
     * 获取树状数组
     *
     * @param string $myid 要查询的ID
     * @param string $nametpl 名称条目模板
     * @param string $itemprefix 前缀
     * @return string
     */
    public function getTreeArray($myid, $itemprefix = '')
    {
        $childs = $this->getChild($myid);
        $n = 0;
        $data = [];
        $number = 1;
        if($childs){
            $total = count($childs);
            foreach ($childs as $id => $value)
            {
                $j = $k = '';
                if($number == $total){
                    $j .= $this->config['icon'][2];
                    $k = $itemprefix ? $this->config['nbsp'] : '';
                }
                else{
                    $j .= $this->config['icon'][1];
                    $k = $itemprefix ? $this->config['icon'][0] : '';
                }
                $spacer = $itemprefix ? $itemprefix . $j : '';
                $value['spacer'] = $spacer;
                $data[$n] = $value;
                $data[$n]['childlist'] = $this->getTreeArray($value['id'], $itemprefix . $k . $this->config['nbsp']);
                $n++;
                $number++;
            }
        }
        return $data;
    }

    /**
     * 将getTreeArray的结果返回为二维数组
     *
     * @param array $data
     * @return array
     */
    public function getTreeList($data = [], $field = 'name')
    {
        $arr = [];
        foreach($data as $k => $v)
        {
            $childlist = isset($v['childlist']) ? $v['childlist'] : [];
            unset($v['childlist']);
            $v[$field] = $v['spacer'] . ' ' . $v[$field];
            $v['haschild'] = ($childlist || $v['pid'] == 0) ? 1 : 0;
            if ($v['id']){
                $arr[] = $v;
            }
            if ($childlist){
                $arr = array_merge($arr, $this->getTreeList($childlist, $field));
            }
        }
        return $arr;
    }

}
