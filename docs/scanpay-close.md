# 扫码交易关单接口

POST https://api.huifu.com/v2/trade/payment/scanpay/close

最近更新时间：2025.02.25

## 应用场景

服务商/商户系统通过本接口发起订单关闭请求。

## 适用对象

开通微信/支付宝权限的商户。

**注意事项：**
- 银联、数字货币订单不支持关单
- 原交易已是终态（成功/失败）的，关单会失败
- 不允许关闭一分钟以内的订单

## 接口说明

- **请求方式**：POST
- **支持格式**：JSON
- **加签验签**：参考"接入指引-开发指南"

## 公共请求参数

- `sys_id` String(32) 必填：渠道商/商户的huifu_id
  - 主体为渠道商：填写渠道商huifu_id
  - 主体为直连商户：填写商户huifu_id
  - 示例：`6666000108854952`
- `product_id` String(32) 必填：汇付分配的产品号（例：`MCS`）
- `sign` String(512) 必填：加签结果（见加签验签说明）
- `data` Json 必填：业务请求参数（见下）

## 业务请求参数（data）

### 必填参数

- `req_date` String(8)：请求日期，格式yyyyMMdd，示例：`20220905`
- `req_seq_id` String(128)：请求流水号，同一huifu_id下当天唯一，示例：`rQ2021121311173944134649875651`
- `huifu_id` String(32)：商户号，开户后自动生成，示例：`6666000000000000`
- `org_req_date` String(8)：原交易请求日期，格式yyyyMMdd，示例：`20220905`

### 条件必填参数

**以下两个参数必填其一：**
- `org_hf_seq_id` String(128)：原交易返回的全局流水号，示例：`0030default220825182711P099ac1f343f*****`
- `org_req_seq_id` String(128)：原交易请求流水号，示例：`20211021001210****`

## 请求示例

```json
{
    "sys_id": "6666000108840829",
    "product_id": "YYZY",
    "data": {
        "req_date": "20240425",
        "req_seq_id": "20240425105638838efkflqyi00ond6",
        "huifu_id": "6666000000000001",
        "org_req_date": "20240405",
        "org_req_seq_id": "2021091895616****"
    },
    "sign": "ilDS3LYVqlJYS+q1F/MhiFfVZOyHngNAg3i0XvnqqHv2guQXyfcIOVp4us9WCaqF..."
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
- `huifu_id` String(32) 必填：商户号，示例：`6666000000000000`
- `req_date` String(8) 必填：请求日期，交易时传入原样返回，示例：`20220905`
- `req_seq_id` String(128) 必填：请求流水号，交易时传入原样返回

#### 原交易信息
- `org_req_date` String(8) 必填：原交易请求日期，格式yyyyMMdd，示例：`20220905`
- `org_req_seq_id` String(128) 选填：原交易请求流水号，示例：`rQ20221010134649875651`
- `org_hf_seq_id` String(128) 选填：原交易的全局流水号，示例：`0030default220825182711P099ac1f343f*****`
- `org_trans_stat` String(1) 选填：原交易状态
  - `P`：处理中
  - `S`：成功
  - `F`：失败

#### 关单状态
- `trans_stat` String(1) 必填：关单状态
  - `P`：处理中
  - `S`：成功
  - `F`：失败

## 返回示例

```json
{
    "data": {
        "resp_code": "10000016",
        "resp_desc": "原订单已为终态,请发起查询交易获取",
        "huifu_id": "6666000018328947",
        "req_date": "20210919",
        "req_seq_id": "2021091981146428003",
        "org_req_date": "20210918",
        "org_req_seq_id": "202109187312431237001",
        "trans_stat": "F"
    },
    "sign": "JooKBwoym+a4qA5xnSawA4NSAK/sPtxKR7vtErxeKAXJklD4WtbN8hjd2tUIgmOwH9g25HEtitn22xK2T+73u+xsQX31X/rtuY8/zWhr8/Jn0GF/hh1QbOxdYZybM2z4m/oqi6H0SZAajgKzQcrfNHdhvFCD2GP+cVI2rQEm/lMXYkzW7ik4AWicJSA429g7O+rQf+TSMA+qgefkhsMZ3xZeCl0sVDCqkyRfAc7m28cYKQZdFQY0aOsMMgUF7Wsq78R+McYmseASoDZv8dI1//Jl5+uKFYrMRq1ADt8s01pPGILjWSJPkj6cAcMd7pqRa4k8No7+Cv5MunFP3Wwhhg=="
}
```

## 业务返回码

- `00000000`：交易成功
- `00000100`：交易处理中
- `10000000`：入参数据不符合接口要求
- `20000001`：不允许关闭一分钟以内的订单
- `20000001`：并发冲突,请稍后重试
- `21000000`：原请求流水号和原全局流水号不能同时为空
- `22000000`：产品号不存在
- `22000000`：产品号状态异常
- `23000000`：原订单已为终态,无法发起关单操作
- `23000000`：关单状态为终态，不能重复关单
- `23000001`：原交易不存在
- `23000004`：原订单为银联二维码交易，不支持关单
- `90000000`：业务执行失败
- `98888888`：系统错误
- `99999999`：系统异常,请稍后重试

## PHP 使用示例

```php
use cccdl\DougongPay\Payment\PaymentClose;

// 创建关单实例
$paymentClose = new PaymentClose($dougongConfig);

// 通过原交易请求流水号关单
$result = $paymentClose->close([
    'req_date' => date('Ymd'),
    'req_seq_id' => 'CLOSE_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => '6666000000000000',
    'org_req_date' => '20240405',
    'org_req_seq_id' => '2021091895616****'
]);

// 通过原交易全局流水号关单
$result = $paymentClose->close([
    'req_date' => date('Ymd'),
    'req_seq_id' => 'CLOSE_' . date('YmdHis') . rand(1000, 9999),
    'huifu_id' => '6666000000000000',
    'org_req_date' => '20240405',
    'org_hf_seq_id' => '0030default220825182711P099ac1f343f*****'
]);

// 检查关单结果
if ($result['data']['resp_code'] === '00000000') {
    $closeStatus = $result['data']['trans_stat'];
    switch ($closeStatus) {
        case 'S':
            echo "关单成功";
            break;
        case 'P':
            echo "关单处理中";
            break;
        case 'F':
            echo "关单失败";
            break;
    }
} else {
    echo "关单失败：" . $result['data']['resp_desc'];
}
```

## Webhook 说明

斗拱交易完成后除了返回异步消息也支持另外发送webhook关单事件【trans.close】。webhook事件可以灵活配置接收端用于驱动业务流程。

## 注意事项

1. **关单限制**：银联、数字货币订单不支持关单操作
2. **时间限制**：不允许关闭一分钟以内的订单
3. **状态判断**：以 `trans_stat` 字段为准判断关单状态
4. **原交易状态**：已为终态（成功/失败）的订单无法关单
5. **参数校验**：`org_hf_seq_id` 和 `org_req_seq_id` 二选一必填
6. **幂等性**：相同参数重复调用会返回相同结果
7. **响应验签**：建议对返回结果进行签名验证确保数据完整性