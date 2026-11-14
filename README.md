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

> 当前版本 `1.7.1`，包含若干行为调整（`Gif` 异常化、`Tool` 移除两个静态缓存方法、`IdCard::$dataFile` 私有化、`Container` 魔术访问限制等），升级前请先阅读 [CHANGELOG.md](CHANGELOG.md) 的「不兼容变更」。

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

// 移除已缓存的实例（保留绑定关系），用于常驻进程清理请求级服务
Container::instance()->remove('db');
// 清理实例缓存；传入 true 时同时清空绑定关系
Container::instance()->clear();

// 反射执行类方法，支持参数依赖注入（格式：类名@方法名）
Container::instance()->invokeMethod('Test@say', ['name' => 'mon']);
```

> 类不存在时抛出 `mon\util\exception\NotFoundException`（实现 PSR-11 的 `NotFoundExceptionInterface`），反射实例化失败时抛出 `mon\util\exception\ContainerException`。

> 魔术访问（`$container->db`、`$container->db()`）仅对**已绑定**的标识生效，未绑定时抛出 `NotFoundException`，避免用户输入驱动任意类实例化；需要实例化未绑定的类请显式调用 `get()`。

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

> 单例按 `static::class` 分桶存储，父类与子类各自持有独立实例，`Child::instance()` 不会拿到父类实例。

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

$idcard->setDataFile('/path/to/idcard.php');   // 更换扩展数据文件
$idcard->getDataFile();                        // 获取当前数据文件路径
```

### 多语言

```php
use mon\util\Lang;

$lang = Lang::instance();
$lang->set('hello', '你好', 'zh-cn');    // 写入语言包
$lang->range('zh-cn');                   // 切换当前语言
$lang->get('hello');                     // 获取翻译
$lang->detect();                         // 依据 Accept-Language 自动侦测
$lang->reset();                          // 复位为默认语言（常驻进程请在请求结束时调用）
```

### 网络请求

```php
use mon\util\Network;

// HTTP请求（2xx 均视为成功，$toJson 为 true 时解析 JSON）
$res = Network::sendHTTP('https://api.example.com/user', ['id' => 1]);

// 第10个参数 $sslVerify 控制是否校验服务端TLS证书，默认 false（不校验）
$res = Network::sendHTTP('https://api.example.com/user', ['id' => 1], 'GET', [], true, 5, '', '', '', true);

// 批量并发请求，默认参数中的 $sslVerify 可被单项 ssl_verify 覆盖
$list = Network::sendMultiHTTP([
    ['url' => 'https://a.example.com/1', 'ssl_verify' => true],
    ['url' => 'https://b.example.com/2', 'ssl_verify' => false],
], 5);
// $list = ['success' => [...], 'error' => [...]]

// 模拟表单上传文件
Network::sendFile('https://api.example.com/upload', '/path/to/a.zip', ['uid' => 1]);

// TCP / UDP，$toJson 解析返回内容，$close 控制是否关闭连接（false 时复用连接）
Network::sendTCP('127.0.0.1', 9501, "ping\n", true, 3, 1024, true);
Network::sendUDP('127.0.0.1', 9502, 'ping', true, 3, 1024, true);
```

> 开启 TLS 校验前请确保 PHP 已配置 CA 证书包（`curl.cainfo` 或 `openssl.cafile`），否则会因 `unable to get local issuer certificate` 请求失败。

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

## 常驻进程（Workerman 等）使用注意

本库的部分类为单例或持有静态状态，在常驻进程中会被跨请求复用，使用前请对应处理：

* `Container`：实例缓存默认上限 1024 条（`setMaxService()` 可调），可用 `remove($id)`、`clear()` 在请求结束时清理请求级服务。**不要**把用户输入作为容器标识符使用。
* `Lang`：`range()` 是请求级状态，请求结束后调用 `Lang::instance()->reset()` 复位（`setDefaultRange()` 可改默认语言）。
* `View`：实例可复用，渲染时已自动清理继承关系与片段，但模板中 `block()` 未闭合时请确保异常被捕获在业务层。
* `IdCard`：数据文件路径通过 `setDataFile()` 设置，不再允许直接改写属性。
* `IPLocation`：`IPLocation::instance($dbPath)` 每次传入路径都会按该路径重新初始化，避免首个路径被固化。
* `SnowFlake`：多进程部署时每个 worker 进程必须使用不同的 `workerId`/`datacenterId`（在 `onWorkerStart` 中设置），否则会生成重复 ID。
* 依赖 `$_SERVER`、`$_FILES`、`$_COOKIE`、`header()`、`setcookie()`、`ob_*` 的方法（`Tool::ip()`、`Tool::createTicket()`、`Tool::exportCsv()`、`Image::output()`、`UploadFile` 等）为传统 FPM 场景设计，常驻进程下需改用框架的请求/响应对象。
* `HttpRequest` 上传文件会通过 `tempnam()` 落盘，请在使用完毕后自行 `unlink()` 清理。
* `Gif`、`Image` 处理非法/截断的图片数据时抛出 `ImgException`（不再 `printf` + `exit(0)`），请按异常方式捕获，避免 worker 进程被直接终止。

## 安全注意事项

* `Network` 与 `Tool::download` 对 HTTPS 请求默认**不校验证书**（`SSL_VERIFYPEER=false`），生产环境涉及敏感数据时请自行评估并加强。`Network::sendHTTP()`、`sendMultiHTTP()`、`sendFile()`、`getRequest()` 均已支持末尾传入 `$sslVerify=true` 开启证书校验（批量请求可用单项 `ssl_verify` 覆盖）；开启前需确保 PHP 已配置 CA 证书包（`curl.cainfo` 或 `openssl.cafile`），否则会因 `unable to get local issuer certificate` 失败。
* `UploadSlice::merge`、`Tool::download` 的文件名与保存路径参数直接参与路径拼接，务必在业务侧校验。
* `OS` 类依赖 `shell_exec`/`exec` 函数获取系统信息，部署时请注意相关函数是否被禁用。

## 更新日志

请查阅 [CHANGELOG.md](CHANGELOG.md)

## 致谢

感谢您的支持和阅读，如果有什么不足的地方或者建议还请@我，如果你觉得对你有帮助的话还请给个star。

## 关于

作者邮箱： 985558837@qq.com
