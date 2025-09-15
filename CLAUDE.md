# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

这是一个PHP Composer包项目（dougong-pay），专注于支付功能。

## 常用命令

### Composer 包管理
```bash
composer install          # 安装依赖
composer update           # 更新依赖
composer dump-autoload    # 重新生成自动加载文件
composer require <package> # 添加依赖
```

### 开发工具命令
```bash
composer test             # 运行测试（需要在composer.json中配置）
composer lint             # 代码检查（需要配置）
composer format           # 代码格式化（需要配置）
composer dump-autoload -o # 刷新自动加载（优化模式）
vendor/bin/phpunit        # 直接运行PHPUnit测试
vendor/bin/phpstan        # 静态分析（如果使用）
vendor/bin/php-cs-fixer   # 代码格式化（如果使用）
```

## 包结构

典型的Composer包结构：
- `src/` - 主要源代码
- `tests/` - 测试文件
- `composer.json` - 包配置和依赖
- `README.md` - 包文档
- `LICENSE` - 开源许可证

## 开发指导

### composer.json 配置要点
- 设置正确的命名空间和自动加载
- 配置开发依赖（testing/linting工具）
- 设置脚本快捷方式

### 测试
- 使用PHPUnit进行单元测试
- 测试文件放在`tests/`目录
- 测试类命名规范：`ClassNameTest.php`

## 支付包特殊注意事项

- 敏感信息（API密钥、支付密钥等）通过环境变量传入
- 所有支付相关操作需要严格验证
- 提供完整的错误处理和日志记录
- 遵循PCI DSS等安全标准
- 包含全面的单元测试，特别是安全相关功能

## 项目结构与模块组织

- `src/`：库代码（PSR-4 命名空间 `Yourname\DougongPay\`）。按提供方分模块，例如 `src/Adapay/Client.php` → `Yourname\DougongPay\Adapay\Client`
- `vendor/`：Composer 依赖（不要修改或提交其内容）
- `tests/`（按需添加）：PHPUnit 测试，目录结构与 `src/` 对应
- `.idea/`：IDE 配置，评审时可忽略

示例映射：
```
src/
  Adapay/Client.php         # Yourname\DougongPay\Adapay\Client
  Huifu/WebhookVerifier.php # Yourname\DougongPay\Huifu\WebhookVerifier
```

## 代码风格与命名约定

- 标准：PSR-12；4 空格缩进；120 列软限制
- 文件：每文件一个类；文件名与类名一致
- 命名：类 PascalCase；方法/属性 camelCase；常量 UPPER_SNAKE_CASE
- PHP：新文件顶部添加 `declare(strict_types=1);`；优先使用类型声明与返回类型
- 控制结构：所有 if、for、while 等控制结构必须使用大括号，即使只有一行代码
- 布局：按提供方分文件夹（如 `Adapay/`、`Huifu/`）；避免在业务处直接调用第三方 SDK，使用本库封装接口

**示例：**
```php
// 正确
if ($condition) {
    $result = true;
}

// 错误
if ($condition) $result = true;
```

## 开发原则

**最小化原则：只增加需要用到的方法**
- 不要预先编写可能用到的方法
- 不要多思考并增加没用的方法
- 没用到的方法全部删除
- 需要的时候再编写
- 例如：`setGetHeader`、`setPostHeader` 等方法，只有在真正需要使用时才添加
- **严禁添加多余的辅助方法**：如 `getTransactionStatus()`、`isQuerySuccessful()` 等便利方法，除非明确需要
- 每个类只实现核心业务方法，避免过度封装

**文档优先开发规则**
- **必须先记录文档，再进行代码开发**
- 新增接口功能时，先在 `docs/` 目录创建对应的接口文档
- 文档应包含：接口说明、请求参数、响应参数、使用示例、业务返回码
- 文档完成后再开始编写代码实现

**Git 提交规则**
- 提交消息不要带有 Claude、AI 或自动生成等标记
- 使用简洁明了的中文描述变更内容
- 采用常规的 git commit 格式，就像人工开发一样
- 提交消息应该描述"做了什么"和"为什么"

## 测试规范

- 框架：PHPUnit。安装：`composer require --dev phpunit/phpunit`
- 位置：`tests/` 镜像 `src/`（如 `tests/Adapay/ClientTest.php`）
- 命名：`*Test.php`；每个测试聚焦一个断言点；变体使用 data provider
- 隔离：mock 外部 SDK 调用；禁止直连真实支付端点；夹具放在 `tests/fixtures/`

## 提交与 Pull Request 指南

- 提交：优先使用 Conventional Commits
  - 例：`feat: add Adapay client`，`fix(huifu): correct webhook signature check`，`chore: update composer.lock`
- 分支：`feat/...`、`fix/...`、`chore/...`，简短且可描述
- PR：包含变更说明、关联 Issue、破坏性变更说明与测试覆盖摘要；新增提供方请附示例代码

## 安全与配置提示

- 切勿提交凭据或私钥。通过环境变量（或 `.env`）加载，并以配置对象传递
- 校验并记录 Webhook 签名；无法验证的请求直接拒绝
- 固定依赖版本范围；运行 `composer update` 后审阅变更

## 官方 SDK 参考

- 官方斗拱 SDK：`vendor/huifurepo/dg-php-sdk`。后续开发可参考其 API 与代码结构进行封装与适配
- 注意：请勿直接修改 `vendor/` 目录；如需扩展或调整，请在 `src/` 创建适配层并通过我们的接口暴露

## 重要开发文档

### 斗拱支付核心文档

**文档结构已重新组织为模块化设计：**

#### 基础协议规则
文件路径：`docs/base-protocol.md`

**主要内容：**
- 协议规则：HTTPS、POST、JSON、UTF-8 规范
- 请求/响应体模型：Header 要求、请求结构、响应结构
- 标准字段及返回码：参数命名、金额格式、HTTP 状态码
- 接入步骤：从协议签署到接口调试的完整流程
- 本库实现映射：官方协议与 PHP 实现的对应关系

#### 签名验签
文件路径：`docs/sign-verify.md`

**主要内容：**
- 认证与安全：加签、鉴权、加密解密算法
- 证书生成：OpenSSL 生成 RSA 密钥对的完整流程
- 联调公私钥参数获取：密钥生成和配置流程
- v2 版接口加签验签：完整的签名实现示例（多语言）
- 接口加密解密说明：RSA 加密解密敏感信息

**PHP 签名实现要点：**
- 签名原文为 `data` 字段内容
- 排序实现：`ksort($post_data); json_encode($post_data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);`
- 验签时同步返参需排序，异步返参直接验签

#### 异步消息通知
文件路径：`docs/async-notification.md`

**主要内容：**
- 同步通知 vs 异步通知：触发时机和应用场景对比
- 异步消息机制：webhook 通知处理规范
- HTTP(S) 异步通知配置：超时、重试、端口等设置
- 返回报文示例：Headers 和 POST data 格式
- PHP 处理示例：完整的异步通知处理代码示例
- 幂等性处理：防重复通知的实现方案
- 补偿查询机制：主动查询订单状态确保一致性

**关键实现要点：**
- 异步通知必须返回 `RECV_ORD_ID_` + 订单号
- 必须实现幂等性处理防重复
- 严格验证签名，拒绝无法验证的请求
- 5秒内响应，超时会重试3次

#### 聚合正扫接口
文件路径：`docs/aggregate-scan-interface.md`

**主要内容：**
- 应用场景：台牌码、公众号/小程序商城等
- 支持的支付类型：微信、支付宝、银联、数字人民币等完整清单
- 接口参数详解：必填/可选参数、数据格式、长度限制
- 请求/响应示例：完整的 JSON 格式示例
- 参数说明：禁用支付方式、业务返回码等
- 支付产品场景映射：各种支付方式的 trade_type 映射

#### 聚合正扫支付
文件路径：`docs/aggregate-scan-payment.md`

**主要内容：**
- 聚合正扫支付业务流程和实现
- 多种支付方式的统一处理
- 实际业务场景应用指南

#### 微信APP支付
文件路径：`docs/wechat-app-payment.md`

**主要内容：**
- 产品介绍：应用场景和支持的平台
- 接入前准备：商务准备、对接准备、密钥获取
- 开发指引：对接规范、业务配置确认
- 系统调用流程：完整的交易、退款、对账、异步通知流程
- 常见问题 FAQ：错误排查和解决方案

#### 支付宝支付
文件路径：`docs/alipay-payment.md`

**主要内容：**
- 产品介绍：支付宝 NATIVE 和 JS 支付
- 接入前准备：商户准备和业务配置
- 开发指引：两种支付方式的完整实现流程
- 异步通知处理：PHP 处理示例和验签实现
- 参数说明：支付宝专用参数和禁用支付方式

### 斗拱支付 Composer 开发指南
文件路径：`docs/DOUGONG_COMPOSER_DEV.md`

**主要内容：**
- 目录结构与命名空间规范
- Composer 配置要点
- 核心类职责（DougongConfig、BaseCore、Request 等）
- 资源类模式与使用示例
- 测试与代码风格约定
- 安全与配置最佳实践
- 官方 SDK 对照与适配建议

**关键开发模式：**
- 使用接口驱动设计（PaymentClientInterface）
- 在 `src/Adapters/` 中创建适配层对接官方 SDK
- 统一异常处理和返回值规范
- 完整的签名验签实现

### 官方协议文档
文件路径：`docs/OFFICIAL_PROTOCOL.md`

**主要内容：**
- 斗拱支付官方协议完整规范
- API 接口详细说明
- 参数规范和示例

## 项目架构说明

### 当前项目结构
```
src/
├── Core/                        # 核心基础类
│   ├── DougongConfig.php       # 配置类
│   └── BaseCore.php            # 基础核心类
├── Traits/                      # 通用特质
│   └── Request.php             # HTTP 请求封装 (Guzzle)
├── Exception/                   # 异常处理
│   └── DougongException.php    # 统一异常类
├── Tools/                       # 工具类
│   └── SignTool.php            # 签名验证工具
└── 功能模块 (按斗拱功能菜单分类)
    ├── Merchant/               # 进件管理
    ├── Payment/                # 支付产品
    ├── Refund/                 # 退款
    ├── Split/                  # 分账服务
    ├── Settlement/             # 结算取现
    ├── Account/                # 对账服务
    ├── Invoice/                # 发票服务
    ├── Customer/               # 账户服务
    ├── Risk/                   # 风险管理
    ├── Activity/               # AT活动
    ├── Solution/               # 解决方案
    └── Helper/                 # 辅助接口
```


### 架构特点
- **模仿 adapay_sdk 设计**：扁平化结构，功能模块与 Core 同级
- **最小化原则**：只实现需要用到的方法和类
- **按需创建**：功能模块文件夹已创建，但具体的 PHP 文件按实际需求添加
- **直接对接官方 API**：无适配器抽象层，避免过度设计

### 核心组件说明
- `DougongConfig.php`：配置载体，存储 API 密钥和 RSA 密钥
- `BaseCore.php`：基础核心类，提供配置注入和验证
- `Request.php`：Guzzle HTTP 请求封装，提供 GET/POST 方法
- `DougongException.php`：统一异常处理
- `SignTool.php`：签名验证工具（按需扩展方法）

### 开发模式
每个功能模块类都继承 `BaseCore`，根据实际业务接口需求实现对应方法，不受统一接口约束。