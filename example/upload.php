<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>upfile</title>
</head>
<body>
    <?php
    require __DIR__ . '/../vendor/autoload.php';
    if ($_POST) {
        $file = new \mon\util\UploadFile([
            'rootPath'  => __DIR__ . '/upload/',
            'exts'      => ['jpg']
        ]);

        try {
            // 获取上传文件信息
            $info = $file->upload()->getFile();
            var_dump($file);
            $save = $file->save()->getFile();
            var_dump($save);
        } catch (\mon\util\exception\UploadException $e) {
            var_dump($e->getMessage(), $e->getCode());
        }
    } else {
        ?>
        <form action="" method='post' enctype="multipart/form-data">
            <input type="file" name="file" id="file" />
            <input type="hidden" name="sf" value="sf" />
            <input type="submit" value="上传" name="sub" />
        </form>
        <?php
    }
    ?>
</body>
</html>