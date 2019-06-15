# mon-util

PHP常用工具、辅助类集合，包含一些类库

* Date类，时间操作
* From类，表单操作
* Instance类， trait单例
* Tree类，树结构操作
* UpdateImg类，图片上传
* Validate类，验证器
* Tool类，常用工具类库函数
* Common类，公共工具函数库
* Image类，图片处理工具
* UploadFile类，文件上传
* GIF类，辅助Image类进行GIF图片处理

## 安装

```bash
composer require mongdch/mon-util
```

## 版本

### 1.0.2

* 验证器使用场景验证的情况下，字段未设置【required】验证规则，仍然必须存在字段BUG
* 增加Image类、UploadFile类、GIF类
* 优化代码结构

### 1.0.1

* 增强Common类、Tool类功能函数
* 增加From类、Instance类
* 优化代码结构
