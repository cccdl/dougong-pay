# 斗拱支付 SDK for PHP

### 主要特性

* 斗拱支付 SDK for PHP
* 基于官方 SDK 优化的 Composer 依赖自动加载形式
* 规范代码符合 PSR 标准和 SonarLint 检测
* 统一配置加载，提高性能
* 采用 Guzzle HTTP 统一请求处理
* 支持单元测试
* 简化使用方式、更符合面向对象、命名空间使用规范
* 错误、成功都统一返回正常可用数组

### 更新日志

- 1.0.0 初始版本，支持聚合正扫支付、扫码交易查询、关单、退款等核心功能
- 1.0.0 增加聚合反扫支付（micropay）接口，支持微信/支付宝/银联/数字人民币

### 更新计划

- [x] 聚合正扫支付
- [x] 聚合反扫支付
- [x] 扫码交易查询
- [x] 扫码交易关单
- [x] 扫码交易退款
- [ ] 异步通知处理
- [ ] 分账服务
- [ ] 进件管理

## 安装

> 运行环境要求 PHP 7.4+。

```shell
$ composer require vendor/dougong-pay
```

### 接口对应文件

了解[斗拱支付接口文档](https://api.huifu.com/)，点击快速进入

| 文件                      | 方法        | 说明           |
|-------------------------|-----------|--------------|
| Payment.php             | `pay()`   | 聚合正扫支付       |
| PaymentMicropay.php     | `pay()`   | 聚合反扫支付       |
| PaymentQuery.php        | `query()` | 扫码交易查询       |
| PaymentClose.php        | `close()` | 扫码交易关单       |
| PaymentCloseQuery.php   | `query()` | 扫码交易关单查询     |
| PaymentRefund.php       | `refund()` | 扫码交易退款      |
| PaymentRefundQuery.php  | `query()` | 扫码交易退款查询     |

### 快速使用

了解斗拱支付[接口约定](https://api.huifu.com/)。

#### 聚合正扫支付

```php
<?php

use cccdl\DougongPay\Core\DougongConfig;
use cccdl\DougongPay\Payment\Payment;

// 配置信息
$config = new DougongConfig([
    'baseUri' => 'https://api.huifu.com',
    'sysId' => 'your_sys_id',
    'productId' => 'your_product_id',
    'privateKey' => 'your_private_key',
    'publicKey' => 'dougong_public_key',
]);

// 支付参数
$params = [
    'req_date' => date('Ymd'),
    'req_seq_id' => 'PAY_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => '6666000000000000',
    'trans_amt' => '100.00',
    'goods_desc' => '商品描述',
    'notify_url' => 'https://your-domain.com/notify',
    'risk_check_data' => json_encode([
        'ip_addr' => '192.168.1.1'
    ])
];

$payment = new Payment($config);
$result = $payment->pay($params);

// 处理结果
var_dump($result);
```

#### 聚合反扫支付

```php
<?php

use cccdl\DougongPay\Core\DougongConfig;
use cccdl\DougongPay\Payment\PaymentMicropay;

$config = new DougongConfig([
    'baseUri' => 'https://api.huifu.com',
    'sysId' => 'your_sys_id',
    'productId' => 'your_product_id',
    'privateKey' => 'your_private_key',
    'publicKey' => 'dougong_public_key',
]);

// 反扫支付参数
$params = [
    'req_date' => date('Ymd'),
    'req_seq_id' => 'MP_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => '6666000000000000',
    'trans_amt' => '100.00',
    'goods_desc' => '商品描述',
    'auth_code' => '288413840870151****', // 用户付款码
    'risk_check_data' => json_encode([
        'ip_addr' => '192.168.1.1'
    ])
];

$micropay = new PaymentMicropay($config);
$result = $micropay->pay($params);

// 处理结果
var_dump($result);
```

#### 交易查询

```php
<?php

use cccdl\DougongPay\Core\DougongConfig;
use cccdl\DougongPay\Payment\PaymentQuery;

$config = new DougongConfig([
    'baseUri' => 'https://api.huifu.com',
    'sysId' => 'your_sys_id',
    'productId' => 'your_product_id',
    'privateKey' => 'your_private_key',
    'publicKey' => 'dougong_public_key',
]);

// 查询参数
$params = [
    'huifu_id' => '6666000000000000',
    'org_req_seq_id' => '2024040522182635****',
    'org_req_date' => date('Ymd')
];

$query = new PaymentQuery($config);
$result = $query->query($params);

// 处理结果
var_dump($result);
```

#### 退款

```php
<?php

use cccdl\DougongPay\Core\DougongConfig;
use cccdl\DougongPay\Payment\PaymentRefund;

$config = new DougongConfig([
    'baseUri' => 'https://api.huifu.com',
    'sysId' => 'your_sys_id',
    'productId' => 'your_product_id',
    'privateKey' => 'your_private_key',
    'publicKey' => 'dougong_public_key',
]);

// 退款参数
$params = [
    'req_date' => date('Ymd'),
    'req_seq_id' => 'REFUND_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => '6666000000000000',
    'ord_amt' => '0.01',
    'org_req_date' => date('Ymd'),
    'org_hf_seq_id' => '0030default220825182711P099ac1f343f*****'
];

$refund = new PaymentRefund($config);
$result = $refund->refund($params);

// 处理结果
var_dump($result);
```

## 配置说明

DougongConfig 配置参数：

| 参数名       | 类型     | 必填 | 说明                |
|-----------|--------|----|--------------------|
| baseUri   | string | 是  | 斗拱支付 API 基础地址     |
| sysId     | string | 是  | 系统 ID             |
| productId | string | 是  | 产品 ID             |
| privateKey| string | 是  | 商户 RSA 私钥         |
| publicKey | string | 是  | 斗拱支付 RSA 公钥       |

## 支持的支付类型

### 聚合正扫支付
- 微信公众号支付：T_JSAPI
- 微信小程序支付：T_MINIAPP
- 微信APP支付：T_APP
- 支付宝JS支付：A_JSAPI
- 支付宝正扫：A_NATIVE
- 银联二维码正扫：U_NATIVE
- 银联二维码JS：U_JSAPI
- 数字货币二维码支付：D_NATIVE

### 聚合反扫支付
- 微信反扫：T_MICROPAY
- 支付宝反扫：A_MICROPAY
- 银联反扫：U_MICROPAY
- 数字人民币反扫：D_MICROPAY

## 文档

[斗拱支付官方文档](https://api.huifu.com/)

## 问题

[提交 Issue](https://github.com/vendor/dougong-pay/issues)，不符合指南的问题可能会立即关闭。

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/vendor/dougong-pay/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/vendor/dougong-pay/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

[MIT](LICENSE)