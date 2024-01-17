<?php

namespace mon\util;

use PDO;
use PDOException;

/**
 * mysql数据字典
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0   2019-12-19
 */
class Dictionary
{
    /**
     * Mysql链接实例
     *
     * @var PDO
     */
    protected $db;

    /**
     * 数据库配置
     *
     * @var array
     */
    protected $config = [
        // 数据库类型
        'type'          => 'mysql',
        // 服务器地址
        'host'          => '127.0.0.1',
        // 数据库名
        'database'      => '',
        // 用户名
        'username'      => '',
        // 密码
        'password'      => '',
        // 端口
        'port'          => '3306',
        // 数据库编码默认采用utf8
        'charset'       => 'utf8mb4',
        // 数据库连接参数
        'params'        => []
    ];

    /**
     * 视图表前缀标志
     *
     * @var string
     */
    protected $viewMark;

    /**
     * 构造方法
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 析构方法，断开DB链接
     */
    public function __destruct()
    {
        $this->db = null;
    }

    /**
     * 设置DB配置
     *
     * @param array $config DB配置信息
     * @return Dictionary
     */
    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 设置视图前缀标志
     *
     * @param string $mark  视图前缀标志
     * @return Dictionary
     */
    public function setViewMark($mark)
    {
        $this->viewMark = $mark;
        return $this;
    }

    /**
     * 获取所有表
     *
     * @return array
     */
    public function getTable()
    {
        $table = [];
        $result = $this->query('SHOW TABLES');

        // 取得所有的表名
        foreach ($result as $tableName) {
            $table[]['TABLE_NAME'] = $tableName[0];
        }
        return $table;
    }

    /**
     * 获取所有表信息
     *
     * @return array
     */
    public function getTableInfo()
    {
        $tables = $this->getTable();
        foreach ($tables as $k => $v) {
            $sql = "SHOW INDEX FROM " . $v['TABLE_NAME'];
            $result = $this->query($sql);
            foreach ($result as $item) {
                $tables[$k]['INDEX'][] = $item;
            }

            $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$this->config['database']}'";
            $table_result = $this->query($sql);
            foreach ($table_result as $item2) {
                $tables[$k]['TABLE_COMMENT'][] = $item2['TABLE_COMMENT'];
            }

            $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$this->config['database']}'";
            $fields = [];
            $filed_result = $this->query($sql);
            foreach ($filed_result as $t) {
                $fields[] = $t;
            }
            $tables[$k]['COLUMN'] = $fields;
        }

        return $tables;
    }

    /**
     * 获取内容
     *
     * @param boolean $menu 是否需要菜单
     * @return string
     */
    public function getContent($menu = true)
    {
        // 获取所有表信息
        $tables = $this->getTableInfo();
        // 构造HTMl
        $html = '';
        if ($menu) {
            // 循环生成右边导航栏
            $html .= '<div class="left-side">' . "\n";
            $html .= '<h2><a href="#TABLE">数据表</a></h2>' . "\n";
            foreach ($tables as $key => $val) {
                static $first = false;
                if (!$first && !empty($this->viewMark)) {
                    if (strstr($val['TABLE_NAME'], $this->viewMark)) {
                        $first = true;
                        $html .= '<h2><a href="#TABLE">视图</a></h2>' . "\n";
                    }
                }
                $html .= '<a href="#' . $val['TABLE_NAME'] . '" class="tab-btn">' . $val['TABLE_NAME'] . '</a>' . "\n";
            }
            $html .= '</div>' . "\n";
        }

        // 循环生成左边数据表内容
        $html .= '<div class="right-side">' . "\n";
        foreach ($tables as $k => $v) {
            // 数据表头
            $html .= '<table class="dictionary" id="' . $v['TABLE_NAME'] . '">' . "\n";
            $html .= '<colgroup class="data-table"><col/><col/><col/><col/><col/><col/></colgroup>';
            $html .= '<thead>' . "\n";
            $html .= '<tr><th colspan="6">数据表名称：' . $v['TABLE_NAME'] . '</th></tr>' . "\n";
            $html .= '<tr><th colspan="6" align="left">表备注：' . (isset($v['TABLE_COMMENT'][0]) ? $v['TABLE_COMMENT'][0] : '') . '</th></tr>' . "\n";
            $html .= '<tr>' . "\n";
            $html .= '<th>字段名</th>' . "\n";
            $html .= '<th>字段类型</th>' . "\n";
            $html .= '<th>默认值</th>' . "\n";
            $html .= '<th>允许为空</th>' . "\n";
            $html .= '<th>自动递增</th>' . "\n";
            $html .= '<th>备注</th>' . "\n";
            $html .= '</tr>' . "\n";
            $html .= '</thead>' . "\n";
            // 数据表内容
            $html .= '<tbody>' . "\n";
            foreach ($v['COLUMN'] as $f) {
                $html .= '  <tr>' . "\n";
                $html .= '      <td>' . $f['COLUMN_NAME'] . '</td>' . "\n";
                $html .= '      <td>' . $f['COLUMN_TYPE'] . '</td>' . "\n";
                $html .= '      <td>' . $f['COLUMN_DEFAULT'] . '</td>' . "\n";
                $html .= '      <td>' . $f['IS_NULLABLE'] . '</td>' . "\n";
                $html .= '      <td>' . ($f['EXTRA'] == 'auto_increment' ? '是' : '&nbsp;') . '</td>' . "\n";
                $html .= '      <td>' . $f['COLUMN_COMMENT'] . '</td>' . "\n";
                $html .= '  </tr>';
            }
            $html .= '</tbody>' . "\n";
            $html .= '</table>';

            // 数据表索引表头
            if (isset($v['INDEX']) && is_array($v['INDEX'])) {
                $html .= '<table class="dictionary">' . "\n";
                $html .= '<colgroup class="index-table"><col/><col/><col/></colgroup>';
                $html .= '<thead>' . "\n";
                $html .= '<tr><th colspan="3" align="left">索引信息:</th></tr>' . "\n";
                $html .= '<tr>' . "\n";
                $html .= '<th>字段名</th>' . "\n";
                $html .= '<th>是否唯一</th>' . "\n";
                $html .= '<th>索引名称</th>' . "\n";
                $html .= '</tr>' . "\n";
                $html .= '</thead>' . "\n";

                foreach ($v['INDEX'] as $idx) {
                    $html .= '<tr>' . "\n";
                    $html .= '<td>' . $idx['Column_name'] . '</td>' . "\n";
                    $html .= '<td>' . ($idx['Non_unique'] ? '否' : '是') . '</td>' . "\n";
                    $html .= '<td>' . $idx['Key_name'] . '</td>' . "\n";
                    $html .= '</tr>';
                }
            }

            $html .= '</table>' . "\n" . '<br>' . "\n" . '<br>' . "\n";
        }
        $html .= '</div>' . "\n";

        return $html;
    }

    /**
     * 获取HTML内容
     *
     * @return string
     */
    public function getHTML()
    {
        $content = $this->getContent();
        $html = "<!doctype html>
        <html>
        <head>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
            <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge,chrome=1\">
            <title>{$this->config['database']}库数据字典</title>
            <style>
                html{color:#000;background:#ECEADF}
                body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,form,input,textarea,p,blockquote,th,td{margin:0;padding:0;font-size:14px;font-family:Arial,sans-serif}
                table{border-collapse:collapse;border-spacing:0;table-layout: fixed;}
                a{color:#00e;}
                h2{padding-left:10px;}
                h2 a{text-decoration: none;}
                .tab-btn{display:block; padding-left:30px; height:26px; line-height: 26px; text-decoration: none;font-weight: bold;border-radius: 5px;}
                .left-side{width:240px; position: absolute; left: 0; top:0; bottom: 0; overflow-y: scroll; overflow-x: auto; padding: 20px 0 120px 0;}
                .right-side{width:auto; position: absolute; left: 240px; right:0; top:0; bottom: 0; overflow-y: scroll; overflow-x: auto; padding:20px 0 10px 20px;}
                .dictionary{border: none; border-left:1px #aaa solid; border-top:1px #aaa solid; width:98%; margin-top: 20px;}
                .dictionary thead{background:#e0e0d0; }
                .dictionary tr{height:25px;}
                .dictionary tr:nth-child(2n){background: #e0e0d0;}
                .dictionary tr:hover td{background: #654b24;color:#fff;}
                .dictionary tr.selected td{background: #b83400;color: #fff;}
                .dictionary tr:hover a,.dictionary tr:hover span,.dictionary tr.selected a,.dictionary tr.selected span{color:#fff;}
                .dictionary tr th,.dictionary tr td{border-width:0 1px 1px 0; border-style: solid; border-color: #aaa; padding: 0 4px;overflow: hidden;}
                .dictionary .data-table col:nth-child(1), .dictionary .data-table col:nth-child(2), .dictionary .data-table col:nth-child(3) {width: 140px;}
                .dictionary .data-table col:nth-child(4), .dictionary .data-table col:nth-child(5) {width: 80px;}
                .dictionary .data-table col:nth-child(6) {width: auto;}
                .dictionary .index-table col:nth-child(1), .dictionary .index-table col:nth-child(2) {width: 140px;}
            </style>
        </head>
        <body>
        {$content}
        </body>
        </html>";

        return $html;
    }

    /**
     * 到处HTML数据字典
     *
     * @return void
     */
    public function exportHTML()
    {
        header("Content-Type: application/html");
        header("Content-Disposition: attachment; filename={$this->config['database']}库数据字典.html");
        echo $this->getHTML();
    }

    /**
     * 获取DB链接
     *
     * @throws PDOException
     * @return PDO
     */
    protected function getDB()
    {
        if (!$this->db) {
            // 生成mysql连接dsn
            $is_port = (isset($this->config['port']) && is_int($this->config['port'] * 1));
            $dsn = 'mysql:host=' . $this->config['host'] . ($is_port ? ';port=' . $this->config['port'] : '') . ';dbname=' . $this->config['database'];
            if (!empty($this->config['charset'])) {
                $dsn .= ';charset=' . $this->config['charset'];
            }
            // 数据库连接参数
            $params = [
                PDO::ATTR_CASE              => PDO::CASE_NATURAL,
                PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES  => false,
            ];
            if (isset($config['params']) && is_array($config['params'])) {
                $params = $config['params'] + $params;
            }
            // 链接
            $this->db = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $params
            );
        }

        return $this->db;
    }

    /**
     * 执行查询语句
     *
     * @param string $sql  SQL语句
     * @return array
     */
    protected function query($sql)
    {
        $query = $this->getDB()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_BOTH);
    }
}
