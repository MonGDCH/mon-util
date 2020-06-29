# mon-util

PHP常用工具、辅助类集合，包含一些类库

* Date类，时间操作
* Instance类， trait单例实现
* Tree类，树结构操作
* UpdateImg类，base64图片上传
* UpdateFile类，文件上传
* Validate类，验证器
* Tool类，常用工具类库函数
* Common类，公共工具函数库
* Image类，图片处理工具
* UploadFile类，文件上传
* GIF类，辅助Image类进行GIF图片处理
* Lang类，多语言操作
* Dictionary类，数据字典

## 安装

```bash
composer require mongdch/mon-util
```

## 版本

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
