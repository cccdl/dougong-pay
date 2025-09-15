# Repository Guidelines（仓库指南）

## 项目结构与模块组织
- `src/`：库代码（PSR-4 命名空间 `Yourname\DougongPay\`）。按提供方分模块，例如 `src/Adapay/Client.php` → `Yourname\DougongPay\Adapay\Client`。
- `vendor/`：Composer 依赖（不要修改或提交其内容）。
- `composer.json` / `composer.lock`：包元数据与精确依赖版本。
- `tests/`（按需添加）：PHPUnit 测试，目录结构与 `src/` 对应。
- `.idea/`：IDE 配置，评审时可忽略。

示例映射：
```
src/
  Adapay/Client.php         # Yourname\DougongPay\Adapay\Client
  Huifu/WebhookVerifier.php # Yourname\DougongPay\Huifu\WebhookVerifier
```

## 构建、测试与开发命令
- 安装依赖：`composer install`
- 更新依赖：`composer update`
- 刷新自动加载：`composer dump-autoload -o`
- 运行测试（安装 PHPUnit 后）：`vendor/bin/phpunit`

最小使用示例：
```php
require __DIR__ . '/vendor/autoload.php';
// use Yourname\DougongPay\Adapay\Client; // 示例
```

## 代码风格与命名约定
- 标准：PSR-12；4 空格缩进；120 列软限制。
- 文件：每文件一个类；文件名与类名一致。
- 命名：类 PascalCase；方法/属性 camelCase；常量 UPPER_SNAKE_CASE。
- PHP：新文件顶部添加 `declare(strict_types=1);`；优先使用类型声明与返回类型。
- 布局：按提供方分文件夹（如 `Adapay/`、`Huifu/`）；避免在业务处直接调用第三方 SDK，使用本库封装接口。

## 测试规范
- 框架：PHPUnit。安装：`composer require --dev phpunit/phpunit`。
- 位置：`tests/` 镜像 `src/`（如 `tests/Adapay/ClientTest.php`）。
- 命名：`*Test.php`；每个测试聚焦一个断言点；变体使用 data provider。
- 隔离：mock 外部 SDK 调用；禁止直连真实支付端点；夹具放在 `tests/fixtures/`。

## 提交与 Pull Request 指南
- 提交：优先使用 Conventional Commits。
  - 例：`feat: add Adapay client`，`fix(huifu): correct webhook signature check`，`chore: update composer.lock`。
- 分支：`feat/...`、`fix/...`、`chore/...`，简短且可描述。
- PR：包含变更说明、关联 Issue、破坏性变更说明与测试覆盖摘要；新增提供方请附示例代码。

## 安全与配置提示
- 切勿提交凭据或私钥。通过环境变量（或 `.env`）加载，并以配置对象传递。
- 校验并记录 Webhook 签名；无法验证的请求直接拒绝。
- 固定依赖版本范围；运行 `composer update` 后审阅变更。

## 开发指南
- 斗拱支付 Composer 开发指南：`docs/DOUGONG_COMPOSER_DEV.md`
- 官方协议规则（接入与安全）：`docs/OFFICIAL_PROTOCOL.md`
  - 聚合正扫：同文件中章节“聚合正扫”
  - 微信APP支付：同文件中章节“微信APP支付”
  - 支付产品：同文件中章节“支付产品”
  - 异步消息：同文件中章节“异步消息”
  - 接口加密解密说明：同文件中章节“接口加密解密说明”
  - v2 版接口加签验签：同文件中章节“v2 版接口加签验签”
  - 联调公私钥参数获取：同文件中章节“联调公私钥参数获取”
  - 接入步骤：同文件中章节“接入步骤”
  - 标准字段及返回码：同文件中章节“标准字段及返回码”
  - 请求/响应体模型：同文件中章节“请求/响应体模型”
  - 官方接口片段与映射：同文件中章节“官方接口片段与映射”（如：微信 APP 支付）

## 官方 SDK 参考
- 官方斗拱 SDK：`vendor/huifurepo/dg-php-sdk`。后续开发可参考其 API 与代码结构进行封装与适配。
- 注意：请勿直接修改 `vendor/` 目录；如需扩展或调整，请在 `src/` 创建适配层并通过我们的接口暴露。

## 开发进度记录
（中文总结机制）每次功能/文档改动完成后，按以下格式在此处追加一条记录，保持简洁、可追溯：
- 标题：一句话概述本次变更
- 内容：
  - 变更点：修改/新增的主要模块与文件
  - 命令：涉及的关键命令（如 composer/test/build）
  - 兼容性：PHP/依赖版本、破坏性变更说明（如有）
  - 后续：下一步计划或待确认事项

- 文档与指引
  - 新增 `docs/DOUGONG_COMPOSER_DEV.md`，沉淀适配/封装建议与目录规范。
  - 在本文件新增“官方 SDK 参考”，并从本文件链接至开发指南。
- 代码脚手架
  - 更新 `composer.json`：加入 `guzzlehttp/guzzle`、`phpunit`、脚本 `test/dump` 与 PSR-4 dev autoload。
  - 新增核心与通用：`src/Core/DougongConfig.php`、`src/Core/BaseCore.php`、`src/Traits/Request.php`、`src/Exception/DougongException.php`。
  - 新增契约与适配：`src/Contracts/PaymentClientInterface.php`、`src/Adapters/Huifu/PaymentClient.php`（对接官方 SDK）。
  - 新增资源与回调：`src/Payment/Payment.php`、`src/Tools/SignTool.php`。
  - 新增测试配置与冒烟测试：`phpunit.xml.dist`、`tests/bootstrap.php`、`tests/Smoke/AutoloadTest.php`。
- PHP 7.0 兼容性
  - `composer.json` 增加 `"php": ">=7.0"`，`phpunit/phpunit` 调整为 `^6.5`；将 `cccdl/adapay_sdk` 移至 `suggest`。
  - 代码移除 `void`/可空类型与多类型捕获，保证 7.0 可运行。
