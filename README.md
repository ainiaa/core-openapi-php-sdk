# PHP SDK 接入指南

## 接入指南

  1. PHP version >= 5.4 & curl extension support
  2. 通过composer安装SDK
  3. 创建Config配置类，填入key，secret和sandbox参数
  4. 使用sdk提供的接口进行开发调试
  5. 线上环境将Config中$sandbox值设为false

### 安装

```
php
    composer require ddxq/core-openapi-php-sdk
```