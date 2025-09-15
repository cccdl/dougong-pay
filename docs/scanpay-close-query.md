# 扫码交易关单查询接口

POST https://api.huifu.com/v2/trade/payment/scanpay/closequery

最近更新时间：2025.02.25

## 应用场景

服务商/商户发起关单请求后，未收到关单结果，可通过本接口查询关单状态。

## 适用对象

开通微信/支付宝/云闪付/数字人民币权限的商户。
注：只能通过原交易查询关单

## 接口说明

- **请求方式**：POST
- **支持格式**：JSON
- **加签验签**：参考"接入指引-开发指南"

## 公共请求参数

- `sys_id` String(32) 必填：渠道商/代理商/商户的huifu_id
  - 主体为渠道商/代理商：填写渠道商/代理商huifu_id
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
- `org_req_seq_id` String(128)：原交易请求流水号，示例：`20211021001210****`
- `org_hf_seq_id` String(128)：原交易返回的全局流水号，示例：`0030default220825182711P099ac1f343f*****`

## 请求示例

```json
{
    "sys_id": "6666000108840829",
    "product_id": "YYZY",
    "data": {
        "req_date": "20240425",
        "req_seq_id": "20240425104052910l0c5dsjxmqp****",
        "huifu_id": "6666000000000001",
        "org_req_date": "20240328",
        "org_req_seq_id": "20240129555522220211711612****"
    },
    "sign": "M6K0uE0nu5KFVJRs1dCsEdst2zLm3Vwnov+9NmNtj+9WVQw/92TdyAQrFS0uwXiKrS8FmoqSTKXM4T0PFifhHRUHcmfyMz5WUGLZorxCzi+9BeNc6yoE/yL+VuunoiH/Zlx4vK0/3q5Vs55MJ/BUgxT/HGzImFcZY6qtsyUmzOlZtV8+IKRKSFE7kn6TzhnHjgMlL7EiEymZ6QA/EzmaL78eBRpCJytSOouK+oNYvxlAyO1Se4ePQx0hifTHShVgeJg055gGmXXVlIU5kIDvfS0ARujRA8L/RCiYDog7pY4x9/rCdpsabVF79d4YdG8md1GYwALntgP5BPrlbu3jQw=="
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
- `huifu_id` String(32) 必填：商户号
- `req_date` String(8) 必填：请求日期，交易时传入，原样返回，格式yyyyMMdd
- `req_seq_id` String(128) 必填：请求流水号，交易时传入，原样返回

#### 原交易信息
- `org_req_date` String(8) 必填：原交易请求日期，格式yyyyMMdd
- `org_req_seq_id` String(128) 选填：原交易请求流水号
- `org_hf_seq_id` String(128) 选填：原交易的全局流水号
- `org_trans_stat` String(1) 选填：原交易状态
  - `P`：处理中
  - `S`：成功
  - `F`：失败

#### 关单状态
- `trans_stat` String(1) 必填：关单状态（以此字段为准）
  - `P`：处理中
  - `S`：成功
  - `F`：失败

## 返回示例

```json
{
    "data": {
        "resp_code": "00000000",
        "resp_desc": "成功",
        "huifu_id": "6666000018328947",
        "req_date": "20210919",
        "req_seq_id": "202109190835286001",
        "org_req_date": "20210918",
        "org_req_seq_id": "202109187312431237001",
        "org_trans_stat": "S",
        "trans_stat": "F"
    },
    "sign": "JooKBwoym+a4qA5xnSawA4NSAK/sPtxKR7vtErxeKAXJklD4WtbN8hjd2tUIgmOwH9g25HEtitn22xK2T+73u+xsQX31X/rtuY8/zWhr8/Jn0GF/hh1QbOxdYZybM2z4m/oqi6H0SZAajgKzQcrfNHdhvFCD2GP+cVI2rQEm/lMXYkzW7ik4AWicJSA429g7O+rQf+TSMA+qgefkhsMZ3xZeCl0sVDCqkyRfAc7m28cYKQZdFQY0aOsMMgUF7Wsq78R+McYmseASoDZv8dI1//Jl5+uKFYrMRq1ADt8s01pPGILjWSJPkj6cAcMd7pqRa4k8No7+Cv5MunFP3Wwhhg=="
}
```

## 业务返回码

- `00000000`：查询成功
- `10000000`：入参数据不符合接口要求
- `21000000`：原请求流水号和原全局流水号不能同时为空
- `22000000`：产品号不存在
- `22000000`：产品号状态异常
- `23000001`：原交易不存在
- `98888888`：系统错误

## PHP 使用示例

```php
use cccdl\DougongPay\Payment\PaymentCloseQuery;

// 创建关单查询实例
$paymentCloseQuery = new PaymentCloseQuery($dougongConfig);

// 通过原交易请求流水号查询关单状态
$result = $paymentCloseQuery->query([
    'req_date' => '20240425',
    'req_seq_id' => '20240425104052910l0c5dsjxmqp****',
    'huifu_id' => '6666000000000001',
    'org_req_date' => '20240328',
    'org_req_seq_id' => '20240129555522220211711612****'
]);

// 通过原交易全局流水号查询关单状态
$result = $paymentCloseQuery->query([
    'req_date' => '20240425',
    'req_seq_id' => '20240425104052910l0c5dsjxmqp****',
    'huifu_id' => '6666000000000001',
    'org_req_date' => '20240328',
    'org_hf_seq_id' => '0030default220825182711P099ac1f343f*****'
]);

// 检查查询结果
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

    // 检查原交易状态
    if (isset($result['data']['org_trans_stat'])) {
        $orgTransStatus = $result['data']['org_trans_stat'];
        echo "原交易状态：" . ($orgTransStatus === 'S' ? '成功' : ($orgTransStatus === 'P' ? '处理中' : '失败'));
    }
} else {
    echo "查询失败：" . $result['data']['resp_desc'];
}
```

## 注意事项

1. **查询条件**：`org_req_seq_id`、`org_hf_seq_id` 两个参数必填其一
2. **关单状态判断**：以 `trans_stat` 字段为准，不要依赖 `resp_code`
3. **原交易关联**：只能通过原交易查询关单状态
4. **请求流水号**：查询接口需要自己的请求流水号，同一商户当天唯一
5. **幂等性**：查询接口天然幂等，可重复调用
6. **响应验签**：建议对返回结果进行签名验证确保数据完整性