# 扫码交易查询接口

POST https://api.huifu.com/v3/trade/payment/scanpay/query

最近更新时间：2025.07.18

## 应用场景

服务商/商户系统因网络原因未收到交易状态，可以通过本接口主动查询订单状态。支持微信公众号-T_JSAPI、小程序-T_MINIAPP、微信APP支付-T_APP、支付宝JS-A_JSAPI、支付宝正扫-A_NATIVE、银联二维码正扫-U_NATIVE、银联二维码JS-U_JSAPI、数字货币二维码支付-D_NATIVE，以及微信反扫、支付宝反扫、银联二维码反扫、数字人民币反扫交易查询。

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
  - 示例：`6666000123120000`
- `product_id` String(32) 必填：汇付分配的产品号（例：`MCS`）
- `sign` String(512) 必填：加签结果（见加签验签说明）
- `data` Json 必填：业务请求参数（见下）

## 业务请求参数（data）

### 必填参数

- `huifu_id` String(32)：汇付商户号，示例：`6666000000000000`

### 条件必填参数

**以下三个参数必填其一：**
- `out_ord_id` String(32)：汇付服务订单号，示例：`1234323JKHDFE1243252`
- `org_hf_seq_id` String(128)：创建服务订单返回的汇付全局流水号，示例：`00290TOP1GR210919004230P853ac132622*****`
- `org_req_seq_id` String(128)：服务订单创建请求流水号，示例：`20211021001210****`

### 可选参数

- `org_req_date` String(8)：原机构请求日期，格式 `yyyyMMdd`，示例：`20220125`
  - 传入 `org_hf_seq_id` 时非必填，其他场景必填

## 请求示例

```json
{
    "sys_id": "6666000108840829",
    "product_id": "YYZY",
    "data": {
        "org_req_seq_id": "2024040522182635****",
        "org_req_date": "20240405",
        "huifu_id": "6666000000000001"
    },
    "sign": "Cepjaip5grYBDTy6k5DPmlgfwgI6wdYbO8NFxJRIo1udximn+WZk2fYbDFH5RPqc..."
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
- `bagent_id` String(32) 选填：渠道商商户号

#### 订单信息
- `org_req_date` String(8) 必填：原机构请求日期，格式 `yyyyMMdd`
- `org_hf_seq_id` String(128) 选填：交易返回的全局流水号
- `org_req_seq_id` String(128) 选填：原机构请求流水号
- `out_trans_id` String(64) 选填：用户账单上的交易订单号
- `party_order_id` String(64) 选填：用户账单上的商户订单号

#### 交易信息
- `trans_amt` String(14) 必填：交易金额，单位元，示例：`1.00`
- `pay_amt` String(14) 选填：消费者实付金额，单位元
- `settlement_amt` String(14) 选填：结算金额，单位元
- `unconfirm_amt` String(14) 选填：待确认总金额，单位元
- `confirmed_amt` String(14) 选填：已确认总金额，单位元
- `trans_type` String(16) 选填：交易类型
  - `T_JSAPI`：微信公众号支付
  - `T_MINIAPP`：微信小程序支付
  - `T_APP`：微信APP支付
  - `A_JSAPI`：支付宝JS
  - `A_NATIVE`：支付宝正扫
  - `U_NATIVE`：银联正扫
  - `U_JSAPI`：银联JS
  - `T_MICROPAY`：微信反扫
  - `A_MICROPAY`：支付宝反扫
  - `U_MICROPAY`：银联反扫
  - `D_NATIVE`：数字人民币正扫
  - `D_MICROPAY`：数字人民币反扫
  - `T_H5`：微信直连H5支付
- `trans_stat` String(1) 选填：交易状态（以此字段为准）
  - `P`：处理中
  - `S`：成功
  - `F`：失败
  - `I`：初始（罕见，请联系技术人员）

#### 时间信息
- `trans_time` String(6) 选填：交易时间，格式 `HHMMSS`
- `end_time` String(14) 选填：支付完成时间，格式 `yyyyMMddHHMMSS`
- `acct_date` String(8) 选填：账务日期，格式 `yyyyMMdd`
- `freeze_time` String(14) 选填：冻结时间，格式 `yyyyMMddHHMMSS`
- `unfreeze_time` String(14) 选填：解冻时间，格式 `yyyyMMddHHMMSS`

#### 账务信息
- `delay_acct_flag` String(1) 必填：是否延时交易，`Y`延迟/`N`不延迟
- `acct_id` String(9) 选填：商户账户号
- `acct_stat` String(1) 选填：账务状态
  - `I`：初始
  - `P`：处理中
  - `S`：成功
  - `F`：失败

#### 手续费信息
- `fee_huifu_id` String(32) 选填：手续费商户号
- `fee_formula_infos` Array 选填：手续费费率信息（jsonArray格式）
- `fee_amt` String(14) 选填：手续费金额，单位元
- `fee_type` String(8) 选填：手续费扣款标志，`INNER`内扣/`OUTSIDE`外扣

#### 分账信息
- `div_flag` String(1) 必填：是否分账交易，`Y`分账/`N`非分账
- `acct_split_bunch` String(4000) 选填：分账对象（jsonObject字符串）
- `split_fee_info` String 选填：分账手续费信息

#### 补贴信息
- `combinedpay_data` String 选填：补贴支付信息（jsonArray字符串）
- `combinedpay_data_fee_info` String 选填：补贴支付手续费信息（jsonObject字符串）
- `trans_fee_allowance_info` String 选填：手续费补贴信息（Json格式）

#### 支付渠道信息
- `debit_type` String(1) 选填：借贷记标识，`D`借记卡/`C`信用卡/`Z`借贷合一卡/`O`其他
- `wx_user_id` String(128) 选填：微信用户唯一标识码
- `wx_response` String 选填：微信返回的响应报文
- `alipay_response` String 选填：支付宝返回的响应报文
- `unionpay_response` String(6000) 选填：银联返回的响应报文（JsonObject格式）
- `dc_response` String(6000) 选填：数字货币返回的响应报文（Json to String）

#### 其他信息
- `remark` String(255) 选填：备注，原样返回
- `device_type` String(2) 选填：终端类型
  - `01`：智能POS
  - `02`：扫码POS
  - `03`：云音箱
  - `04`：台牌
  - `05`：云打印
  - `06`：扫脸设备
  - `07`：收银机
  - `08`：收银助手
  - `09`：传统POS
  - `10`：一体音箱
  - `11`：虚拟终端
- `mer_dev_location` String(128) 选填：商户终端定位信息（jsonObject字符串）
- `mer_priv` String(1500) 选填：商户私有域
- `auth_no` String(6) 选填：授权号（同一商户当天，同一终端，同一批次号唯一）
- `password_trade` String(1) 选填：输入密码提示，`Y`等待用户输入密码状态
- `mer_name` String(100) 选填：商户名称
- `shop_name` String(100) 选填：店铺名称
- `fq_channels` String(20) 选填：信用卡分期资产方式
- `bank_desc` String(200) 选填：外部通道返回描述
- `atu_sub_mer_id` String(32) 选填：ATU真实商户号
- `fund_freeze_stat` String(16) 选填：资金冻结状态，`FREEZE`冻结/`UNFREEZE`解冻
- `unfreeze_amt` String(14) 选填：解冻金额，单位元

#### 预授权相关（仅预授权交易）
- `pre_auth_amt` String(14) 选填：预授权金额，单位元
- `pre_auth_pay_amt` String(14) 选填：预授权完成金额，单位元
- `org_auth_no` String(6) 选填：原授权号
- `pre_auth_hf_seq_id` String(128) 选填：预授权汇付全局流水号
- `pre_auth_pay_fee_amount` String(14) 选填：预授权完成手续费，单位元
- `pre_auth_pay_refund_fee_amount` String(14) 选填：预授权完成退还手续费，单位元
- `org_fee_flag` String(8) 选填：原手续费扣款标志，`INNER`内扣/`OUTSIDE`外扣
- `org_fee_rec_type` String(1) 选填：原手续费扣取方式，`1`实收/`2`后收
- `org_allowance_type` String(1) 选填：原补贴类型，`0`不补贴/`1`补贴/`2`部分补贴

## 返回示例

```json
{
    "data": {
        "org_req_date": "20210923",
        "resp_desc": "成功",
        "trans_stat": "S",
        "bank_desc": "TRADE_SUCCESS",
        "org_hf_seq_id": "00290TOP1GR210923152444P297ac13262200000",
        "end_time": "20210923152445",
        "trans_amt": "0.02",
        "trans_time": "152446",
        "fee_type": "INNER",
        "div_flag": "Y",
        "alipay_response": "{\"buyer_id\":\"2088802149333153\",\"buyer_logon_id\":\"109***%40qq.com\"}",
        "delay_acct_flag": "Y",
        "org_req_seq_id": "202109237745559",
        "out_trans_id": "262021092322001433151443427606",
        "party_order_id": "03242109235548445903889",
        "bagent_id": "6666000020720949",
        "resp_code": "00000000",
        "debit_type": "O",
        "settlement_amt": "0.02",
        "huifu_id": "6666000018328947",
        "acct_date": "20210923",
        "fee_amt": "0.00",
        "trans_type": "A_MICROPAY"
    },
    "sign": "QlUPRVo9cOY4Smh5mQPTtQLyFdV9DPNHYPR9Pr2qGzTm+Vzcgigp67yjBw+oMT+F..."
}
```

## 业务返回码

- `10000000`：请求内容体不能为空
- `10000000`：%s不能为空（%s代指报错参数名）
- `10000000`：%s长度固定%d位（%s代指报错参数名、%d代指字段长度）
- `10000000`：%s最大长度为%d位（%s代指报错参数名、%d代指字段长度）
- `10000000`：%s的传入枚举[%s]不存在（%s代指报错参数名）
- `10000000`：%s不符合%s格式（%s代指报错参数名）
- `21000000`：原机构请求流水号、交易返回的全局流水号、用户账单上的商户订单号、用户账单上的交易订单号、外部订单号、终端订单号不能同时为空
- `22000000`：产品号不存在
- `22000000`：产品状态异常
- `23000001`：交易不存在
- `91111119`：通道异常，请稍后重试
- `98888888`：系统错误

## PHP 使用示例

```php
use cccdl\DougongPay\Payment\Payment;

// 创建支付实例
$payment = new Payment($dougongConfig);

// 通过请求流水号查询
$result = $payment->query([
    'huifu_id' => '6666000000000000',
    'org_req_seq_id' => '2024040522182635****',
    'org_req_date' => '20240405'
]);

// 通过全局流水号查询
$result = $payment->query([
    'huifu_id' => '6666000000000000',
    'org_hf_seq_id' => '00290TOP1GR210919004230P853ac132622*****'
]);

// 通过服务订单号查询
$result = $payment->query([
    'huifu_id' => '6666000000000000',
    'out_ord_id' => '1234323JKHDFE1243252'
]);

// 检查查询结果
if ($result['data']['resp_code'] === '00000000') {
    $transStatus = $result['data']['trans_stat'];
    switch ($transStatus) {
        case 'S':
            echo "交易成功";
            break;
        case 'P':
            echo "交易处理中";
            break;
        case 'F':
            echo "交易失败";
            break;
        case 'I':
            echo "交易初始状态，请联系技术支持";
            break;
    }
} else {
    echo "查询失败：" . $result['data']['resp_desc'];
}
```

## 注意事项

1. **查询条件**：`out_ord_id`、`org_hf_seq_id`、`org_req_seq_id` 三个参数必填其一
2. **交易状态判断**：以 `trans_stat` 字段为准，不要依赖 `resp_code`
3. **初始状态处理**：返回 `trans_stat=I` 时请联系客服确认订单问题
4. **账务状态**：返回 `acct_stat=I` 时请联系客服确认订单问题
5. **参数校验**：查询接口相对宽松，但建议仍进行基础参数校验
6. **幂等性**：查询接口天然幂等，可重复调用
7. **响应验签**：建议对返回结果进行签名验证确保数据完整性