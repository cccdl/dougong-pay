# 扫码交易退款接口

POST https://api.huifu.com/v3/trade/payment/scanpay/refund

最近更新时间：2025.09.12

## 应用场景

交易发生之后一段时间内，由于用户或者商户的原因需要退款时，商户可以通过本接口将支付款退还给用户，退款成功资金将原路返回。

支持的支付类型退款：
- 微信公众号：T_JSAPI
- 微信小程序：T_MINIAPP
- 微信APP支付：T_APP
- 支付宝JS：A_JSAPI
- 支付宝正扫：A_NATIVE
- 银联二维码正扫：U_NATIVE
- 银联二维码JS：U_JSAPI
- 数字货币二维码支付：D_NATIVE
- 微信反扫：T_MICROPAY
- 支付宝反扫：A_MICROPAY
- 银联反扫：U_MICROPAY
- 数字人民币反扫：D_MICROPAY

**退款期限：**
- 微信：360天
- 支付宝：360天
- 银联二维码：360天

## 适用对象

开通微信/支付宝/银联二维码/数字人民币聚合扫码功能的商户。

## 接口说明

- **请求方式**：POST
- **支持格式**：JSON
- **加签验签**：参考"接入指引-开发指南"

## 公共请求参数

- `sys_id` String(32) 必填：渠道商/商户的huifu_id
  - 主体为渠道商：填写渠道商huifu_id
  - 主体为直连商户：填写商户huifu_id
  - 示例：`6666000108854952`
- `product_id` String(32) 必填：汇付分配的产品号（例：`YYZY`）
- `sign` String(512) 必填：加签结果（见加签验签说明）
- `data` Json 必填：业务请求参数（见下）

## 业务请求参数（data）

### 必填参数

- `req_date` String(8)：请求日期，格式yyyyMMdd，示例：`20220925`
- `req_seq_id` String(128)：请求流水号，同一huifu_id下当天唯一，示例：`rQ2021121311173944175651`
- `huifu_id` String(32)：商户号，示例：`6666000123120001`
- `ord_amt` String(14)：申请退款金额，单位元，需保留小数点后两位，示例：`1.00`，最低传入`0.01`
  - **注意**：如果原交易是延时交易，退款金额必须小于等于待确认金额
- `org_req_date` String(8)：原交易请求日期，格式yyyyMMdd，示例：`20220925`

### 条件必填参数

**以下三个参数必填其一：**
- `org_hf_seq_id` String(128)：原交易全局流水号，示例：`0030default220825182711P099ac1f343f*****`
- `org_party_order_id` String(64)：原交易微信支付宝的商户单号，示例：`0323210919025510560****`
- `org_req_seq_id` String(128)：原交易请求流水号，示例：`20211021001210****`

### 可选参数

- `acct_split_bunch` String(2048)：分账对象，jsonObject字符串
- `wx_data` String(2048)：聚合正扫微信拓展参数集合，直连模式需要提供
- `digital_currency_data` String(2048)：数字货币扩展参数集合，jsonObject字符串
- `combinedpay_data` String：补贴支付信息，jsonArray字符串
- `combinedpay_data_fee_info` String：补贴支付手续费承担方信息，jsonObject字符串
- `remark` String(84)：备注，原样返回
- `loan_flag` String(2)：是否垫资退款，Y是垫资出款，N是普通出款，为空默认N
  - **注意**：延时交易退款在【交易确认退款】接口中设置loan_flag为垫资，本接口不可再次设置垫资
- `loan_undertaker` String(32)：垫资承担者，垫资方的huifu_id，为空则各自承担，不为空走第三方垫资
- `loan_acct_type` String(2)：垫资账户类型，01:基本户，05:充值户，默认充值户
- `risk_check_data` String(2048)：安全信息，jsonObject字符串
- `terminal_device_data` String(2048)：设备信息，jsonObject字符串
- `notify_url` String(512)：异步通知地址
- `unionpay_data` String(2048)：银联参数集合，jsonObject字符串

## 请求示例

```json
{
    "sys_id": "6666000108840829",
    "product_id": "YYZY",
    "data": {
        "req_date": "20240425",
        "req_seq_id": "20240425104655979csxtpgjyi1zc5x",
        "huifu_id": "6666000108854952",
        "ord_amt": "0.01",
        "org_req_date": "20221107",
        "org_hf_seq_id": "002900TOP3B221107142320P992ac139c0c00000"
    },
    "sign": "dfpg8KO1/79hHWO1JHcZjcQdWEkI2w+E3ScdJNR7OS8F00DLdZZgP5ZVuSNayJAUiuPGZbZfMw92RNXgD7..."
}
```

## 同步返回参数

### 公共返回

- `sign` String(512) 必填：返回值签名
- `data` Json 必填：业务返回参数

### data字段

#### 基础信息
- `resp_code` String(8) 必填：业务响应码
- `resp_desc` String(512) 必填：业务响应信息
- `product_id` String(32) 必填：产品号，交易时传入原样返回
- `huifu_id` String(32) 必填：商户号
- `req_date` String(8) 必填：请求日期，格式yyyyMMdd
- `req_seq_id` String(128) 必填：请求流水号，交易时传入原样返回
- `hf_seq_id` String(128) 选填：全局流水号

#### 原交易信息
- `org_req_date` String(8) 选填：原交易请求日期，格式yyyyMMdd
- `org_req_seq_id` String(128) 选填：原交易请求流水号

#### 退款交易信息
- `trans_date` String(8) 选填：退款交易发生日期，格式yyyyMMdd
- `trans_time` String(6) 选填：退款交易发生时间，格式HHMMSS
- `trans_finish_time` String(14) 选填：退款完成时间，格式yyyyMMddHHmmss
- `trans_stat` String(1) 选填：交易状态
  - `P`：处理中
  - `S`：成功
  - `F`：失败
- `ord_amt` String(14) 必填：退款金额（元），保留小数点后两位
- `actual_ref_amt` String(14) 选填：实际退款金额（元），保留小数点后两位

#### 分账与补贴信息
- `acct_split_bunch` String(2048) 选填：分账信息，jsonObject字符串
- `combinedpay_data` String 选填：补贴支付信息，jsonArray字符串
- `combinedpay_data_fee_info` String 选填：补贴支付手续费承担方信息，jsonObject字符串

#### 垫资信息
- `loan_flag` String(2) 选填：是否垫资退款，Y是垫资出款，N是普通出款
- `loan_undertaker` String(32) 选填：垫资承担者，为空则各自承担
- `loan_acct_type` String(2) 选填：垫资账户类型，01:基本户，05:充值户

#### 通道响应信息
- `wx_response` String(6000) 选填：微信返回的响应报文
- `alipay_response` String(6000) 选填：支付宝返回的响应报文，直连返回字段
- `unionpay_response` String(6000) 选填：银联返回的响应报文，Json格式
- `dc_response` String 选填：数字货币返回报文
- `bank_message` String(256) 选填：通道返回描述

#### 其他信息
- `remark` String(84) 选填：备注，原样返回
- `unconfirm_amt` String(14) 选填：待确认金额，单位元
- `fund_freeze_stat` String(16) 选填：资金冻结状态，FREEZE冻结/UNFREEZE解冻
- `trans_fee_ref_allowance_info` String 选填：手续费补贴返还信息，jsonObject字符串
- `pay_channel` String(1) 选填：交易通道，A-支付宝、T-微信、U-银联二维码、D-数字货币
- `is_refund_fee_flag` String(1) 选填：是否退还手续费，Y或空:退费，N-不退费

## 返回示例

```json
{
    "data": {
        "resp_code": "00000100",
        "resp_desc": "交易正在处理中",
        "huifu_id": "6666000018328947",
        "product_id": "SPIN",
        "req_seq_id": "20210919282850529",
        "hf_seq_id": "00310TOP1GR210919003147P171ac13262200000",
        "req_date": "20210919",
        "trans_date": "20210919",
        "trans_stat": "P",
        "ord_amt": "0.01",
        "acct_split_bunch": "{\"acct_infos\":[{\"div_amt\":\"0.01\",\"huifu_id\":\"6666000018328947\"}]}",
        "bank_message": "",
        "remark": "12321312"
    },
    "sign": "kMhWRU5kcPlaBe3zYLO/GwGtcPJIOKri2EQfPPTtkSw1fLPdDS7UnmZ7cm+KKbQ6QVCfYa22LmqRvc4LaREULX..."
}
```

## 异步通知参数

异步返回报文有两种模式：间联模式与直联模式。如果商户业务开通时做了微信直连配置（wx_zl_conf），则属于直联模式。

### 主要异步通知参数

- `resp_code` String(8) 必填：业务响应码
- `resp_desc` String(512) 必填：业务响应信息
- `trans_type` String(40) 必填：交易类型，TRANS_REFUND：交易退款
- `trans_stat` String(1) 选填：交易状态，P:处理中、S:成功、F:失败
- `ord_amt` String(14) 必填：退款金额，单位元
- `actual_ref_amt` String(14) 选填：实际退款金额，单位元
- `total_ref_amt` String(14) 必填：原交易累计退款金额，单位元
- `total_ref_fee_amt` String(14) 必填：原交易累计退款手续费金额，单位元
- `ref_cut` String(14) 必填：累计退款次数
- `party_order_id` String(64) 选填：微信支付宝的商户单号

## 业务返回码

- `00000000`：交易成功
- `00000100`：交易处理中
- `10000000`：入参数据不符合接口要求
- `20000001`：并发冲突，请稍后重试
- `21000000`：原交易请求流水号、原交易微信支付宝的商户单号、原交易全局流水号不能同时为空
- `21000000`：数字货币交易退款原因必填
- `22000000`：产品号不存在
- `22000000`：产品号状态异常
- `22000002`：商户信息不存在
- `22000002`：商户状态异常
- `22000003`：账户信息不存在
- `22000004`：暂未开通退款权限
- `22000004`：暂未开通分账退款权限
- `22000005`：结算配置信息不存在
- `23000000`：原交易未入账，不能发起退款
- `23000001`：原交易不存在
- `23000002`：退款手续费承担方和原交易手续费承担方不一致
- `23000003`：申请退款金额大于可退余额
- `23000003`：退款金额大于待确认金额
- `23000003`：手续费退款金额大于可退手续费金额
- `23000003`：申请退款金额大于可退款余额
- `23000003`：退款分账金额总和必须等于退款订单金额
- `23000003`：账户余额不足
- `23000004`：不支持预授权撤销类交易
- `23000004`：不支持刷卡撤销类交易
- `23000004`：优惠交易不支持部分退款
- `23000004`：该交易为部分退款，需传入分账串
- `23000004`：优惠退款不支持传入分账串
- `23000004`：分账串信息与原交易不匹配
- `90000000`：业务执行失败，可用余额不足
- `90000000`：交易存在风险
- `98888888`：系统错误
- `99999999`：系统异常，请稍后重试

## PHP 使用示例

```php
use cccdl\DougongPay\Payment\PaymentRefund;

// 创建退款实例
$paymentRefund = new PaymentRefund($dougongConfig);

// 通过原交易全局流水号退款
$result = $paymentRefund->refund([
    'req_date' => date('Ymd'),
    'req_seq_id' => 'REFUND_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => '6666000000000000',
    'ord_amt' => '0.01',
    'org_req_date' => '20240405',
    'org_hf_seq_id' => '0030default220825182711P099ac1f343f*****'
]);

// 通过原交易请求流水号退款
$result = $paymentRefund->refund([
    'req_date' => date('Ymd'),
    'req_seq_id' => 'REFUND_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => '6666000000000000',
    'ord_amt' => '0.01',
    'org_req_date' => '20240405',
    'org_req_seq_id' => '2021091895616****'
]);

// 通过原交易商户单号退款
$result = $paymentRefund->refund([
    'req_date' => date('Ymd'),
    'req_seq_id' => 'REFUND_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => '6666000000000000',
    'ord_amt' => '0.01',
    'org_req_date' => '20240405',
    'org_party_order_id' => '0323210919025510560****'
]);

// 检查退款结果
if ($result['data']['resp_code'] === '00000000') {
    $refundStatus = $result['data']['trans_stat'];
    switch ($refundStatus) {
        case 'S':
            echo "退款成功";
            break;
        case 'P':
            echo "退款处理中";
            break;
        case 'F':
            echo "退款失败";
            break;
    }
} elseif ($result['data']['resp_code'] === '00000100') {
    echo "退款交易正在处理中";
} else {
    echo "退款失败：" . $result['data']['resp_desc'];
}
```

## Webhook 说明

斗拱交易完成后除了返回异步消息也支持另外发送webhook退款事件【refund.standard】。webhook事件可以灵活配置多个接收端用于驱动业务流程。

## 注意事项

1. **退款条件**：原交易必须已入账才能发起退款
2. **退款金额限制**：不能超过原交易可退余额
3. **延时交易退款**：退款金额必须小于等于待确认金额
4. **分账退款**：如果原交易包含分账，需要传入对应的分账信息
5. **垫资退款**：延时交易退款不可在此接口设置垫资标志
6. **参数校验**：`org_hf_seq_id`、`org_party_order_id`、`org_req_seq_id` 三选一必填
7. **幂等性**：相同参数重复调用会返回相同结果
8. **响应验签**：建议对返回结果进行签名验证确保数据完整性
9. **异步通知处理**：务必实现异步通知处理逻辑确保退款状态准确