<?php

namespace mon\util;

use RuntimeException;
use DirectoryIterator;
use InvalidArgumentException;

/**
 * 文章操作类
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0
 * @version 1.1 优化代码
 */
class File
{
    use Instance;

    /**
     * 字节格式化 把字节数格式为 B K M G T P E Z Y 描述的大小
     *
     * @param integer $size 大小
     * @param integer $dec 精准度，小数位数
     * @param boolean $toString 输出字符串
     * @return array|string
     */
    public function formatByte($size, $dec = 0, $toString = true)
    {
        $type = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        $result = [
            'size'  => round($size, $dec),
            'type'  => $type[$pos]
        ];
        return $toString ? ($result['size'] . ' ' . $result['type']) : $result;
    }

    /**
     * 改变文件和目录的相关属性
     *
     * @param string $file 文件路径
     * @param string $type 操作类型, 支持：group、mode、ower
     * @param mixed  $ch_info 操作信息
     * @throws InvalidArgumentException
     * @return boolean
     */
    public function changeAuth($file, $type, $ch_info)
    {
        switch ($type) {
            case 'group':
                // 改变文件组。
                return chgrp($file, $ch_info);
            case 'mode':
                // 改变文件模式。
                return chmod($file, $ch_info);
            case 'ower':
                // 改变文件所有者。
                return chown($file, $ch_info);
            default:
                throw new InvalidArgumentException("type prams invalid.[group|mode|ower]");
        }
    }

    /**
     * 获取上传文件信息
     *
     * @param  string $field $_FILES 字段索引
     * @return array
     */
    public function uploadFileInfo($field)
    {
        // 取得上传文件基本信息
        $fileInfo = $_FILES[$field];
        $info = [];
        // 取得文件类型
        $info['type'] = strtolower(trim(stripslashes(preg_replace("/^(.+?);.*$/", "\\1", $fileInfo['type'])), '"'));
        // 取得上传文件在服务器中临时保存目录
        $info['temp'] = $fileInfo['tmp_name'];
        // 取得上传文件大小
        $info['size'] = $fileInfo['size'];
        // 取得文件上传错误
        $info['error'] = $fileInfo['error'];
        // 取得上传文件名
        $info['name'] = $fileInfo['name'];
        // 取得上传文件后缀
        $info['ext'] = $this->getExt($fileInfo['name']);
        return $info;
    }

    /**
     * 创建目录
     *
     * @param  string $dirPath 目录路径
     * @return boolean
     */
    public function createDir($dirPath)
    {
        return !is_dir($dirPath) && mkdir($dirPath, 0755, true);
    }

    /**
     * 删除非空目录
     * 说明:只能删除非系统和特定权限的文件,否则会出现错误
     *
     * @param  string  $dirPath 目录路径
     * @param  boolean $all     是否删除所有
     * @return boolean
     */
    public function removeDir($dirPath, $all = false)
    {
        $dirName = $this->pathReplace($dirPath);
        $handle = @opendir($dirName);
        while (($file = @readdir($handle)) !== FALSE) {
            if ($file != '.' && $file != '..') {
                $dir = $dirName . '/' . $file;
                if ($all) {
                    is_dir($dir) ? $this->removeDir($dir) : $this->removeFile($dir);
                } else {
                    if (is_file($dir)) {
                        $this->removeFile($dir);
                    }
                }
            }
        }
        closedir($handle);
        return @rmdir($dirName);
    }

    /**
     * 获取指定目录的信息
     *
     * @param  string $dir  目录路径
     * @return array
     */
    public function getDirInfo($dir)
    {
        $handle = @opendir($dir); //打开指定目录
        $directory_count = 0;
        $total_size = 0;
        $file_cout = 0;
        while (false !== ($path = readdir($handle))) {
            if ($path != "." && $path != "..") {
                $next_path = $dir . '/' . $path;
                if (is_dir($next_path)) {
                    $directory_count++;
                    $result_value = $this->getDirInfo($next_path);
                    $total_size += $result_value['size'];
                    $file_cout += $result_value['filecount'];
                    $directory_count += $result_value['dircount'];
                } elseif (is_file($next_path)) {
                    $total_size += filesize($next_path);
                    $file_cout++;
                }
            }
        }
        closedir($handle); //关闭指定目录
        $result_value['size'] = $total_size;
        $result_value['filecount'] = $file_cout;
        $result_value['dircount'] = $directory_count;
        return $result_value;
    }

    /**
     * 获取目录内容
     *
     * @param  string $dir 目录路径
     * @throws InvalidArgumentException
     * @return array|false
     */
    public function getDirContent($dir)
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("dir path is not dir!");
        }
        //遍历目录取得文件信息
        $data = [];
        if ($handle = opendir($dir)) {
            $i = 0;
            while (false !== ($filename = readdir($handle))) {
                if (mb_strpos($filename, '.') === 0) {
                    continue;
                }
                $file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                if (is_dir($file)) {
                    // 是否文件夹
                    $data[$i]['is_dir'] = true;
                    // 文件夹是否包含文件
                    $data[$i]['has_file'] = (count(scandir($file)) > 2);
                    // 文件大小
                    $data[$i]['filesize'] = 0;
                    // 文件类别，用扩展名判断
                    $data[$i]['filetype'] = '';
                } else {
                    $data[$i]['is_dir'] = false;
                    $data[$i]['has_file'] = false;
                    $data[$i]['filesize'] = filesize($file);
                    $data[$i]['filetype'] = $this->getExt($file);
                }
                // 文件名，包含扩展名
                $data[$i]['filename'] = $filename;
                // 文件最后修改时间
                $data[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file));
                $i++;
            }
            closedir($handle);
        }

        return $data;
    }

    /**
     * 创建文件
     *
     * @param  string  $content 写入内容
     * @param  string  $path    文件路径
     * @param  boolean $append  存在文件是否继续写入
     * @return boolean
     */
    public function createFile($content, $path, $append = true)
    {
        $dirPath = dirname($path);
        is_dir($dirPath) or $this->createDir($dirPath);
        if ($append) {
            // 添加写入
            return file_put_contents($path, $content, FILE_APPEND);
        } else {
            // 重新写入
            return file_put_contents($path, $content);
        }
    }

    /**
     * 删除文件
     *
     * @param  string $path 文件路径
     * @return mixed
     */
    public function removeFile($path)
    {
        $path = $this->pathReplace($path);
        if (file_exists($path)) {
            return unlink($path);
        }
    }

    /**
     * 获取完整文件名称
     *
     * @param  string $path 目录路径
     * @return string
     */
    public function getBaseName($path)
    {
        return basename(str_replace('\\', '/', $this->pathReplace($path)));
    }

    /**
     * 获取文件后缀名
     * 
     * @param  string $path 文件路径
     * @return string
     */
    public function getExt($path)
    {
        return pathinfo($this->pathReplace($path), PATHINFO_EXTENSION);
    }

    /**
     * 重命名文件
     *
     * @param  string $oldFileName 旧名称
     * @param  string $newFileNmae 新名称
     * @return boolean
     */
    public function rename($oldFileName, $newFileNmae)
    {
        if (($oldFileName != $newFileNmae) && is_writable($oldFileName)) {
            return rename($oldFileName, $newFileNmae);
        }

        return false;
    }

    /**
     * 读取文件内容
     *
     * @param  string $file 文件路径
     * @return string
     */
    public function read($file)
    {
        return file_get_contents($file);
    }

    /**
     * 获取文件信息
     *
     * @param  string $file 文件路径
     * @return array
     */
    public function getFileInfo($file)
    {
        $info = [];
        // 返回路径中的文件名部分
        $info['filename']   = basename($file);
        // 返回绝对路径名
        $info['pathname']   = realpath($file);
        // 文件的 user ID （所有者）
        $info['owner']      = fileowner($file);
        // 返回文件的 inode 编号
        $info['perms']      = fileperms($file);
        // 返回文件的 inode 编号 
        $info['inode']      = fileinode($file);
        // 返回文件的组 ID
        $info['group']      = filegroup($file);
        // 返回路径中的目录名称部分
        $info['path']       = dirname($file);
        // 返回文件的上次访问时间
        $info['atime']      = fileatime($file);
        // 返回文件的上次改变时间
        $info['ctime']      = filectime($file);
        // 返回文件的权限 
        $info['perms']      = fileperms($file);
        // 返回文件大小
        $info['size']       = filesize($file);
        // 返回文件类型
        $info['type']       = filetype($file);
        // 返回文件后缀名
        $info['ext']        = is_file($file) ? pathinfo($file, PATHINFO_EXTENSION) : '';
        // 返回文件的上次修改时间
        $info['mtime']      = filemtime($file);
        // 判断指定的文件名是否是一个目录
        $info['isDir']      = is_dir($file);
        // 判断指定文件是否为常规的文件
        $info['isFile']     = is_file($file);
        // 判断指定的文件是否是连接
        $info['isLink']     = is_link($file);
        // 判断文件是否可读
        $info['isReadable'] = is_readable($file);
        // 判断文件是否可写
        $info['isWritable'] = is_writable($file);
        // 判断文件是否是通过 HTTP POST 上传的
        $info['isUpload']   = is_uploaded_file($file);
        return $info;
    }

    /**
     * 分卷记录文件
     *
     * @param  string  $content 记录的内容
     * @param  string  $path    保存的路径, 不含后缀
     * @param  integer $maxSize 文件最大尺寸
     * @param  string  $rollNum 分卷数
     * @param  string  $postfix 文件后缀
     * @throws RuntimeException
     * @return mixed
     */
    public function subsectionFile($content, $path, $maxSize = 20480000, $rollNum = 3, $postfix = '.log')
    {
        $destination = $path . $postfix;
        $contentLength = mb_strlen($content);
        // 判断写入内容的大小
        if ($contentLength > $maxSize) {
            throw new RuntimeException("Save content size cannot exceed {$maxSize}, content size: {$contentLength}");
        }
        // 判断记录文件是否已存在，存在时文件大小不足写入
        elseif (file_exists($destination) && floor($maxSize) < (filesize($destination) + $contentLength)) {
            // 超出剩余写入大小，分卷写入
            $this->shiftFile($path, (int) $rollNum, $postfix);
            return $this->createFile($content, $destination, false);
        }
        // 不存在文件或文件大小足够继续写入
        else {
            return $this->createFile($content, $destination);
        }
    }

    /**
     * 获取路径下所有的内容及后代内容
     *
     * @param string  $path  路径
     * @param boolean $tree  输出树结构还是数组
     * @return mixed
     */
    public function getFoldersContent($path, $tree = false)
    {
        if ((!file_exists($path) || !is_dir($path))) {
            return [];
        }
        $dir = new DirectoryIterator($path);

        return $tree ? $this->directoryIteratorToTree($dir) : $this->directoryIteratorToArray($dir);
    }

    /**
     * 获取路径下所有的内容及后代内容转数组辅助方法
     *
     * @param DirectoryIterator $dir
     * @return mixed
     */
    protected function directoryIteratorToArray(DirectoryIterator $dir)
    {
        $result = [];
        foreach ($dir as $key => $child) {
            if ($child->isDot()) {
                continue;
            }
            $name = $child->getBasename();
            if ($child->isDir()) {
                $subit = new DirectoryIterator($child->getPathname());
                $result[$name] = $this->DirectoryIteratorToArray($subit);
            } else {
                $result[] = $name;
            }
        }
        return $result;
    }

    /**
     * 获取路径下所有的内容及后代内容转树结构辅助方法
     *
     * @param DirectoryIterator $dir
     * @return mixed
     */
    protected function directoryIteratorToTree(DirectoryIterator $dir)
    {
        $result = [];
        foreach ($dir as $key => $child) {
            if ($child->isDot()) {
                continue;
            }
            $name = $child->getBasename();
            if ($child->isDir()) {
                $path = $child->getPathname();
                $subit = new DirectoryIterator($path);
                $result[$key] = [
                    'children'  => $this->directoryIteratorToTree($subit),
                    'title'     => $name,
                    'path'      => $path,
                ];
            } else {
                $result[$key] = [
                    'title'     => $name,
                    'path'      => $child->getPathname(),
                ];
            }
        }
        return $result;
    }

    /**
     * 分卷重命名文件
     *
     * @param  string $path    文件路径
     * @param  int    $rollNum 分卷数
     * @param  string $postfix 后缀名
     * @throws RuntimeException
     * @return void
     */
    protected function shiftFile($path, $rollNum, $postfix = '.log')
    {
        // 判断是否存在最老的一份文件，存在则删除
        $oldest = $this->buildShiftName($path, ($rollNum - 1));
        $oldestFile = $oldest . $postfix;
        if (!$this->removeFile($oldestFile)) {
            throw new RuntimeException("Failed to delete old file, oldFileName: {$oldestFile}");
        }

        // 循环重命名文件
        for ($i = ($rollNum - 2); $i >= 0; $i--) {
            // 最新的一卷不需要加上分卷号
            if ($i == 0) {
                $oldFile = $path;
            }
            // 获取分卷号文件名称
            else {
                $oldFile = $this->buildShiftName($path, $i);
            }

            // 重命名文件
            $oldFileName = $oldFile . $postfix;
            if (file_exists($oldFileName)) {
                $newFileNmae = $this->buildShiftName($path, ($i + 1)) . $postfix;
                // 重命名
                if (!$this->rename($oldFile, $newFileNmae)) {
                    throw new RuntimeException("Failed to rename volume file name, oldFileName: {$oldFileName}, newFileNmae: {$newFileNmae}");
                }
            }
        }
    }

    /**
     * 构造分卷文件名称
     *
     * @param  string  $fileName 文件名称，不含后缀
     * @param  integer $num      分卷数
     * @return string
     */
    protected function buildShiftName($fileName, $num)
    {
        return $fileName . '_' . $num;
    }

    /**
     * 路径替换相应的字符
     *
     * @param string $path 路径
     * @return string
     */
    protected function pathReplace($path)
    {
        return str_replace('//', '/', str_replace('\\', '/', $path));
    }
}
