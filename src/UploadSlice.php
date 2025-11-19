<?php

declare(strict_types=1);

namespace mon\util;

use mon\util\exception\UploadException;

/**
 * 大文件分片上传
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1 优化代码 2022-07-08
 */
class UploadSlice
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 允许上传的文件后缀
        'exts'      => [],
        // 分片文件大小限制
        'sliceSize' => 0,
        // 保存根路径
        'rootPath'  => '',
        // 临时文件存储路径，基于rootPath
        'tmpPath'   => 'tmp'
    ];

    /**
     * 错误的分片序号
     *
     * @var array
     */
    protected $error_chunk = [];

    /**
     * 构造方法
     *
     * @param array $config 自定义配置信息
     */
    public function __construct(?array $config = [])
    {
        $this->config = array_merge($this->config, (array)$config);
    }

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 设置配置信息
     *
     * @param array|string $config  配置信息或配置节点  
     * @param mixed $value 值
     * @return UploadSlice
     */
    public function setConifg($config, $value = null): UploadSlice
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        } else {
            $this->config[$config] = $value;
        }

        return $this;
    }

    /**
     * 获取错误的分片序号
     *
     * @return array
     */
    public function getErrorChunk(): array
    {
        return $this->error_chunk;
    }

    /**
     * 保存上传的文件分片到临时文件目录
     *
     * @param string $fileID 文件唯一ID
     * @param integer $chunk 文件分片序号，从0递增到N
     * @param array $files 文件流，默认 $_FILES
     * @param string $name 文件流索引，默认 file
     * @throws UploadException
     * @return array 文件保存路径
     */
    public function upload(string $fileID, int $chunk = 0, string $name = 'file', ?array $files = null): array
    {
        // 支持外部传入 $files（用于测试）或默认 $_FILES
        if (is_null($files)) {
            $files = $_FILES;
        }
        if (empty($files) || !isset($files[$name])) {
            throw new UploadException('未上传文件', UploadException::ERROR_UPLOAD_FAILD);
        }

        // 基本路径检查
        $this->checkPath();

        // 文件信息并校验
        $file = $files[$name];
        $this->checkFile($file);

        // 清洗 fileID，避免路径穿越或非法字符
        $safeFileID = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', (string)$fileID);

        // 临时目录与文件名
        $fileName = md5($safeFileID) . '_' . intval($chunk);
        $tmpPath = rtrim($this->config['rootPath'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . trim($this->config['tmpPath'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeFileID;
        if (!File::createDir($tmpPath)) {
            throw new UploadException('创建临时文件存储目录失败', UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
        }

        $savePath = $tmpPath . DIRECTORY_SEPARATOR . $fileName;

        // 支持标准表单上传或 raw php://input 回退（某些分片客户端使用）
        if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            if (!move_uploaded_file($file['tmp_name'], $savePath)) {
                throw new UploadException('临时文件保存失败', UploadException::ERROR_UPLOAD_SAVE_FAILD);
            }
        } else {
            // 尝试从 php://input 读取到临时文件
            $input = fopen('php://input', 'rb');
            if ($input === false) {
                throw new UploadException('无法读取上传数据', UploadException::ERROR_UPLOAD_SAVE_FAILD);
            }
            $out = fopen($savePath, 'wb');
            if ($out === false) {
                fclose($input);
                throw new UploadException('临时文件保存失败', UploadException::ERROR_UPLOAD_SAVE_FAILD);
            }
            stream_copy_to_stream($input, $out);
            fclose($input);
            fclose($out);
        }

        return ['savePath' => $savePath, 'saveDir' => $tmpPath, 'fileName' => $fileName];
    }

    /**
     * 合并分片临时文件，生成上传文件
     *
     * @param string $fileID    文件唯一ID
     * @param integer $chunkLength  文件分片长度
     * @param string $fileName  保存文件名
     * @param string $saveDir   基于 rootPath 路径下的多级目录存储路径
     * @throws UploadException
     * @return array 文件保存路径
     */
    public function merge(string $fileID, int $chunkLength, string $fileName, string $saveDir = ''): array
    {
        // 临时目录
        $tmpPath = rtrim($this->config['rootPath'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . trim($this->config['tmpPath'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $fileID);
        if (!is_dir($tmpPath)) {
            throw new UploadException('临时文件不存在', UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
        }

        // 验证扩展名
        $ext = File::getExt($fileName);
        if (!empty($this->config['exts']) && !in_array($ext, $this->config['exts'], true)) {
            throw new UploadException('不支持文件保存类型', UploadException::ERROR_UPLOAD_EXT_FAILD);
        }

        // 目标保存目录准备
        $savePath = rtrim($this->config['rootPath'], DIRECTORY_SEPARATOR) . ($saveDir !== '' ? DIRECTORY_SEPARATOR . trim($saveDir, DIRECTORY_SEPARATOR) : '');
        if ($savePath !== '' && !is_dir($savePath)) {
            if (!File::createDir($savePath)) {
                throw new UploadException('创建文件存储目录失败', UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
            }
        }

        // 验证分片完整性（记录缺失分片但不立刻删除目录）
        $this->error_chunk = [];
        $chunkBase = md5($fileID) . '_';
        for ($i = 0; $i < $chunkLength; $i++) {
            $chunkPath = $tmpPath . DIRECTORY_SEPARATOR . $chunkBase . $i;
            if (!is_file($chunkPath)) {
                $this->error_chunk[] = $i;
            }
        }
        if (!empty($this->error_chunk)) {
            throw new UploadException('分片文件不完整: ' . implode(',', $this->error_chunk), UploadException::ERROR_CHUNK_FAILD);
        }

        // 合并：使用锁保护，先写入临时文件，完成后原子重命名
        $finalPath = ($savePath === '' ? rtrim($this->config['rootPath'], DIRECTORY_SEPARATOR) : $savePath) . DIRECTORY_SEPARATOR . $fileName;
        $tmpFinal = $finalPath . '.tmp_' . uniqid('', true);

        $lockFile = $tmpPath . DIRECTORY_SEPARATOR . '.merge.lock';
        $lockFp = fopen($lockFile, 'c');
        if ($lockFp === false) {
            throw new UploadException('无法创建合并锁文件', UploadException::ERROR_UPLOAD_SAVE_FAILD);
        }

        // 阻塞式获取独占锁，确保不会并发合并
        if (!flock($lockFp, LOCK_EX)) {
            fclose($lockFp);
            throw new UploadException('获取合并锁失败', UploadException::ERROR_UPLOAD_SAVE_FAILD);
        }

        $writerFp = fopen($tmpFinal, 'cb');
        if ($writerFp === false) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
            throw new UploadException('无法打开目标文件用于写入', UploadException::ERROR_UPLOAD_SAVE_FAILD);
        }

        try {
            // 按序拷贝每个 chunk 到目标临时文件，避免一次性载入内存
            for ($k = 0; $k < $chunkLength; $k++) {
                $chunkPath = $tmpPath . DIRECTORY_SEPARATOR . $chunkBase . $k;
                $readerFp = fopen($chunkPath, 'rb');
                if ($readerFp === false) {
                    throw new UploadException("读取分片失败: {$k}", UploadException::ERROR_CHUNK_FAILD);
                }
                // 以流方式拷贝
                stream_copy_to_stream($readerFp, $writerFp);
                fclose($readerFp);
                // 立即删除已合并的分片，节省磁盘空间
                File::removeFile($chunkPath);
            }
            fflush($writerFp);
            // 设置合并文件权限（可选）
            @chmod($tmpFinal, 0644);
            // 关闭写入句柄
            fclose($writerFp);
            // 原子重命名到最终位置
            if (!rename($tmpFinal, $finalPath)) {
                // 若重命名失败，尝试复制再删除
                if (!copy($tmpFinal, $finalPath)) {
                    throw new UploadException('合并文件重命名失败', UploadException::ERROR_UPLOAD_SAVE_FAILD);
                }
                @unlink($tmpFinal);
            }
            // 删除临时目录及锁
            File::removeDir($tmpPath);
        } finally {
            // 释放锁并关闭锁文件
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
            @unlink($lockFile);
            if (isset($writerFp) && is_resource($writerFp)) {
                fclose($writerFp);
            }
            // 若异常导致临时合并文件残留，尝试删除
            if (isset($tmpFinal) && is_file($tmpFinal)) {
                @unlink($tmpFinal);
            }
        }

        return ['savePath' => $finalPath, 'saveDir' => $savePath, 'fileName' => $fileName];
    }

    /**
     * 校验文件
     *
     * @param array $file 文件信息
     * @throws UploadException
     * @return boolean
     */
    protected function checkFile(array $file): bool
    {
        // 错误码检查（若存在）
        if (!empty($file['error'])) {
            throw new UploadException($this->uploadErrorMsg((int)$file['error']), UploadException::ERROR_UPLOAD_CHECK_FAILD);
        }
        // 名称校验
        if (empty($file['name'])) {
            throw new UploadException('未知上传错误', UploadException::ERROR_UPLOAD_NOT_MESSAGE);
        }
        // tmp_name 可能不存在（某些客户端直接上传流），若存在优先使用 is_uploaded_file 校验
        if (!empty($file['tmp_name'])) {
            if (!is_file($file['tmp_name'])) {
                throw new UploadException('上传临时文件不存在', UploadException::ERROR_UPLOAD_ILLEGAL);
            }
            // is_uploaded_file 更严格，若不是表单上传则仍允许（但可视场景按需开启）
            // if (!is_uploaded_file($file['tmp_name'])) {
            //     throw new UploadException('非法上传文件', UploadException::ERROR_UPLOAD_ILLEGAL);
            // }
        }
        // 分片大小限制（若配置了）
        if (!empty($this->config['sliceSize']) && isset($file['size']) && $file['size'] > $this->config['sliceSize']) {
            throw new UploadException('分片文件大小不符', UploadException::ERROR_UPLOAD_SIZE_FAILD);
        }

        return true;
    }

    /**
     * 检测上传根目录
     *
     * @throws UploadException
     * @return boolean
     */
    protected function checkPath(): bool
    {
        $rootPath = $this->config['rootPath'] ?? '';
        if ($rootPath === '') {
            throw new UploadException('未配置上传根目录', UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
        }
        if ((!is_dir($rootPath) && !File::createDir($rootPath)) || (is_dir($rootPath) && !is_writable($rootPath))) {
            throw new UploadException('上传文件保存目录不可写入：' . $rootPath, UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
        }
        $tmpPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim((string)$this->config['tmpPath'], DIRECTORY_SEPARATOR);
        if ((!is_dir($tmpPath) && !File::createDir($tmpPath)) || (is_dir($tmpPath) && !is_writable($tmpPath))) {
            throw new UploadException('上传文件临时保存目录不可写入：' . $tmpPath, UploadException::ERROR_UPLOAD_DIR_NOT_FOUND);
        }
        return true;
    }

    /**
     * 获取错误代码信息
     *
     * @param integer $errorNo  错误号
     * @return string
     */
    protected function uploadErrorMsg(int $errorNo): string
    {
        switch ($errorNo) {
            case 1:
                return '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
            case 2:
                return '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
            case 3:
                return '文件只有部分被上传';
            case 4:
                return '没有文件被上传';
            case 6:
                return '找不到临时文件夹';
            case 7:
                return '文件写入失败';
            default:
                return '未知上传错误';
        }
    }
}
