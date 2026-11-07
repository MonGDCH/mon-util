# mon-util

> PHP常用工具、辅助类集合，包含各种常用的类库

## 环境要求

* PHP >= 8.0
* 部分功能依赖以下扩展，请按需安装：
  * `bcmath`：Lottery（概率计算）、Nbed64
  * `curl`：Network、Tool::download
  * `gd`：Image、Gif、Qrcode
  * `fileinfo`：UploadFile（MIME 检测）
  * `sockets`：Network 的 TCP/UDP 功能

## 安装

```bash
composer require mongdch/mon-util
```

## 功能清单

**核心**

* Container类，对象容器，符合 PSR-11 标准
* Instance类，trait单例实现，兼容Container容器
* Pipeline类，管道，适合中间件式编排
* Event类，事件hook类
* Collection类，数组集合类
* Lang类，多语言操作
* View类，视图字符串操作类

**常用工具**

* Tool类，常用工具类库函数
* Common类，公共工具函数库
* Date类，时间操作
* Tree类，树结构操作
* File类，文件操作
* OS类，操作系统相关工具类
* Validate类，验证器
* IdCard类，处理身份证相关的业务工具类
* Dictionary类，Mysql数据字典生成
* Sql类，解析SQL文件，获取sql操作语句
* DocParse类，解析PHP类对象注解，生成文档
* Lottery类，概率抽奖工具类
* SnowFlake类，雪花ID生成类
* Spids类，生成简短唯一ID类
* Minifier类，HTML、JS、CSS、VUE、PHP文件代码压缩工具
* Nbed64类，Nbed64加密算法类
* Obfuscator类，PHP代码混淆工具

**文件与图片**

* UploadFile类，文件上传
* UploadImg类，base64图片上传
* UploadSlice类，处理大文件分片上传
* Image类，图片处理工具
* Gif类，辅助Image类进行GIF图片处理
* Qrcode类，用于生成图片二维码

**网络与迁移**

* Network类，网络客户端工具类，支持HTTP、TCP、UDP
* HttpRequest类，HTTP请求信息封装
* HttpResponse类，HTTP响应封装
* IPLocation类，基于纯真IP库的ip地址定位
* Migrate类，Mysql数据库备份迁移

## 使用示例

### 服务容器

```php
use mon\util\Container;

// 绑定服务（支持数组批量绑定，值为闭包、类名或实例）
Container::instance()->set('db', MyDb::class);

// 获取实例，支持构造方法依赖注入，默认单例缓存
$db = Container::gen('db');
// 重新获取新实例
$db2 = Container::gen('db', [], true);

// 判断服务是否存在
Container::instance()->has('db');

// 反射执行类方法，支持参数依赖注入（格式：类名@方法名）
Container::instance()->invokeMethod('Test@say', ['name' => 'mon']);
```

> 类不存在时抛出 `mon\util\exception\NotFoundException`（实现 PSR-11 的 `NotFoundExceptionInterface`），反射实例化失败时抛出 `mon\util\exception\ContainerException`。

### 单例 trait

```php
use mon\util\Instance;

class Service
{
    use Instance;

    protected function __construct(int $id = 0) {}
}

Service::instance();                 // 获取单例
Service::createInstance([1], true);  // 强制创建新实例
```

### 验证器

```php
use mon\util\Validate;

$validate = new Validate();
$result = $validate->rule([
        'name'  => ['required', 'length:3,20'],
        'phone' => ['required', 'mobile'],
        'code'  => ['postal'],
    ])
    ->check($data);

if (!$result) {
    var_dump($validate->getError());
}
```

内置常用验证规则：`mobile`、`tel`、`email`、`postal`、`china`、`alpha`、`car_num`、`pay_card`、`required`、`length` 等，详见 `Validate` 类源码。

### 文件操作

```php
use mon\util\File;

File::createFile('内容', '/path/to/file.txt');   // 写入文件（默认追加）
File::read('/path/to/file.txt');                 // 读取文件
File::createDir('/path/to/dir');                 // 创建目录
File::copyFile($source, $dest);                  // 复制文件
File::getExt('/path/to/file.txt');               // 获取后缀名
File::removeFile('/path/to/file.txt');           // 删除文件（内部已做路径归一化，防穿越）
```

### 雪花ID

```php
use mon\util\SnowFlake;

$snowflake = SnowFlake::instance();
// 数据中心ID、机器ID，多实例部署必须区分，范围均为 0-31
$snowflake->setConfig(1, 1);
echo $snowflake->createID();   // 如：415724538895360001
```

### 概率抽奖

```php
use mon\util\Lottery;

$lottery = Lottery::gen([
    ['title' => '一等奖', 'probability' => 1],
    ['title' => '二等奖', 'probability' => 9],
    // 概率总和小于 100 时，剩余概率自动补充为未中奖奖项
]);

$result = $lottery->draw();   // isWin 为 true 表示中奖
```

> 概率精确到 4 位小数（`scale` 可调），底层使用 `random_int` 与 bcmath 高精度计算，概率总和必须小于等于 100。

### 身份证

```php
use mon\util\IdCard;

$idcard = new IdCard();
$idcard->check('110101199003077777');   // 校验合法性（位数、省份码、生日、校验码）
$idcard->getProvinces($id);             // 获取省份
$idcard->getCity($id);                  // 获取城市
$idcard->getLocation($id);              // 获取地区信息
```

### 大文件分片上传

```php
use mon\util\UploadSlice;

$upload = new UploadSlice([
    'rootPath'  => './public/upload',   // 保存根路径
    'tmpPath'   => 'tmp',               // 分片临时目录，基于rootPath
    'exts'      => ['mp4', 'zip'],      // 允许的文件后缀
    'sliceSize' => 4 * 1024 * 1024,     // 分片大小限制
]);

// 保存分片：fileID 为本次上传唯一标识，chunk 从 0 递增
$upload->upload($fileID, $chunk);

// 全部分片上传完成后合并
$result = $upload->merge($fileID, $chunkLength, $fileName, $saveDir);
```

> 并发合并安全（内部使用文件锁），合并采用流式拷贝与原子重命名，不会占用大量内存。`merge` 的 `$fileName` 与 `$saveDir` 参数会参与最终保存路径拼接，请务必在业务侧校验后再传入。

### 助手函数

```php
dd($var);                        // 调试输出（浏览器友好）
collection($arr)->column('id');  // 数组集合
check('mobile', '13800138000');  // 快捷验证，支持Validate所有规则
buildURL('/index', ['id' => 1]); // 构建URL
```

## 安全注意事项

* `Network` 与 `Tool::download` 对 HTTPS 请求默认**不校验证书**（`SSL_VERIFYPEER=false`），生产环境涉及敏感数据时请自行评估并加强。
* `UploadSlice::merge`、`Tool::download` 的文件名与保存路径参数直接参与路径拼接，务必在业务侧校验。
* `OS` 类依赖 `shell_exec`/`exec` 函数获取系统信息，部署时请注意相关函数是否被禁用。

## 更新日志

请查阅 [CHANGELOG.md](CHANGELOG.md)

## 致谢

感谢您的支持和阅读，如果有什么不足的地方或者建议还请@我，如果你觉得对你有帮助的话还请给个star。

## 关于

作者邮箱： 985558837@qq.com
