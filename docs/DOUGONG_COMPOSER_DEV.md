# 斗拱支付 Composer 开发指南

本文档以 `cccdl/adapay_sdk` 为参考，指导如何以相同风格封装斗拱支付 API。你将获得目录组织、核心类职责、请求封装、资源类实现与测试规范的完整思路。

## 目录结构与命名空间
```
src/
  Core/
    DougongConfig.php      # 配置载体
    BaseCore.php           # 公共基类（最小化设计）
  Traits/
    Request.php            # Guzzle 请求封装（GET/POST）
  Exception/
    DougongException.php   # 统一异常
  Tools/SignTool.php       # 回调验签（按需添加方法）
  功能模块/ (按斗拱功能菜单分类，与 Core 同级)
    Payment/               # 支付产品
    Refund/                # 退款
    Merchant/              # 进件管理
    Split/                 # 分账服务
    Settlement/            # 结算取现
    Account/               # 对账服务
    Invoice/               # 发票服务
    Customer/              # 账户服务
    Risk/                  # 风险管理
    Activity/              # AT活动
    Solution/              # 解决方案
    Helper/                # 辅助接口
tests/
  Payment/PaymentTest.php
composer.json
```
- 命名空间：`cccdl\DougongPay\`（PSR-4，根到 `src/`）。
- 模块：按功能模块扁平化组织，与 Core 同级。
- 原则：遵循最小化原则，功能模块文件夹已创建，具体 PHP 文件按需添加。

## composer 配置（要点）
- `autoload.psr-4`: `{"Yourname\\DougongPay\\": "src/"}`
- 运行环境：建议 PHP 7.4+ 或 8.0+
- 依赖：`guzzlehttp/guzzle`、`ext-json`、`ext-openssl`
- 开发依赖：`phpunit/phpunit`
- 常用脚本：`"test": "vendor/bin/phpunit"`，`"dump": "composer dump-autoload -o"`

## 核心类职责（基于最小化原则）
- `Core/DougongConfig`：保存 `api_key`、`rsa_private_key`、`rsa_public_key`、`base_uri` 等配置。
- `Core/BaseCore`：基础核心类，最小化设计：
  - 提供配置注入和验证
  - 其他方法（如 URL 设置、签名等）按需添加，遵循"只增加需要用到的方法"原则
- `Traits/Request`：使用 Guzzle 统一 `GET/POST` 请求，异常时读取响应体，统一 `json_decode` 返回数组。
- `Tools/SignTool`：回调验签工具，按需扩展方法。

## 开发原则
**最小化原则：只增加需要用到的方法**
- 不要预先编写可能用到的方法
- 没用到的方法全部删除
- 需要的时候再编写
- 例如：`setUrl()`、`setGetParams()`、`setPostParams()` 等方法，只有在真正需要使用时才添加到 BaseCore 中

## 资源类模式（按需实现）
每个功能模块类都继承 `BaseCore`，根据实际业务接口需求实现对应方法，不受统一接口约束。

```php
// 示例：当需要支付功能时，在 Payment/ 文件夹中创建 Payment.php
class Payment extends BaseCore {
    protected $endpoint = '/v2/trade/payment/jspay';  // 根据实际接口设置

    // 根据实际需要的方法添加，比如：
    public function create(array $params): array {
        // 实现支付创建逻辑
        // 此时再在 BaseCore 中添加所需的 setUrl、setPostParams 等方法
    }

    // 其他方法按需添加，不要预先实现所有可能的方法
}
```

**重要**：
- 功能模块文件夹已创建，具体 PHP 文件按实际需求添加
- 不要预先实现 `query()`、`queryList()`、`close()` 等方法，除非真正需要
- 每个模块的接口可能完全不同，不强制统一

## 使用示例
```php
use cccdl\DougongPay\Core\DougongConfig;
use cccdl\DougongPay\Payment\Payment;

$config = new DougongConfig([
  'api_key' => getenv('DG_API_KEY'),
  'rsa_private_key' => getenv('DG_RSA_PRIVATE'),
  'rsa_public_key' => getenv('DG_RSA_PUBLIC'),
  'base_uri' => 'https://api.dougongpay.com', // 或沙箱
]);
$svc = new Payment($config);
$res = $svc->create(['amount'=>100,'order_no'=>'2025-0001']);
```

## 测试与约定
- 测试：`composer require --dev phpunit/phpunit` 后，`vendor/bin/phpunit`
- 结构：`tests/` 与 `src/` 镜像；命名 `*Test.php`
- 隔离：使用 Guzzle MockHandler/自定义客户端替身，禁止直连真实网关
- 风格：PSR-12，4 空格缩进；类 PascalCase、方法/属性 camelCase、常量 UPPER_SNAKE_CASE

## 开发流程建议
1) 根据官方 API 补全资源类与方法清单（路径、HTTP 动词、必填参数）
2) 在 `BaseCore` 实现或替换签名算法与验签逻辑
3) 为每个方法添加最小单元测试与错误场景用例
4) 编写 `Tools/SignTool::checkSign()`，对接框架时注意读取原始请求体
5) 引入 `composer scripts` 简化开发：`test`、`dump`

## 安全与配置
- 严禁提交密钥/证书；统一走环境变量或安全配置中心
- 固定依赖范围并审阅 `composer update` 变更
- 记录请求/响应关键字段（脱敏），便于排障

## 开发架构说明
- **直接对接官方 API**：无适配器抽象层，避免过度设计
- **最小化设计**：BaseCore 只包含配置注入，其他功能按需添加
- **扁平化结构**：功能模块与 Core 同级，便于维护
- **按需扩展**：功能模块文件夹已就位，具体实现按实际需求创建

## 开发流程建议
1) 根据具体业务需求，在对应功能模块文件夹中创建 PHP 文件
2) 继承 BaseCore，设置 endpoint，实现需要的方法
3) 在实现过程中，如需要签名、URL 设置等功能，再添加到 BaseCore 中
4) 为每个方法添加必要的单元测试
5) 编写 `Tools/SignTool` 中的验签方法（如需要）
