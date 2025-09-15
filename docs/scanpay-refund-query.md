# 扫码交易退款查询接口

POST https://api.huifu.com/v3/trade/payment/scanpay/refundquery

最近更新时间：2025.07.24

## 应用场景

商家调用退款接口未收到状态返回，调用本接口查询退款结果。支持微信公众号-T_JSAPI、小程序-T_MINIAPP、微信APP支付-T_APP、支付宝JS-A_JSAPI、支付宝正扫-A_NATIVE、银联二维码正扫-U_NATIVE、银联二维码JS-U_JSAPI、数字货币二维码支付-D_NATIVE，以及微信反扫、支付宝反扫、银联二维码反扫、数字人民币反扫交易退款的查询。

## 适用对象

开通微信/支付宝/银联二维码/数字人民币聚合扫码功能的商户。

## 接口说明

- **请求方式**：POST
- **支持格式**：JSON
- **加签验签**：参考"接入指引-开发指南"

## 公共请求参数

- `sys_id` String(32) 必填：渠道商/代理商/商户的huifu_id
  - 主体为渠道商/代理商：填写渠道商/代理商huifu_id
  - 主体为直连商户：填写商户huifu_id
  - 示例：`6666000108854952`
- `product_id` String(32) 必填：汇付分配的产品号（例：`YYZY`）
- `sign` String(512) 必填：加签结果（见加签验签说明）
- `data` Json 必填：业务请求参数（见下）

## 业务请求参数（data）

### 必填参数

- `huifu_id` String(32)：商户号，示例：`6666000000000000`

### 条件必填参数

**以下三个参数必填其一：**
- `org_hf_seq_id` String(128)：退款全局流水号，示例：`0030default220825182711P099ac1f343f*****`
- `org_req_seq_id` String(128)：退款请求流水号，示例：`20211021001210****`
- `mer_ord_id` String(50)：终端订单号，示例：`166726708335243****`

### 可选参数

- `org_req_date` String(8)：退款请求日期，格式yyyyMMdd，示例：`20220925`
  - 传入退款全局流水号时非必填，其他场景必填

## 请求示例

```json
{
    "sys_id": "6666000108840829",
    "product_id": "YYZY",
    "data": {
        "org_req_date": "20221110",
        "org_hf_seq_id": "003100TOP2B221110093241P139ac139c0c00000",
        "huifu_id": "6666000108854952"
    },
    "sign": "DRJFSTH6erC7hyU33tHP6o9E+L8mwGjcas6G1E4Qb/fgdOpMEPahsUe5ko0b5c60pr0Cmk3MaY3N/DkMH..."
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

#### 退款订单信息
- `org_hf_seq_id` String(128) 选填：退款全局流水号
- `org_req_date` String(8) 选填：退款请求日期，格式yyyyMMdd
- `org_req_seq_id` String(128) 选填：退款请求流水号
- `mer_ord_id` String(50) 选填：终端订单号
- `org_party_order_id` String(64) 选填：原交易用户账单上的商户订单号

#### 退款交易信息
- `ord_amt` String(14) 必填：退款金额，单位元，保留小数点后两位
- `actual_ref_amt` String(14) 选填：实际退款金额，单位元，保留小数点后两位
- `trans_date` String(8) 选填：交易发生日期，格式yyyyMMdd
- `trans_time` String(6) 选填：交易发生时间，格式HHMMSS
- `trans_finish_time` String(14) 选填：退款完成时间，格式yyyyMMddHHmmss
- `trans_type` String(20) 选填：交易类型，示例：`TRANS_REFUND`
- `trans_stat` String(1) 选填：交易状态（以此字段为准）
  - `P`：处理中
  - `S`：成功
  - `F`：失败
  - `I`：初始（罕见，请联系技术人员）

#### 手续费信息
- `fee_amt` String(14) 选填：手续费金额，单位元，保留小数点后两位
- `is_refund_fee_flag` String(1) 选填：是否退还手续费，Y或空:退费，N-不退费

#### 分账与补贴信息
- `acct_split_bunch` String 选填：分账对象，jsonObject字符串
- `split_fee_info` String 选填：分账手续费信息
- `combinedpay_data` Array 选填：补贴支付信息，jsonArray字符串
- `combinedpay_data_fee_info` String 选填：补贴支付手续费承担方信息，jsonObject字符串
- `trans_fee_ref_allowance_info` String 选填：手续费补贴返还信息，jsonObject字符串

#### 通道响应信息
- `wx_response` String 选填：微信返回的响应报文
- `alipay_response` String 选填：支付宝返回的响应报文
- `unionpay_response` String(6000) 选填：银联返回的响应报文，Json格式
- `dc_response` String 选填：数字货币返回报文
- `bank_message` String(256) 选填：通道返回描述
- `pay_channel` String(1) 选填：交易通道，A-支付宝、T-微信、U-银联二维码、D-数字货币

#### 预授权相关（仅预授权交易）
- `pre_auth_cancel_amt` String(14) 选填：预授权撤销金额
- `pre_auth_cance_fee_amount` String(14) 选填：预授权撤销返还手续费
- `pre_auth_hf_seq_id` String(128) 选填：原预授权全局流水号
- `auth_no` String(6) 选填：授权号
- `org_auth_no` String(6) 选填：原授权号

#### 其他信息
- `remark` String(84) 选填：备注，原样返回
- `mer_name` String(128) 选填：商户名称
- `shop_name` String(128) 选填：店铺名称
- `mer_priv` String 选填：商户私有域
- `debit_flag` String(1) 选填：借贷标识，1-借,2-贷，3-其他
- `org_out_order_id` String(128) 选填：原外部订单号
- `unconfirm_amt` String(14) 选填：待确认总金额，单位元
- `confirmed_amt` String(14) 选填：已确认总金额，单位元

#### 分期相关
- `fq_acq_ord_amt` String(14) 选填：分期退款金额，单位元
- `fq_acq_fee_amt` String(14) 选填：分期退款手续费金额，单位元
- `oth_ord_amt` String(14) 选填：除分期外的退款金额，单位元
- `oth_fee_amt` String(14) 选填：除分期外的退款手续费金额，单位元

## 返回示例

```json
{
    "data": {
        "org_req_date": "20210923",
        "trans_date": "20210923",
        "ord_amt": "0.01",
        "resp_desc": "成功",
        "trans_stat": "S",
        "org_hf_seq_id": "00310TOP1GR210923175212P241ac13262200000",
        "bank_message": "",
        "actual_ref_amt": "0.01",
        "org_req_seq_id": "2021092399880559447",
        "resp_code": "00000000",
        "huifu_id": "6666000018328947",
        "acct_split_bunch": "{\"acct_infos\":[{\"div_amt\":\"0.01\",\"huifu_id\":\"6666000018328947\"}]}"
    },
    "sign": "ioAey/Ovt/b4jsAre2uoLkFQ9OX5CRufpjHddgCPnlS+lwN28HTmEYUE9Wp32CyO0Cyu4EmrPtb1ZDpM4d..."
}
```

## 业务返回码

- `00000000`：查询成功
- `10000000`：入参数据不符合接口要求
- `20000001`：并发冲突，请稍后重试
- `21000000`：原退款全局流水号、原退款请求流水号、外部订单号不能同时为空
- `22000000`：产品号不存在
- `22000000`：产品号状态异常
- `23000001`：原交易不存在
- `99999999`：系统异常，请稍后重试

## PHP 使用示例

```php
use cccdl\DougongPay\Payment\PaymentRefundQuery;

// 创建退款查询实例
$paymentRefundQuery = new PaymentRefundQuery($dougongConfig);

// 通过退款全局流水号查询
$result = $paymentRefundQuery->query([
    'huifu_id' => '6666000000000000',
    'org_hf_seq_id' => '0030default220825182711P099ac1f343f*****'
]);

// 通过退款请求流水号查询
$result = $paymentRefundQuery->query([
    'huifu_id' => '6666000000000000',
    'org_req_seq_id' => '20211021001210****',
    'org_req_date' => '20240405'
]);

// 通过终端订单号查询
$result = $paymentRefundQuery->query([
    'huifu_id' => '6666000000000000',
    'mer_ord_id' => '166726708335243****',
    'org_req_date' => '20240405'
]);

// 检查查询结果
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
        case 'I':
            echo "退款初始状态，请联系技术支持";
            break;
    }
} else {
    echo "查询失败：" . $result['data']['resp_desc'];
}
```

## 注意事项

1. **查询条件**：`org_hf_seq_id`、`org_req_seq_id`、`mer_ord_id` 三个参数必填其一
2. **交易状态判断**：以 `trans_stat` 字段为准，不要依赖 `resp_code`
3. **初始状态处理**：返回 `trans_stat=I` 时请联系客服确认订单问题
4. **参数校验**：查询接口相对宽松，但建议仍进行基础参数校验
5. **幂等性**：查询接口天然幂等，可重复调用
6. **响应验签**：建议对返回结果进行签名验证确保数据完整性
7. **退款日期**：传入退款全局流水号时 `org_req_date` 非必填，其他场景必填