# PHP SDK 接入指南

## 接入指南

  1. PHP version >= 5.4 & curl extension support
  2. 通过composer安装SDK

## 安装

```
php
    composer require ddxq/core-openapi-php-sdk
```

## PS
当前类库只是一个通用类库，具体的逻辑还需要根据业务来实现。

```
php

生成CoreOpenApi\Config对象的时候需要传递一个配置数组，数组的格式如下
CoreOpenApi\Config
$config = $extParams = [
    'appKey' => 'required',//appkey  $this->getAppKey() //来获取
    'appSecret' => 'required',//app secret $this->getAppSecret() //来获取
    'methodList' => [ //required api列表
        'alias1' => 'real api method1',
        'alias2' => 'real api method2',
        ...
    ],
    'extParams' => [ //optional 扩展配置  通过 $this->getParamByKey($key) //来获取
        'key' => 'value',
        'key2' => 'value',
    ],
];

$confObj = CoreOpenApi\Config($config);
$appKey = $confObj->getAppKey();
$appSecret = $confObj->getAppSecret();
$methodList = $confObj->getMethodList();
$key2 = $confObj->getParamByKey('key2');
$method = $confObj->getMethodByAlias('alias');
```