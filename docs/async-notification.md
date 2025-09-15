# 斗拱支付 - 异步消息

本文档详细说明斗拱支付的异步消息通知机制，包括 Webhook 和异步通知的处理。

## 异步消息简介

最近更新时间：2024.11.29

针对交易结果，汇付统一支付平台会通过异步消息通知客户系统。哪些接口有异步消息以及通知报文格式，请以各接口文档为准。

## HTTP(S) 异步通知使用说明

### 触发机制

异步通知会在以下情况触发：
- **交易状态变更**：支付成功、支付失败、支付关闭
- **退款状态变更**：退款成功、退款失败
- **其他业务状态变更**：根据具体业务接口而定

### 基本配置

- **通知地址**：调用接口时上送的异步通知地址（HTTP/HTTPS 路径）
- **HTTP方法**：POST回调
- **超时时间**：默认5秒，超时后重试3次
- **重试机制**：超时或非2xx状态码时重试3次
- **重定向**：不支持HTTP重定向
- **HTTPS验证**：不进行证书/主机名严格校验（ALLOW_ALL_HOSTNAME_VERIFIER）
- **端口限制**：自定义端口需在8000-9005范围内，否则无法通知
- **URL要求**：通知URL请勿附带GET参数
- **编码格式**：UTF-8
- **签名验证**：签名原文为data中的内容，按"v2版接口加签验签"规则进行验签

### 返回规范

- **HTTP状态码**：必须返回200
- **响应内容**：`RECV_ORD_ID_` + 指定字段（如商户订单号或请求流水号）
- **处理时间**：建议在5秒内完成处理并响应

### 幂等性要求

**重要提醒**：同样的异步消息可能会通知多次，因此接收异步消息的处理需做好幂等，保障多次接收到同样的消息处理后结果不变。

### 补偿查询机制

建议在重要的业务环节，通过反查接口确认**非终态**支付订单的状态，以保证在发生异步消息延迟或无法送达情况下的支付结果一致性。

## 返回报文示例

Headers:
```
charset: UTF-8
Content-Type: application/x-www-form-urlencoded
Connection: Keep-Alive
```

POST data:
```json
{
  "resp_code": "10000",
  "resp_desc": "成功调用",
  "sign": "kP0YeT3BxIRpc0...WmA==",
  "resp_data": "{\"acct_split_bunch\":{\"acct_infos\":[{\"div_amt\":\"753.00\",\"huifu_id\":\"6666000102973106\"}],\"fee_amt\":\"2.86\",\"fee_huifu_id\":\"6666000102973106\"},\"resp_code\":\"00000000\",\"resp_desc\":\"交易成功\",\"huifu_id\":\"6666000102973106\",\"req_seq_id\":\"ORDER123456\",\"req_date\":\"20240101\",\"trans_stat\":\"S\"}"
}
```

说明：
- 支付交易类接口异步通知返回的业务参数字段名为 `resp_data`。
- 商户进件配置类接口异步通知返回的业务参数字段名为 `data`。

## PHP 处理示例

### 基础处理流程

```php
<?php

// 接收异步通知
$notifyData = json_decode(file_get_contents('php://input'), true);

// 验证签名
$signTool = new \cccdl\DougongPay\Tools\SignTool($dougongConfig);
if ($signTool->verifyNotify($notifyData)) {
    $respData = json_decode($notifyData['resp_data'], true);

    if ($respData['trans_stat'] === 'S') {
        // 支付成功，处理业务逻辑
        // 更新订单状态、发货等操作
        processSuccessfulPayment($respData);
        echo 'RECV_ORD_ID_' . $respData['req_seq_id'];
    } else {
        // 支付失败
        processFailedPayment($respData);
        echo 'RECV_ORD_ID_' . $respData['req_seq_id'];
    }
} else {
    // 验签失败
    http_response_code(400);
    exit('Verification failed');
}
```

### 完整示例（含错误处理）

```php
<?php

use cccdl\DougongPay\Core\DougongConfig;
use cccdl\DougongPay\Tools\SignTool;

function handleDougongNotification(DougongConfig $config)
{
    try {
        // 获取原始输入
        $input = file_get_contents('php://input');
        if (empty($input)) {
            http_response_code(400);
            exit('Empty input');
        }

        // 解析 JSON 数据
        $notifyData = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            exit('Invalid JSON');
        }

        // 必要字段检查
        if (!isset($notifyData['sign']) || !isset($notifyData['resp_data'])) {
            http_response_code(400);
            exit('Missing required fields');
        }

        // 验证签名
        $signTool = new SignTool($config);
        if (!$signTool->verifyNotify($notifyData)) {
            http_response_code(400);
            exit('Signature verification failed');
        }

        // 解析业务数据
        $respData = json_decode($notifyData['resp_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            exit('Invalid business data JSON');
        }

        // 检查必要的业务字段
        if (!isset($respData['req_seq_id']) || !isset($respData['trans_stat'])) {
            http_response_code(400);
            exit('Missing business fields');
        }

        // 幂等性检查（防重复处理）
        if (isNotificationProcessed($respData['req_seq_id'])) {
            echo 'RECV_ORD_ID_' . $respData['req_seq_id'];
            exit;
        }

        // 根据交易状态处理
        switch ($respData['trans_stat']) {
            case 'S': // 成功
                processSuccessfulPayment($respData);
                break;
            case 'F': // 失败
                processFailedPayment($respData);
                break;
            case 'P': // 处理中（一般不会在异步通知中收到）
                processProcessingPayment($respData);
                break;
            default:
                error_log('Unknown transaction status: ' . $respData['trans_stat']);
        }

        // 标记通知已处理
        markNotificationProcessed($respData['req_seq_id']);

        // 返回成功响应
        echo 'RECV_ORD_ID_' . $respData['req_seq_id'];

    } catch (Exception $e) {
        error_log('Notification processing error: ' . $e->getMessage());
        http_response_code(500);
        exit('Internal server error');
    }
}

function processSuccessfulPayment(array $data)
{
    // 实现支付成功的业务逻辑
    // 例如：更新订单状态、发送确认邮件、触发发货等

    // 示例代码
    $orderId = $data['req_seq_id'];
    $amount = $data['trans_amt'];
    $paymentId = $data['hf_seq_id'];

    // 更新数据库
    updateOrderStatus($orderId, 'paid', $paymentId);

    // 发送通知
    sendPaymentConfirmation($orderId);

    // 记录日志
    error_log("Payment successful: Order {$orderId}, Amount {$amount}");
}

function processFailedPayment(array $data)
{
    // 实现支付失败的业务逻辑
    $orderId = $data['req_seq_id'];
    $reason = $data['resp_desc'] ?? 'Unknown error';

    // 更新订单状态
    updateOrderStatus($orderId, 'failed', null, $reason);

    // 记录日志
    error_log("Payment failed: Order {$orderId}, Reason: {$reason}");
}

function isNotificationProcessed(string $reqSeqId): bool
{
    // 检查数据库或缓存，判断该通知是否已经处理过
    // 这里需要根据实际存储方案实现
    return false;
}

function markNotificationProcessed(string $reqSeqId): void
{
    // 标记通知已处理，防止重复处理
    // 这里需要根据实际存储方案实现
}
```

### SignTool 中的验签方法实现

```php
// 在 SignTool.php 中添加异步通知验签方法
public function verifyNotify(array $notifyData): bool
{
    if (!isset($notifyData['sign']) || !isset($notifyData['resp_data'])) {
        return false;
    }

    $sign = $notifyData['sign'];
    $data = $notifyData['resp_data'];

    // 异步通知验签不需要排序，直接对原文验签
    return $this->verify($data, $sign);
}

private function verify(string $data, string $sign): bool
{
    $publicKey = $this->formatPublicKey($this->dougongConfig->rsaPublicKey);
    $signature = base64_decode($sign);

    return openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
}

private function formatPublicKey(string $key): string
{
    if (strpos($key, '-----BEGIN PUBLIC KEY-----') !== false) {
        return $key;
    }

    return "-----BEGIN PUBLIC KEY-----\n" .
           wordwrap($key, 64, "\n", true) .
           "\n-----END PUBLIC KEY-----";
}
```

## Java 处理示例（参考）

```java
// 汇付公钥
private static final String PUBLIC_KEY = "XXXX";

// 验签请参 data（支付类通知为 resp_data；配置类通知为 data）
String data = request.getParameter("resp_data");
try {
    String sign = request.getParameter("sign");
    // 使用汇付公钥验签（同步：排序后验签；异步：原文验签）
    if (!RsaUtils.verify(data, PUBLIC_KEY, sign)) {
        // 验签失败处理
        return "";
    }
    JSONObject dataObj = JSON.parseObject(data);
    String subRespCode = dataObj.getString("resp_code");
    String reqSeqId = dataObj.getString("req_seq_id");
    if ("00000000".equals(subRespCode)) {
        // 业务处理成功
    } else {
        // 业务处理失败
    }
    return "RECV_ORD_ID_" + reqSeqId;
} catch (Exception e) {
    // 记录异常
}
return "";
```

## 同步通知 vs 异步通知

### 同步通知
**触发时机**：调用支付接口时立即返回
- 用户调用 `Payment->create()` 方法时直接返回
- 无论支付是否成功都会有同步响应
- 响应速度快（通常几秒内）

**返回内容**：
- `trans_stat`：交易状态（P处理中/S成功/F失败）
- `qr_code`：二维码链接（NATIVE支付）
- `pay_info`：支付信息（JSAPI支付）
- 其他订单基础信息

### 异步通知
**触发时机**：支付状态发生变化时主动推送
- 用户完成支付后触发
- 支付失败时触发
- 退款成功/失败时触发
- 其他交易状态变更时触发

**通知特点**：
- 推送到商户配置的 `notify_url`
- 会重试多次确保送达
- 需要商户返回 `RECV_ORD_ID_` + 订单号确认接收
- 包含最终的支付结果和详细信息

### 典型支付流程

```
1. 商户调用支付接口
   ↓
2. 同步返回（立即）
   - 返回二维码或支付信息
   - trans_stat = "P"（处理中）
   ↓
3. 用户扫码支付
   ↓
4. 异步通知（延迟）
   - 推送到 notify_url
   - trans_stat = "S"（成功）或 "F"（失败）
```

**处理建议**：
- 同步返回：用于展示支付二维码或跳转支付页面
- 异步通知：用于更新订单状态、发货等业务逻辑

## 注意事项

### 重要提醒

- **幂等性处理**：异步消息可能重复通知，请实现幂等处理，确保多次收到同一消息时结果一致。
- **非终态反查**：建议在重要业务环节对"非终态"订单进行反查确认，避免因异步消息延迟或无法送达造成结果不一致。
- **签名验证**：必须严格验证签名，拒绝无法验证的请求。
- **返回格式**：必须返回 `RECV_ORD_ID_` + 订单号格式，否则系统会认为处理失败并重试。

### 安全建议

1. **IP 白名单**：建议配置斗拱通知服务器的 IP 白名单。
2. **HTTPS**：生产环境建议使用 HTTPS 接收通知。
3. **日志记录**：完整记录所有通知处理过程，便于问题排查。
4. **监控告警**：对异常情况设置监控告警。

### 常见问题

1. **重复通知**：正常现象，需要在业务层面实现幂等性。
2. **验签失败**：检查公钥配置、签名算法实现。
3. **未收到通知**：检查网络连通性、端口开放、URL 配置。
4. **处理超时**：优化业务处理逻辑，确保在 5 秒内响应。

## Webhook 说明

交易完成后除异步消息外，亦支持发送 webhook 事件，可配置多个接收端驱动业务流程（如财务入账、物流发货等）。具体配置和使用方法请参考斗拱控制台的 webhook 配置说明。