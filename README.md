# mon-util

PHP常用工具、辅助类集合，包含各种常用的类库

* Container类，对象容器
* Date类，时间操作
* Instance类， trait单例实现
* Tree类，树结构操作
* UpdateImg类，base64图片上传
* UpdateFile类，文件上传
* File类，文件操作
* Validate类，验证器
* Tool类，常用工具类库函数
* Common类，公共工具函数库
* Image类，图片处理工具
* UploadFile类，文件上传
* GIF类，辅助Image类进行GIF图片处理
* Lang类，多语言操作
* Dictionary类，数据字典
* IdCode类，用于整形ID加密转短字符串，可结合应用用于生成短链接
* IPLocation类，基于纯真IP库的ip地址定位，解析GBK数据输出UTF8编码地址
* Qrcode类，用于生成图片二维码
* DocParse类，解析PHP类对象注解，生成文档
* Sql类，解析SQL文件，获取sql操作语句
* IdCard类，处理身份证相关的业务工具类
* 增加Lottery类，概率抽奖工具类

## 安装

```bash
composer require mongdch/mon-util
```

## 版本

### 1.3.4

* 优化代码，移除`Tool`类中的TCP、UDP方法
* 增加`Client`类，支持HTTP、TCP、UDP请求

### 1.3.3

* 优化代码，增强验证器
* File类增加 `copyDir` 文件夹复制方法 

### 1.3.2

* 优化代码，优化错误处理机制
* Validate类check方法统一返回boolean值，通过getError方法获取错误信息
* 移除部分内置函数

### 1.3.1

* 优化代码，优化错误处理机制
* 移除Validate类内置单例模式，增加confirm、eq方法
* 移除Date类内置单例模式，优化业务逻辑
* 优化IdCard类，增加根据身份证号获取所属省市区地址
* 增加Lottery类，概率抽奖工具类


### 1.2.10

* 增加Sql类，用于解析SQL文件，获取sql操作语句
* 增加IdCard类，用于处理身份证相关的业务

### 1.2.9

* 增加DocParse类，用于PHP文档解析

### 1.2.8

* 优化代码
* 从[mongdch/mon-container]迁移container类库，优化类库。
* 增加File文件操作类库。

### 1.2.7

* 优化代码
* 增加IPLocatoin类，用于处理基于纯真IP库的地址定位

### 1.2.6

* 优化id加密生成code类

### 1.2.5

* 优化代码
* 增加更多的工具方法

### 1.2.4

* 优化decryption字符串解密方法
* 增加Tool类download方法，用于下载保存文件
* 增加IdCode类，用于将int类型的ID值转为code值

### 1.2.3

* 优化验证器，验证器规则支持数组定义
* 增加Qrcode二维码工具类、增加Tool类getDistance方法用于获取两个坐标距离
* 增加Tool类exportZip、unZip、qrcode等方法，同步支持直接方法名调用

### 1.2.2

* 优化代码，使用PHP自带的filter_var方法验证邮件及IP类型
* 增加Tool类safe_ip方法，用于验证IP白名单或黑名单

### 1.2.1

* 优化代码

### 1.2.0

* 优化代码，增加注解
* 增加工具类函数调用方式

### 1.1.0

* 优化代码
* 增加Dictionary类，用于做数据字典

### 1.0.4

* 优化代码
* 移除Form类
* 增加Lang多语言操作类

### 1.0.3

* 迭代发布1.0.2版本代码缺失

### 1.0.2

* 验证器使用场景验证的情况下，字段未设置【required】验证规则，仍然必须存在字段BUG
* 增加Image类、UploadFile类、GIF类
* 优化代码结构

### 1.0.1

* 增强Common类、Tool类功能函数
* 增加From类、Instance类
* 优化代码结构
