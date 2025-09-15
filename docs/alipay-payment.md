# 支付宝支付

最近更新时间：2024.9.1 作者: Lin

## 1. 产品介绍

### 1.1 简介
斗拱为手机、平板、POS等智能终端的APP提供支付服务接口。斗拱收款、查询、结算、对账的完整流程服务。

汇付支付支持在主流应用市场完成认证的移动端应用APP接入支付功能。APP接入支付后，商户通过斗拱支付接口调用支付宝支付完成收款需求。目前支持手机系统有：iOS（苹果）、Android（安卓）。

### 1.2 应用场景
客户拥有自己的一个APP，为了使APP接入支付功能实现商业上的闭环，商户APP跳转支付宝成交易。

支付宝APP支付可通过如下两种方式实现，核心是获取支付宝给的"凭证"：

1. **通过支付宝扫码实现（Native支付）**：参见本文3.3.1-支付宝NATIVE支付。
2. **通过支付宝手机网站（JS支付）实现**：参见本文3.3.2-支付宝JS支付（利用支付宝JS实现APP支付，需准备一个支付宝小程序）。

## 2. 接入前准备

### 2.1 商务准备
- 客户需已有上架到公开市场的APP
- 选择接入模式：
  - **直签模式**：商户与汇付直接签约；完成协议与入网材料提交后，审核通过将收到控制台账号与密码
  - **服务商模式**：服务商与汇付签约，通过接口或控制台为商户完成入网
- 在斗拱完成商户进件入网：
  - **直签**：由汇付侧开通，无需额外操作
  - **服务商**：
    - 控台入网：参考服务商控台进件流程、渠道商接入指引
    - API入网：企业商户调用"企业商户基本信息入驻"，小微商户调用"个人商户基本信息入驻"完成开户、绑卡、结算配置
- 选择接入功能并准备材料：
  - **支付宝支付**：需要支付宝商户号和相关配置

### 2.2 对接准备
1) **密钥获取**：参见"联调公私钥参数获取"
2) **公共参数获取**：登录服务商/商户控制台，在"开发设置-开发者信息"获取 `sys_id`、`product_id`
3) **业务开通与配置**：
   - **服务商模式**：
     - 步骤一：服务商功能与权限开通；汇付审核资料并开通支付与费率配置
     - 步骤二：为商户开通相应功能与权限；可在服务商控台配置或调用"商户业务开通"接口
   - **直签商户**：与客户经理确认功能、费率配置已完成

## 3. 开发指引

### 3.1 对接规范
- 接口均为POST，数据格式JSON
- 加签验签遵循"v2版接口加签验签"规范
- 使用聚合正扫接口：`/v3/trade/payment/jspay`

### 3.2 确认业务配置
- **斗拱侧**：
  - 商户支付宝业务开通、费率配置完成；可在控台确认
  - 商户支付宝实名认证完成且已授权

### 3.3 系统调用流程

#### 3.3.1 支付宝NATIVE支付（扫码支付）

**适用场景**：商户APP生成支付宝二维码，用户使用支付宝APP扫码完成支付

**实现步骤**：

1) **调用聚合正扫接口下单**：
```php
$payment = new \cccdl\DougongPay\Payment\Payment($dougongConfig);

$result = $payment->create([
    'trade_type' => 'A_NATIVE',
    'req_seq_id' => 'ORDER_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => 'your_huifu_id',
    'goods_desc' => '测试商品',
    'trans_amt' => '0.01',
    'notify_url' => 'https://your-domain.com/notify',
    'alipay_data' => json_encode([
        'store_id' => '', // 门店ID
        'operator_id' => '123', // 操作员ID
    ])
]);
```

2) **获取二维码链接**：
- 接口同步返回 `qr_code` 字段，包含支付宝二维码链接
- 商户APP将此链接转换为二维码图片展示给用户

3) **用户扫码支付**：
- 用户使用支付宝APP扫描二维码
- 输入支付密码完成支付

4) **接收异步通知**：
- 支付完成后，斗拱会向 `notify_url` 发送异步通知
- 商户需要验证签名并返回 `RECV_ORD_ID_` + 订单号

#### 3.3.2 支付宝JS支付（小程序支付）

**适用场景**：商户APP跳转支付宝小程序完成支付

**实现步骤**：

1) **调用聚合正扫接口下单**：
```php
$payment = new \cccdl\DougongPay\Payment\Payment($dougongConfig);

$result = $payment->create([
    'trade_type' => 'A_JSAPI',
    'req_seq_id' => 'ORDER_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => 'your_huifu_id',
    'goods_desc' => '测试商品',
    'trans_amt' => '0.01',
    'notify_url' => 'https://your-domain.com/notify',
    'alipay_data' => json_encode([
        'buyer_id' => '支付宝用户ID', // 如果有的话
        'store_id' => '', // 门店ID
        'operator_id' => '123', // 操作员ID
    ])
]);
```

2) **获取支付信息**：
- 接口同步返回 `pay_info` 字段，包含支付宝JS支付所需信息
- 商户APP使用此信息调起支付宝支付

3) **调起支付宝支付**：
- 商户APP通过支付宝SDK调起支付
- 用户在支付宝中完成支付流程

4) **接收支付结果**：
- 支付宝返回支付结果给商户APP
- 同时斗拱发送异步通知确认支付状态

### 3.4 异步通知处理

支付完成后，斗拱会向商户指定的 `notify_url` 发送异步通知：

```php
// 接收异步通知
$notifyData = json_decode(file_get_contents('php://input'), true);

// 验证签名
$signTool = new \cccdl\DougongPay\Tools\SignTool($dougongConfig);
if ($signTool->verifyNotify($notifyData)) {
    $respData = json_decode($notifyData['resp_data'], true);

    if ($respData['trans_stat'] === 'S') {
        // 支付成功，处理业务逻辑
        echo 'RECV_ORD_ID_' . $respData['req_seq_id'];
    } else {
        // 支付失败
        echo 'RECV_ORD_ID_' . $respData['req_seq_id'];
    }
} else {
    // 验签失败
    http_response_code(400);
}
```

## 4. API 列表

- **聚合正扫**：创建支付订单，获取支付凭证
- **交易查询**：查询支付交易信息
- **交易退款**：申请退款
- **交易退款查询**：查询退款进度及结果
- **交易关单**：长时间未支付的关单处理

## 5. 常见问题（FAQ）

### 5.1 支付宝支付报错相关问题

1) **支付宝支付报错："商户未开通该产品权限"**
   - **原因**：商户未开通支付宝支付权限
   - **方案**：联系客户经理开通支付宝支付权限

2) **支付宝支付报错："签名验证失败"**
   - **原因**：签名算法错误或密钥配置错误
   - **方案**：检查RSA密钥配置和签名算法实现

3) **支付接口调用报错**：`resp_desc: 数据权限认证失败`
   - **原因**：商户信息校验未通过
   - **方案**：检查 `product_id`、`sys_id` 与 `huifu_id` 的从属关系是否正确

### 5.2 业务流程相关问题

1) **为什么交易成功以后会收到2条异步通知？**
   - 交易异步与账务异步并存，通过 `notify_type` 区分：
     - `notify_type = '1'`（交易异步）：`trans_stat = 'S'` 时，随后会推送账务异步
     - `notify_type = '2'`（账务异步）：`trans_stat = 'S'` 且 `acct_stat = 'S'` 表示交易成功-入账成功

2) **支付宝二维码多长时间过期？**
   - 默认2小时过期，可通过 `time_expire` 参数自定义过期时间

3) **支付宝支付是否支持分账？**
   - 支持，通过 `acct_split_bunch` 参数配置分账信息

## 6. 参数说明

### 6.1 支付宝专用参数（alipay_data）

支付宝支付时，可通过 `alipay_data` 参数传递支付宝专用信息：

```json
{
  "store_id": "门店ID",
  "operator_id": "操作员ID",
  "buyer_id": "支付宝用户ID",
  "buyer_logon_id": "支付宝登录账号",
  "product_code": "产品码",
  "goods_detail": [
    {
      "goods_id": "商品ID",
      "goods_name": "商品名称",
      "quantity": "数量",
      "price": "单价"
    }
  ]
}
```

### 6.2 禁用支付方式

支付宝支付支持禁用特定支付方式，通过 `limit_pay_type` 参数配置：

- `NO_CREDIT`：禁用信用卡（注意花呗支付时不能禁用信用卡）
- `BALANCE`：禁用支付宝余额
- `MONEY_FUND`：禁用支付宝余额宝
- `PCREDIT`：禁用支付宝花呗
- `PCREDIT_PAY_INSTALLMENT`：禁用支付宝花呗分期
- 更多取值参见官方文档

更多问题详见斗拱开发者社区：`https://service.dougong.net/t/qa`