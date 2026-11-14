### 更新日志

> 所有值得注意的版本信息都将记录在该文件中

#### [1.7.1] (2026-11-14)

> 本次更新主要针对常驻进程（Workerman 等）场景，修复会导致进程退出、跨请求状态串用及静默数据错误的问题

**不兼容变更**

- `Gif`：`GIFEncoder` 构造方法不再 `printf` + `exit(0)`，参数或图像数据非法时抛出 `ImgException`，避免杀死常驻进程；同时对截断的 GIF 数据增加越界检查，修复死循环
- `Tool`：移除 `requireCache()`、`includeCache()` 方法（静态缓存无上限导致内存泄漏、文件变更不生效），请直接使用 `require`/`include`
- `IdCard`：`$dataFile` 属性由 `public` 改为 `protected`，新增 `setDataFile()`、`getDataFile()`
- `Collection`：`max()`、`min()` 返回类型放宽为 `int|float|null`（空集合返回 `null`，此前会抛 `TypeError`）
- `Date`：`getDayOfWeek()` 返回本周对应日期（此前 `strtotime('monday')` 返回的是下周一）
- `Tool::exportExcel()`：单元格校验条件修正后，非法单元格（既无 `text` 也无 `img`）将抛出异常，此前会静默输出空单元格
- `Container`：魔术访问 `__get()`、`__call()` 仅允许已绑定的标识，未绑定时抛出 `NotFoundException`；实例化未绑定的类请显式调用 `get()`

**新增**

- `Network::sendHTTP()`、`sendMultiHTTP()`、`sendFile()`、`getRequest()` 新增 `$sslVerify` 参数控制是否校验服务端 TLS 证书，**默认不校验**（保持原有行为）；批量请求可用单项配置 `ssl_verify` 覆盖

**修复**

- 修复`View::flush()`未清理`extends`，导致同一实例后续渲染错误复用上一次父视图的问题
- 修复`Spids`构造方法`require_once`导致第二次实例化后黑名单为空的问题
- 修复`Instance`单例trait中子类与父类共用同一静态属性，`Child::instance()`返回父类实例的问题
- 修复`Lang::detect()`把语言包数组赋值给`string`类型属性导致`TypeError`的问题
- 修复`Lang`请求级语言状态无法复位的问题，新增`reset()`、`setDefaultRange()`
- 修复`Container::set()`声明`string $id`导致数组批量绑定不可用的问题，新增`remove()`、`clear()`、`getMaxService()`、`setMaxService()`
- 修复`Container`实例缓存无上限的问题，超过上限后不再缓存新的标识
- 修复`IPLocation::instance()`忽略后续传入的IP库路径的问题，并修正数据文件打开失败时的异常提示
- 修复`socket_write`使用`mb_strlen`计算长度导致多字节命令被静默截断的问题
- 修复`Network`复用已被对端关闭的TCP/UDP连接后持续失败的问题，读取异常时回收缓存
- 修复`Network::sendHTTP()`、`sendMultiHTTP()`、`sendFile()`只把 200 视为成功的问题（改为 2xx），并补充请求失败时的句柄关闭
- 修复`Network::sendMultiHTTP()`滚动窗口补位时未传递TLS校验参数的问题
- 修复`Collection::svg()`空集合除零错误
- 修复`Common::strToMap()`解析无等号参数时的未定义索引警告
- 修复`DocParse::formatParam()`解析无描述注解时的未定义索引警告
- 修复`HttpResponse`解析非HTTP报文时`substr()`类型错误致命异常
- 修复`File::getDirContent()`在`scandir()`返回`false`时的类型错误
- 修复`HttpRequest`解析`Content-Disposition`时值被截断的问题
- 修复`Tree::data()`遍历引用未断开导致数据被污染的问题
- 修复`helpers.php`中`check()`复用验证器实例导致状态跨调用残留的问题

#### [1.7.0] (2026-11-07)

**新增**

- 新增 `mon\util\exception\ContainerException`、`mon\util\exception\NotFoundException`，分别实现 PSR-11 的 `ContainerExceptionInterface`、`NotFoundExceptionInterface`

**优化**

- `Container`：类不存在时抛出 `NotFoundException`，反射实例化失败（抽象类、私有构造等）归一为 `ContainerException`；`invokeMethod()` 增加方法格式校验；构造参数绑定支持命名类型（联合/交叉类型不再误判为类类型参数），类类型参数优先使用传入值，`array_key_exists()` 替代 `isset()` 以支持显式传入 `null`
- `Instance`：改用反射判断构造方法可见性，私有/保护构造的类不再依赖异常消息判断
- `File::pathReplace()`：规范化 `.`、`..` 与重复分隔符，增强路径穿越防护
- `Lottery::gen()`：改为引用方式写入概率并 `unset()`，避免奖项数据被污染

**修复**

- 修复 `Validate` 的 `postal` 规则未加首尾锚点导致部分匹配即可通过的问题

#### [1.6.2] ~ [1.6.11] (2025-06-14 ~ 2026-10-14)

> 该区间版本未逐版本记录日志，主要包含：验证器重构与规则调整；`Network`、`OS`、`Tree`、`UploadSlice`、`UploadFile`、`SnowFlake`、`Sql` 等模块优化；`Tool`、`Common`、`Collection`、`Date` 功能补全；新增 `Minifier`（代码压缩）、`Obfuscator`（代码混淆）类；支持 PHP 8.5。详见对应版本的提交记录。

#### [1.6.1] (2025-06-12)

- 修复`File`类`pathReplace`方法，路径归一化处理`.`、`..`路径段，防止路径穿越
- 修复`Container`容器类`PSR-11`异常契约，新增`NotFoundException`、`ContainerException`异常类，类不存在时抛出`NotFoundExceptionInterface`
- 修复`Container`容器类`bindParams`方法，支持联合/交叉类型参数，类类型参数优先使用传入参数，否则由容器注入
- 修复`Container`容器类`invokeMethod`方法，方法格式错误时抛出明确的异常
- 修复`Lottery`抽奖类构造方法中奖标记未生效的问题
- 修复`Validate`验证器`postal`规则未锚定导致的误判问题
- 优化`Instance`单例trait，改用反射判断构造方法可见性，不再依赖异常消息内容

#### [1.6.0]

- 优化代码
- 增强验证器`Validate`类，支持更多的验证规则
- 普通工具类改为静态调用，提升性能。如`Tool`, `Common`, `File`, `NetWork`等

#### [1.5.9]

- 支持PHP8.3版本
- 优化代码，修复发现的问题
- `Validate`验证器类功能增强

#### [1.5.3]

- 移除`InviteCode`类，新增`Spids`类
- 优化修正发现的问题

#### [1.5.2]

- 优化代码
- 增加`SnowFlake`雪花ID生成类

#### [1.5.1]

- 优化代码
- 增加`View`视图操作类


#### [1.5.0]

- 升级`PHP`版本至`7.2`，支持`PHP8`


#### [1.4.9]

- 优化`Network`类，增强`TCP/UDP`支持，优化`HTTP`请求头参数解析
- 增加`Tool`类简易快速导出`Excel`工具方法

#### [1.4.7]

- 优化`Validate`验证器类
- 优化`Lottery`抽奖类
- `IdCode`类优化更名为`InviteCode`邀请码类


#### [1.4.6]

- 增加`Collection`类，数组集合类，优化数组操作
- 优化`Common`类


#### [1.4.5]

- 增加`Nbed64`类，作为加密库，实现动态加密、静态加密
- 优化代码


#### [1.4.4]

- 增加`Tool`类 require_cache、include_cache方法
- 优化代码


#### [1.4.3]

- 修正增强`Container`容器类对PHP7类型参数的支持
- 优化`Tool`工具类，增加`buildURL`方法


#### [1.4.1]

- 优化`File`文件类
- 优化`Date`日期类
- 修复发现的问题


#### [1.4.0]

- 精简`File`文件类
- 移除`Log`类，请使用`mongdch/mon-log`包
- 移除`Pinyin`类，请使用`overtrue/pinyin`包
- 验证器`Validate`内置验证规则增加
- `debug`函数更名为`dd`函数
- 优化`Event`类


#### [1.3.13](#https://github.com/MonGDCH/mon-util/commit/f7c9f735fa4cc01a0ee2640382fae1c0f08b218f) (2022-07-26)

- 增加`Event`事件监听类
- 优化`Log`日志类`PSR-3`标准支持
- 优化`container`容器类`PSR-11`标准支持


#### [1.3.12](#https://github.com/MonGDCH/mon-util/commit/dee088b3bee124b1784d39714addb54d141447b0) (2022-07-15)

- 优化文档
- 增加`Log`日志处理类


#### [1.3.11](https://github.com/MonGDCH/mon-util/commit/de7fe3ffd752e7051a9624f2fe500bba932b6ead) (2022-07-13)

* 优化`Sql`类
* 优化`Dictionary`类
* 增加`Migrate`Mysql数据库备份迁移类


#### [1.3.10](https://github.com/MonGDCH/mon-util/commit/95b3be663e8807b17bf6c9af9c6f27707df20b68) (2022-05-18)

* 优化代码
* `Tool`类增加base64图片转换方法


#### [1.3.9](https://github.com/MonGDCH/mon-util/commit/1a689833b9cf22edc7bc68dab0725b89664667bb) (2022-04-21)

* 优化代码
* 增加UploadSilce类，用户处理大文件分片上传
* 增加Pinyin类，中文转拼音


#### [1.3.8](https://github.com/MonGDCH/mon-util/commit/a9b5dd32ae15f77caa569e03fe9e9606a11e1976) (2022-01-24)

* 优化增加文件导出压缩包 Tool::exportZip 方法
* 增加 Tool::exportZipForDir 方法，压缩导出整个目录
* 优化代码注解
* 补全版本


#### [1.3.7](https://github.com/MonGDCH/mon-util/commit/a5beb3ed528fd8e00e52f84648bafc5f84e08361) (2021-11-05)

* 优化增强Image类
* 补全版本


#### [1.3.6](https://github.com/MonGDCH/mon-util/commit/aba7e439f7f17bf8d0e0d3d1fd2757b4e1641549) (2021-10-14)

* 优化代码


#### [1.3.5](https://github.com/MonGDCH/mon-util/commit/7b4d9401ade441d79350cf2acc7d85a2175e1031) (2021-08-25)

* 优化代码，`Client`类更改为`Network`类，优化错误处理，增加模拟表单文件上传功能
* 优化`Date`类，增加获取年月日周等开始时间及结束时间


#### [1.3.4](https://github.com/MonGDCH/mon-util/commit/b52464f523c05464c7a48bc6ebf3b1d7f1222fce) (2021-08-11)

* 优化代码，移除`Tool`类中的TCP、UDP方法
* 增加`Client`类，支持HTTP、TCP、UDP请求


#### [1.3.3](https://github.com/MonGDCH/mon-util/commit/499ce3d6c5b3c91d200b4543e8e5786d0f305017) (2021-07-01)

* 优化代码，增强验证器
* File类增加 `copyDir` 文件夹复制方法 


#### [1.3.2](https://github.com/MonGDCH/mon-util/commit/8495104332a7d4d73b2fc21bb1ad51bb3314e3cb) (2021-04-26)

* 优化代码，优化错误处理机制
* Validate类check方法统一返回boolean值，通过getError方法获取错误信息
* 移除部分内置函数


#### [1.3.1](https://github.com/MonGDCH/mon-util/commit/1de8c9a6c935b1b37823eda6c40585487c54aafe) (2021-03-18)

* 优化代码，优化错误处理机制
* 移除Validate类内置单例模式，增加confirm、eq方法
* 移除Date类内置单例模式，优化业务逻辑
* 优化IdCard类，增加根据身份证号获取所属省市区地址
* 增加Lottery类，概率抽奖工具类


#### [1.2.10](https://github.com/MonGDCH/mon-util/commit/ce683230948c6ade093a1ad8f1e038ec1cab5962) (2021-03-11)

* 增加Sql类，用于解析SQL文件，获取sql操作语句
* 增加IdCard类，用于处理身份证相关的业务


#### [1.2.9](https://github.com/MonGDCH/mon-util/commit/ece2233632916f89e0fd51719c0da683f98598cb) (2021-03-08)

* 增加DocParse类，用于PHP文档解析


#### [1.2.8](https://github.com/MonGDCH/mon-util/commit/b0024ac96d4311554a767bd9cad0e93c893d9058) (2021-03-01)

* 优化代码
* 从[mongdch/mon-container]迁移container类库，优化类库
* 增加File文件操作类库


#### [1.2.7](https://github.com/MonGDCH/mon-util/commit/d7d5f4bd76acfb1844b3ba69397dd2a86010da47) (2021-02-03)

* 优化代码
* 增加IPLocatoin类，用于处理基于纯真IP库的地址定位


#### [1.2.6](https://github.com/MonGDCH/mon-util/commit/d816217e31ab2d715a2c8c73d250b6dc583ae19f) (2021-01-28)

* 优化id加密生成code类


#### [1.2.5](https://github.com/MonGDCH/mon-util/commit/6b14c1e22ba031c8ff3bb5b735741365a9e202e4) (2020-12-09)

* 优化代码
* 增加更多的工具方法


#### [1.2.4](https://github.com/MonGDCH/mon-util/commit/75139eb2f4e6e190fd23556913e053f9aec1fc66) (2020-11-30)

* 优化decryption字符串解密方法
* 增加Tool类download方法，用于下载保存文件
* 增加IdCode类，用于将int类型的ID值转为code值


#### [1.2.3](https://github.com/MonGDCH/mon-util/commit/9bb8f1c810bfac4e801a3b0ec7d6003e3bacb212) (2020-11-25)

* 优化验证器，验证器规则支持数组定义
* 增加Qrcode二维码工具类、增加Tool类getDistance方法用于获取两个坐标距离
* 增加Tool类exportZip、unZip、qrcode等方法，同步支持直接方法名调用


#### [1.2.2](https://github.com/MonGDCH/mon-util/commit/031e6c308918f56e190863cc028bdf1984fdbcc8) (2020-06-29)

* 优化代码，使用PHP自带的filter_var方法验证邮件及IP类型
* 增加Tool类safe_ip方法，用于验证IP白名单或黑名单


#### [1.2.1](https://github.com/MonGDCH/mon-util/commit/6473e5e18b8215a7f33978b53340f61c0b398d91) (2020-06-16)

* 优化代码


#### [1.2.0](https://github.com/MonGDCH/mon-util/commit/b6036ea7db9c8e2161cd6a5d91e4281e7dff5e86) (2020-05-30)

* 优化代码，增加注解
* 增加工具类函数调用方式


#### [1.1.0](https://github.com/MonGDCH/mon-util/commit/cf1dcf3f389abdaab2530de568fc28a5e30b6352) (2019-12-20)

* 优化代码
* 增加Dictionary类，用于做数据字典


#### [1.0.4](https://github.com/MonGDCH/mon-util/commit/4e0925df23fcca06f2e903fb27654175b8c5ecc7) (2019-09-12)

* 优化代码
* 移除Form类
* 增加Lang多语言操作类


#### [1.0.3](https://github.com/MonGDCH/mon-util/commit/9c8bcf2724df50685804eb735bb9532d2e5a97f0) (2019-06-15)

* 修正函数缺失的BUG
* 优化代码


#### [1.0.2](https://github.com/MonGDCH/mon-util/commit/d51b57dd00043aaa687ab9e0a6bfe22a55876a8d) (2019-06-14)

* 验证器使用场景验证的情况下，字段未设置【required】验证规则，仍然必须存在字段BUG
* 增加Image类、UploadFile类、GIF类
* 优化代码结构


#### [1.0.1](https://github.com/MonGDCH/mon-util/commit/6e4456b222ec84db045d6b1bc9d41596b69f13d6) (2019-04-18)

* 增强Common类、Tool类功能函数
* 增加From类、Instance类
* 优化代码结构


#### [1.0.0](https://github.com/MonGDCH/mon-util/commit/30c2688aca8875bdf6b49c6c79b561ebd704f93e) (2019-04-11)

- 发布第一个版本


