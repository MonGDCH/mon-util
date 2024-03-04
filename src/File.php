<?php

declare(strict_types=1);

namespace mon\util;

use DirectoryIterator;
use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * 文件操作类
 *
 * @author Mon <985558837@qq.com>
 * @version 1.1.2 优化注解，增加copyFile 2022-08-25
 * @version 1.1.3 精简代码 2022-09-16
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
     * @return array
     */
    public function formatByte(int $size, int $dec = 0): array
    {
        $type = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return [
            'size' => round($size, $dec),
            'type' => $type[$pos]
        ];
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
    public function changeAuth(string $file, string $type, $ch_info): bool
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
                throw new InvalidArgumentException('type prams invalid.[group|mode|ower]');
        }
    }

    /**
     * 创建目录
     *
     * @param  string $dirPath 目录路径
     * @return boolean
     */
    public function createDir(string $dirPath): bool
    {
        if (is_dir($dirPath)) {
            return true;
        }
        return mkdir($dirPath, 0755, true);
    }

    /**
     * 复制文件夹
     *
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     * @param boolean $overwrite   文件是否覆盖，默认不覆盖
     * @return void
     */
    public function copydir(string $source, string $dest, bool $overwrite = false)
    {
        $this->createDir($dest);
        $dir_iterator = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $sontDir = $dest . '/' . $iterator->getSubPathName();
                $this->createDir($sontDir);
            } else {
                $file = $dest . '/' . $iterator->getSubPathName();
                if (file_exists($file) && !$overwrite) {
                    continue;
                }

                copy($item, $file);
            }
        }
    }

    /**
     * 删除非空目录
     * 说明:只能删除非系统和特定权限的文件,否则会出现错误
     *
     * @param  string  $dirPath 目录路径
     * @param  boolean $all     是否删除所有
     * @return boolean
     */
    public function removeDir(string $dirPath, bool $all = false): bool
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
    public function getDirInfo(string $dir): array
    {
        $handle = @opendir($dir);
        $directory_count = 0;
        $total_size = 0;
        $file_cout = 0;
        while (false !== ($path = readdir($handle))) {
            if ($path != '.' && $path != '..') {
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
        closedir($handle);
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
     * @return array
     */
    public function getDirContent(string $dir): array
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException('dir path is not dir!');
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
     * @param  mixed  $content 写入内容
     * @param  string  $path    文件路径
     * @param  boolean $append  存在文件是否继续写入
     * @return boolean|integer
     */
    public function createFile($content, string $path, bool $append = true)
    {
        $dirPath = dirname($path);
        is_dir($dirPath) || $this->createDir($dirPath);
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
     * @return boolean
     */
    public function removeFile(string $path): bool
    {
        $path = $this->pathReplace($path);
        if (file_exists($path)) {
            return unlink($path);
        }

        return true;
    }

    /**
     * 复制文件
     *
     * @param string $source 源文件
     * @param string $dest   目标文件
     * @param boolean $overwrite   文件是否覆盖，默认不覆盖
     * @return boolean
     */
    public function copyFile(string $source, string $dest, bool $overwrite = false): bool
    {
        // 源文件不存在
        if (!file_exists($source)) {
            return false;
        }
        // 目标文件存在且不进行覆盖
        if (file_exists($dest) && !$overwrite) {
            return true;
        }
        // 创建目标文件目录
        if (!File::instance()->createDir(dirname($dest))) {
            return false;
        }
        return copy($source, $dest);
    }

    /**
     * 获取完整文件名称
     *
     * @param  string $path 目录路径
     * @return string
     */
    public function getBaseName(string $path): string
    {
        return basename(str_replace('\\', '/', $this->pathReplace($path)));
    }

    /**
     * 获取文件后缀名
     * 
     * @param  string $path 文件路径
     * @return string
     */
    public function getExt(string $path): string
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
    public function rename(string $oldFileName, string $newFileNmae): bool
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
     * @throws InvalidArgumentException
     * @return string
     */
    public function read(string $file): string
    {
        if (!file_exists($file)) {
            throw new InvalidArgumentException('File not found[' . $file . ']');
        }
        return file_get_contents($file);
    }

    /**
     * 获取文件信息
     *
     * @param  string $file 文件路径
     * @return array
     */
    public function getFileInfo(string $file): array
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
     * 获取文件mimetype
     *
     * @param string $file  文件路径
     * @param string $default  默认文件mimetype
     * @return string
     */
    public function getMimeType(string $file, string $default = 'application/octet-stream'): string
    {
        // 默认类型
        $mimeType = $default;
        if (function_exists('mime_content_type')) {
            // 使用 mime_content_type 函数，需要安装 Fileinfo 扩展
            $mimeType = mime_content_type($file);
        } else {
            // 使用自定义函数
            $mime_types = [
                'html' => 'text/html', 'htm' => 'text/html', 'shtml' => 'text/html', 'css' => 'text/css', 'xml' => 'text/xml', 'js' => 'application/javascript',
                'gif' => 'image/gif', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'tif' => 'image/tiff', 'tiff' => 'image/tiff',
                'wbmp' => 'image/vnd.wap.wbmp', 'ico' => 'image/x-icon', 'jng' => 'image/x-jng', 'bmp' => 'image/x-ms-bmp', 'svg' => 'image/svg+xml',
                'svgz' => 'image/svg+xml', 'webp' => 'image/webp', 'ttf' => 'font/ttf', 'mid' => 'audio/midi', 'midi' => 'audio/midi', 'kar' => 'audio/midi',
                'mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'm4a' => 'audio/x-m4a', 'ra' => 'audio/x-realaudio', '3gpp' => 'video/3gpp', '3gp' => 'video/3gpp',
                'ts' => 'video/mp2t', 'mp4' => 'video/mp4', 'mpeg' => 'video/mpeg', 'mpg' => 'video/mpeg', 'mov' => 'video/quicktime', 'webm' => 'video/webm',
                'flv' => 'video/x-flv', 'm4v' => 'video/x-m4v', 'mng' => 'video/x-mng', 'asx' => 'video/x-ms-asf', 'asf' => 'video/x-ms-asf', 'wmv' => 'video/x-ms-wmv',
                'avi' => 'video/x-msvideo', 'mml' => 'text/mathml', 'txt' => 'text/plain', 'php' => 'text/x-php', 'jad' => 'text/vnd.sun.j2me.app-descriptor',
                'wml' => 'text/vnd.wap.wml', 'htc' => 'text/x-component', 'atom' => 'application/atom+xml', 'rss' => 'application/rss+xml', 'woff' => 'application/font-woff',
                'jar' => 'application/java-archive', 'war' => 'application/java-archive', 'ear' => 'application/java-archive', 'json' => 'application/json',
                'hqx' => 'application/mac-binhex40', 'doc' => 'application/msword', 'pdf' => 'application/pdf', 'ps' => 'application/postscript',
                'eps' => 'application/postscript', 'ai' => 'application/postscript', 'rtf' => 'application/rtf', 'm3u8' => 'application/vnd.apple.mpegurl',
                'xls' => 'application/vnd.ms-excel', 'eot' => 'application/vnd.ms-fontobject', 'ppt' => 'application/vnd.ms-powerpoint', 'wmlc' => 'application/vnd.wap.wmlc',
                'kml' => 'application/vnd.google-earth.kml+xml', 'kmz' => 'application/vnd.google-earth.kmz', '7z' => 'application/x-7z-compressed',
                'cco' => 'application/x-cocoa', 'jardiff' => 'application/x-java-archive-diff', 'jnlp' => 'application/x-java-jnlp-file', 'run' => 'application/x-makeself',
                'pl' => 'application/x-perl', 'pm' => 'application/x-perl', 'prc' => 'application/x-pilot', 'pdb' => 'application/x-pilot', 'rar' => 'application/x-rar-compressed',
                'rpm' => 'application/x-redhat-package-manager', 'sea' => 'application/x-sea', 'swf' => 'application/x-shockwave-flash', 'sit' => 'application/x-stuffit',
                'tcl' => 'application/x-tcl', 'tk' => 'application/x-tcl', 'der' => 'application/x-x509-ca-cert', 'pem' => 'application/x-x509-ca-cert',
                'crt' => 'application/x-x509-ca-cert', 'xpi' => 'application/x-xpinstall', 'xhtml' => 'application/xhtml+xml', 'xspf' => 'application/xspf+xml',
                'zip' => 'application/zip', 'bin' => 'application/octet-stream', 'exe' => 'application/octet-stream', 'dll' => 'application/octet-stream',
                'deb' => 'application/octet-stream', 'dmg' => 'application/octet-stream', 'iso' => 'application/octet-stream', 'img' => 'application/octet-stream',
                'msi' => 'application/octet-stream', 'msp' => 'application/octet-stream', 'msm' => 'application/octet-stream',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ];
            $ext = strtolower($this->getExt($file));
            if (isset($mime_types[$ext])) {
                // 存在支持的mimeType类型
                $mimeType = $mime_types[$ext];
            } elseif (function_exists('finfo_file')) {
                // 使用 finfo_file 函数，性能较差，做最后选择
                $finfo = finfo_open(FILEINFO_MIME);
                $mimeType = finfo_file($finfo, $file);
                finfo_close($finfo);
            }
        }

        return $mimeType;
    }

    /**
     * 获取路径下所有的内容及后代内容
     *
     * @param string  $path  路径
     * @param boolean $tree  输出树结构还是数组
     * @return array
     */
    public function getFoldersContent(string $path, bool $tree = false): array
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
     * @return array
     */
    protected function directoryIteratorToArray(DirectoryIterator $dir): array
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
     * @return array
     */
    protected function directoryIteratorToTree(DirectoryIterator $dir): array
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
     * 路径替换相应的字符
     *
     * @param string $path 路径
     * @return string
     */
    protected function pathReplace(string $path): string
    {
        return str_replace('//', '/', str_replace('\\', '/', $path));
    }
}
