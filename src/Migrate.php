<?php

declare(strict_types=1);

namespace mon\util;

use PDO;
use PDOException;
use FilesystemIterator;
use InvalidArgumentException;
use mon\util\exception\SqlException;
use mon\util\exception\MigrateException;

/**
 * Mysql数据库迁移备份
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0   2022-07-13
 */
class Migrate
{
    use Instance;

    /**
     * 数据库连接
     *
     * @var PDO
     */
    protected $db = null;

    /**
     * 文件指针
     *
     * @var resource
     */

    protected $fp = null;

    /**
     * 备份文件信息 part - 卷号，name - 文件名
     *
     * @var array
     */
    protected $file = [];

    /**
     * 当前打开文件大小
     *
     * @var integer
     */
    protected $size = 0;

    /**
     * 备份配置
     *
     * @var array
     */
    protected $config = [
        // 数据库备份路径
        'path'      => './data/',
        // 数据库备份卷大小
        'part'      => 20971520,
        // 数据库备份文件是否启用压缩 0不压缩 1 压缩
        'compress'  => 0,
        // 压缩级别
        'level'     => 9,
        // 数据库配置
        'db'        => [
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
        ]
    ];

    /**
     * 数据库备份构造方法
     *
     * @param array $config 配置信息
     */
    public function __construct(?array $config = [])
    {
        $this->config = array_merge($this->config, (array)$config);
        // 初始化文件名
        $this->setFile();
    }

    /**
     * 析构方法，用于关闭资源
     */
    public function __destruct()
    {
        $this->close();
        $this->db = null;
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return Migrate
     */
    public function setConfig(array $config): Migrate
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 获取配置信息
     *
     * @param string $name
     * @return mixed
     */
    public function getConfig(?string $name = null)
    {
        return is_null($name) ? $this->config : $this->config[$name];
    }

    /**
     * 设置备份文件名
     *
     * @param array $file 文件信息
     * @demo setFile(['name' => '20220713', 'part' => 1])
     * @return Migrate
     */
    public function setFile(?array $file = null): Migrate
    {
        if (is_null($file)) {
            $this->file = ['name' => date('Ymd-His'), 'part' => 1];
        } else {
            $this->file = $file;
        }

        return $this;
    }

    /**
     * 获取文件信息
     *
     * @return array
     */
    public function getFile(): array
    {
        return $this->file;
    }

    /**
     * 获取数据库表数据
     *
     * @return array
     */
    public function tableList(): array
    {
        $list = $this->query("SHOW TABLE STATUS");
        return array_map('array_change_key_case', (array) $list);
    }

    /**
     * 获取表结构
     *
     * @param string $table
     * @return array
     */
    public function tableStruct(string $table): array
    {
        $sql = "SELECT * FROM `information_schema`.`columns` WHERE `TABLE_NAME` = '{$table}' AND `TABLE_SCHEMA` = '{$this->config['db']['database']}'";
        $data = $this->query($sql);
        return $data;
    }

    /**
     * 数据库备份文件列表
     *
     * @throws MigrateException
     * @return array
     */
    public function fileList(): array
    {
        // 检查文件是否可写
        if (!File::instance()->createDir($this->config['path'])) {
            throw new MigrateException("The current directory is not writable");
        }
        $path = realpath($this->config['path']);
        $glob = new FilesystemIterator($path, FilesystemIterator::KEY_AS_FILENAME);
        $list = [];
        foreach ($glob as $name => $file) {
            if (preg_match('/^\\d{8,8}-\\d{6,6}-\\d+\\.sql(?:\\.gz)?$/', $name)) {
                $info['filename'] = $name;
                $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');
                $date = "{$name[0]}-{$name[1]}-{$name[2]}";
                $time = "{$name[3]}:{$name[4]}:{$name[5]}";
                $part = $name[6];
                if (isset($list["{$date} {$time}"])) {
                    $info = $list["{$date} {$time}"];
                    $info['part'] = max($info['part'], $part);
                    $info['size'] = $info['size'] + $file->getSize();
                } else {
                    $info['part'] = $part;
                    $info['size'] = $file->getSize();
                }
                $extension = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                $info['compress'] = $extension === 'SQL' ? '-' : $extension;
                $info['time'] = strtotime("{$date} {$time}");
                $list["{$date} {$time}"] = $info;
            }
        }
        return $list;
    }

    /**
     * 获取备份的数据库文件信息
     * 
     * @param string $type  操作类型
     * @param integer $time 时间戳
     * @throws MigrateException
     * @return array|false|string
     */
    public function fileInfo(string $type = '', int $time = 0)
    {
        switch ($type) {
            case 'time':
                $name = date('Ymd-His', $time) . '-*.sql*';
                $path = realpath($this->config['path']) . DIRECTORY_SEPARATOR . $name;
                return glob($path);
            case 'timeverif':
                $name = date('Ymd-His', $time) . '-*.sql*';
                $path = realpath($this->config['path']) . DIRECTORY_SEPARATOR . $name;
                $files = glob($path);
                $list = [];
                foreach ($files as $name) {
                    $basename = basename($name);
                    $match = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
                    $gz = preg_match('/^\\d{8,8}-\\d{6,6}-\\d+\\.sql.gz$/', $basename);
                    $list[$match[6]] = [$match[6], $name, $gz];
                }
                $last = end($list);
                if (count($list) === $last[0]) {
                    return $list;
                } else {
                    throw new MigrateException("File [{$files['0']}] may be damaged, please check again");
                }
            case 'pathname':
                return "{$this->config['path']}{$this->file['name']}-{$this->file['part']}.sql";
            case 'filename':
                return "{$this->file['name']}-{$this->file['part']}.sql";
            case 'filepath':
                return $this->config['path'];
            default:
                $arr = [
                    'pathname' => "{$this->config['path']}{$this->file['name']}-{$this->file['part']}.sql",
                    'filename' => "{$this->file['name']}-{$this->file['part']}.sql",
                    'filepath' => $this->config['path'],
                    'file' => $this->file
                ];
                return $arr;
        }
    }

    /**
     * 删除备份文件
     *
     * @param integer $time 时间戳
     * @throws MigrateException
     * @return boolean
     */
    public function remove(int $time): bool
    {
        if (!$time) {
            throw new MigrateException("{$time} Time parameter is incorrect");
        }
        $filePathArr = $this->fileInfo('time', $time);
        array_map("unlink", $filePathArr);
        if (count($this->fileInfo('time', $time))) {
            throw new MigrateException("File {$time} deleted failed");
        }

        return true;
    }

    /**
     * 导出下载备份
     *
     * @param integer $time 文件名时间戳
     * @param integer $part
     * @throws MigrateException
     * @throws InvalidArgumentException
     * @return array
     */
    public function export(int $time, int $part = 0): array
    {
        $file = $this->fileInfo('time', $time);
        if (!isset($file[$part])) {
            throw new MigrateException("{$time} Part is abnormal");
        }
        $fileName = $file[$part];
        if (!file_exists($fileName)) {
            throw new MigrateException("{$time} File is abnormal");
        }

        return Tool::instance()->exportFile($fileName, basename($fileName));
    }

    /**
     * 导入数据库文件
     *
     * @param integer $time 文件名时间戳
     * @param integer $part
     * @throws MigrateException
     * @throws SqlException
     * @return true
     */
    public function import(int $time, int $part = 0): bool
    {
        $file = $this->fileInfo('time', $time);
        if (!isset($file[$part])) {
            throw new MigrateException("{$time} Part is abnormal");
        }
        $fileName = $file[$part];
        if (!file_exists($fileName)) {
            throw new MigrateException("{$time} File is abnormal");
        }
        // 读取sql文件
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        if (strtolower($ext) == 'gz') {
            $content = $this->read($fileName);
            $sqls = Sql::instance()->parseSql($content);
        } else {
            $sqls = Sql::instance()->parseFile($fileName);
        }
        // 执行sql
        foreach ($sqls as $sql) {
            $this->execute($sql);
        }

        return true;
    }

    /**
     * 备份表
     *
     * @param array $tables     表名列表，空则备份所有表
     * @param boolean $bakData  是否备份数据
     * @return array    备份失败的表名，空则表示成功
     */
    public function migrate(array $tables = [], bool $bakData = true): array
    {
        $error = [];
        if (empty($tables)) {
            $tableList = $this->tableList();
            $tables = array_column($tableList, 'name');
        }
        for ($i = 0, $l = count($tables); $i < $l; $i++) {
            $table = $tables[$i];
            $backup = $this->backup($table, 0, $bakData, ($i + 1 == $l));
            if ($backup === false) {
                $error[] = $table;
            }
        }

        return $error;
    }

    /**
     * 备份表
     *
     * @param string $table 表名
     * @param integer $start 起始位
     * @param boolean $bakData 是否备份数据
     * @param boolean $closeFp 是否关闭文件句柄
     * @return boolean
     */
    public function backup(string $table, int $start = 0, bool $bakData = true, bool $closeFp = true): bool
    {
        // 查询表结构
        $result = $this->query("SHOW CREATE TABLE `{$table}`");
        if (!$result) {
            if ($closeFp) {
                $this->close();
            }
            throw new MigrateException("{$table} not found!");
        }
        $tableInfo = $result[0];
        // 备份表结构
        if (0 == $start) {
            $sql = "\n";
            $sql .= "-- -----------------------------\n";
            // 获取建表语言或者视图语句
            if (isset($tableInfo['Create Table'])) {
                $sql .= "-- Table structure for `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= trim($tableInfo['Create Table']) . ";\n\n";
            } else if (isset($tableInfo['Create View'])) {
                $sql .= "-- View structure for `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $sql .= "DROP VIEW IF EXISTS `{$table}`;\n";
                $sql .= trim($tableInfo['Create View']) . ";\n\n";
            }
            if (false === $this->write($sql)) {
                if ($closeFp) {
                    $this->close();
                }
                return false;
            }
        }

        // 备份表数据
        if ($bakData && isset($tableInfo['Table'])) {
            // 数据总数
            $countData = $this->query("SELECT count(*) AS 'count' FROM `{$table}`");
            $count = isset($countData[0]) && isset($countData[0]['count']) ? $countData[0]['count'] : false;
            // 备份表数据
            if ($count) {
                // 写入数据注释
                if (0 == $start) {
                    $sql = "-- -----------------------------\n";
                    $sql .= "-- Records of `{$table}`\n";
                    $sql .= "-- -----------------------------\n";
                    $this->write($sql);
                }
                // 备份数据记录
                $start = intval($start);
                $result = $this->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
                foreach ($result as $row) {
                    $row = array_map('addslashes', $row);
                    $sql = "INSERT INTO `{$table}` VALUES ('" . str_replace(["\r", "\n"], ['\\r', '\\n'], implode("', '", $row)) . "');\n";
                    if (false === $this->write($sql)) {
                        if ($closeFp) {
                            $this->close();
                        }
                        return false;
                    }
                }
                // 还有更多数据
                if ($count > $start + 1000) {
                    return $this->backup($table, $start + 1000, $bakData, $closeFp);
                }
            }
        }
        // 备份完成
        if ($closeFp) {
            $this->close();
        }
        return true;
    }

    /**
     * 执行查询语句
     *
     * @param string $sql  SQL语句
     * @return array
     */
    public function query(string $sql): array
    {
        $query = $this->getDB()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 执行更新语句
     *
     * @param string $sql
     * @return integer
     */
    public function execute(string $sql): int
    {
        $query = $this->getDB()->prepare($sql);
        $query->execute();
        return $query->rowCount();
    }

    /**
     * 获取DB链接
     *
     * @throws PDOException
     * @return PDO
     */
    public function getDB(): PDO
    {
        if (!$this->db) {
            // 生成mysql连接dsn
            $config = $this->config['db'];
            $is_port = (isset($config['port']) && is_int($config['port'] * 1));
            $dsn = 'mysql:host=' . $config['host'] . ($is_port ? ';port=' . $config['port'] : '') . ';dbname=' . $config['database'];
            if (!empty($config['charset'])) {
                $dsn .= ';charset=' . $config['charset'];
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
                $config['username'],
                $config['password'],
                $params
            );
        }

        return $this->db;
    }

    /**
     * 读取gzip文件内容
     *
     * @param string $file 文件路径
     * @return string
     */
    protected function read(string $file): string
    {
        // 一次读取4kb的内容
        $buffer_size = 4096;
        $file = gzopen($file, 'rb');
        $str = '';
        while (!gzeof($file)) {
            $str .= gzread($file, $buffer_size);
        }
        gzclose($file);
        return $str;
    }

    /**
     * 写入SQL语句
     *
     * @param string $sql 要写入的SQL语句
     * @return boolean    true - 写入成功，false - 写入失败！
     */
    protected function write(string $sql): bool
    {
        $size = mb_strlen($sql, 'UTF-8');
        // 由于压缩原因，无法计算出压缩后的长度，这里假设压缩率为50%，
        // 一般情况压缩率都会高于50%；
        $size = $this->config['compress'] ? $size / 2 : $size;
        $this->open((int)$size);
        $ret = $this->config['compress'] ? gzwrite($this->fp, $sql) : fwrite($this->fp, $sql);
        return boolval($ret);
    }

    /**
     * 打开一个文件，用于写入数据
     *
     * @param integer $size 写入数据的大小
     * @return void
     */
    protected function open(int $size)
    {
        if ($this->fp) {
            $this->size += $size;
            if ($this->size > $this->config['part']) {
                $this->config['compress'] ? gzclose($this->fp) : fclose($this->fp);
                $this->fp = null;
                $this->file['part']++;
                $this->backupInit();
            }
        } else {
            $backuppath = $this->config['path'];
            $filename = "{$backuppath}{$this->file['name']}-{$this->file['part']}.sql";
            if ($this->config['compress']) {
                $filename = "{$filename}.gz";
                $this->fp = gzopen($filename, "a{$this->config['level']}");
            } else {
                $this->fp = fopen($filename, 'a');
            }
            $this->size = filesize($filename) + $size;
        }
    }

    /**
     * 关闭文件句柄
     *
     * @return void
     */
    protected function close()
    {
        if (!is_null($this->fp)) {
            $this->config['compress'] ? gzclose($this->fp) : fclose($this->fp);
            $this->fp = null;
        }
    }

    /**
     * 写入初始数据
     *
     * @return boolean true - 写入成功，false - 写入失败
     */
    protected function backupInit(): bool
    {
        $sql = "-- -----------------------------\n";
        $sql .= "-- Mon MySQL Data Migrate\n";
        $sql .= "-- \n";
        $sql .= "-- Host     : " . $this->config['db']['host'] . "\n";
        $sql .= "-- Port     : " . $this->config['db']['port'] . "\n";
        $sql .= "-- Database : " . $this->config['db']['database'] . "\n";
        $sql .= "-- UserName : " . $this->config['db']['username'] . "\n";
        $sql .= "-- \n";
        $sql .= "-- Version : 1.0.0";
        $sql .= "-- Part : #{$this->file['part']}\n";
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . "\n";
        $sql .= "-- -----------------------------\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        return $this->write($sql);
    }
}
