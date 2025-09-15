# 聚合正扫支付接口

POST https://api.huifu.com/v3/trade/payment/jspay

最近更新时间：2025.09.12 作者：魏溪

## 应用场景

- **场景1-台牌码**：用户扫描聚合静态二维码，输入订单金额完成支付
- **场景2-公众号/小程序商城**：用户在公众号/小程序下单后输入密码完成支付
- **场景3-商户选择支付方式后**：调用该接口生成支付宝/银联/数字人民币二维码，用户使用对应App扫一扫完成支付

## 支持的支付类型

- **微信公众号**：`T_JSAPI`
- **微信小程序**：`T_MINIAPP`
- **支付宝JS**：`A_JSAPI`
- **支付宝正扫**：`A_NATIVE`
- **银联二维码正扫**：`U_NATIVE`
- **银联二维码JS**：`U_JSAPI`
- **数字人民币正扫**：`D_NATIVE`
- **微信直连H5**：`T_H5`
- **微信APP**：`T_APP`
- **微信正扫**：`T_NATIVE`

## 适用对象

已开通微信/支付宝/银联二维码/数字人民币权限的商户。

## 接口说明

- **数据格式**：JSON
- **加签验签**：参考"接入指引-开发指南"

## 公共请求参数

- `sys_id` String(32) 必填：渠道商/商户的huifu_id
  - 主体为渠道商：填写渠道商huifu_id
  - 主体为直连商户：填写商户huifu_id
  - 示例：`6666000123120000`
- `product_id` String(32) 必填：汇付分配的产品号（例：`MCS`）
- `sign` String(512) 必填：加签结果（见加签验签说明）
- `data` Json 必填：业务请求参数（见下）

## 业务请求参数（data）

### 必填参数

- `req_date` String(8)：请求日期，格式 `yyyyMMdd`
- `req_seq_id` String(128)：请求流水号
- `huifu_id` String(32)：商户号（渠道与一级代理商的直属商户ID）
- `goods_desc` String(127)：商品描述
- `trade_type` String(16)：交易类型，取值见"支持类型"
- `trans_amt` String(14)：交易金额，单位元，保留2位小数，最低0.01

### 可选参数

- `acct_id` String(9)：收款账户号（基本户/现金户，不填默认基本户）
- `time_expire` String(14)：交易有效期，`yyyyMMddHHmmss`。微信/支付宝默认2小时关单
- `wx_data` String：微信参数集合，jsonObject字符串
- `alipay_data` String：支付宝参数集合，jsonObject字符串
- `unionpay_data` String：银联参数集合，jsonObject字符串
- `dc_data` String：数字人民币参数集合，jsonObject字符串
- `delay_acct_flag` String(1)：是否延迟交易，`Y`/`N`（默认`N`）
- `fee_flag` Integer(1)：手续费扣款标志，`1`外扣，`2`内扣（默认取控台配置）
- `acct_split_bunch` String：分账对象，jsonObject字符串
- `term_div_coupon_type` Integer(1)：分账遇到优惠处理规则，`1`按比例分，`2`顺序保障，`3`仅交易商户（默认）
- `combinedpay_data` String：补贴支付信息，jsonArray字符串
- `combinedpay_data_fee_info` String：补贴支付手续费承担方信息，jsonObject字符串
- `limit_pay_type` String(128)：禁用支付方式（见"参数说明"），例：`NO_CREDIT`
- `fq_mer_discount_flag` String(1)：商户贴息标记，`Y`全额，`P`部分，默认非贴息
- `channel_no` String(32)：渠道号（自有渠道请联系运维获取）
- `pay_scene` String(2)：场景类型，需与`channel_no`配合（为空取默认配置）
- `remark` String(255)：备注，原样返回
- `risk_check_data` String：安全信息，jsonObject字符串
- `terminal_device_data` String：设备信息，jsonObject字符串
- `notify_url` String(504)：异步通知地址（http/https）
- `trans_fee_allowance_info` String：手续费补贴信息，jsonObject字符串

## 请求示例

```json
{
  "sys_id": "6666000108840829",
  "product_id": "YYZY",
  "data": {
    "pay_scene": "02",
    "time_expire": "20250518235959",
    "limit_pay_type": "NO_CREDIT",
    "trans_amt": "0.10",
    "goods_desc": "测试商品",
    "notify_url": "http://www.baidu.com",
    "delay_acct_flag": "N",
    "req_seq_id": "20240621101119466q67qrxlv95wyrt",
    "req_date": "20240621",
    "trade_type": "A_NATIVE",
    "huifu_id": "6666000000000001"
  },
  "sign": "Y/D3Gjqk97C2pSlvZpoY..."
}
```

## 同步返回参数

### 公共返回

- `sign` String(512) 必填：返回值签名
- `data` Json 必填：业务返回参数

### data字段

- `resp_code` String(8) 必填：业务返回码
- `resp_desc` String(256) 必填：业务返回描述
- `req_date` String(8) 必填：请求日期（原样返回）
- `req_seq_id` String(128) 必填：请求流水号（原样返回）
- `hf_seq_id` String(128) 选填：全局流水号
- `trade_type` String(16) 选填：交易类型，取值同上
- `trans_amt` String(14) 选填：交易金额，单位元
- `trans_stat` String(1) 选填：交易状态：`P`处理中、`S`成功、`F`失败（以此为准）
- `huifu_id` String(32) 必填：商户号
- `bank_message` String(200) 选填：通道返回描述
- `delay_acct_flag` String(1) 选填：延时标记，`Y`/`N`（默认）
- `pay_info` String(1024) 选填：JSAPI支付返回信息
- `qr_code` String(1024) 选填：NATIVE支付返回二维码链接
- `alipay_response` String 选填：支付宝响应报文（jsonObject字符串）
- `wx_response` String 选填：微信响应报文（jsonObject字符串）
- `unionpay_response` String 选填：银联响应报文（jsonObject字符串）
- `remark` String(255) 选填：备注原样返回
- `acct_id` String(9) 选填：商户账户号
- `device_type` String(2) 选填：终端类型
- `party_order_id` String(64) 选填：用户账单上的商户订单号
- `atu_sub_mer_id` String(32) 选填：ATU真实商户号
- `unconfirm_amt` String(14) 选填：待确认金额（元）
- `combinedpay_data` String 选填：补贴支付信息，jsonArray字符串
- `combinedpay_data_fee_info` String 选填：补贴支付手续费承担方信息，jsonObject字符串

## 返回示例

```json
{
  "data": {
    "bank_message": "成功[0000000]",
    "pay_info": "{\"appId\":\"wx3a9f24097a0ab09c\",...}",
    "resp_desc": "交易正在处理中 cashCode:000 cashDesc:成功",
    "trans_stat": "P",
    "hf_seq_id": "00290TOP1GR210919003919P938ac13262200000",
    "remark": "String",
    "trans_amt": "0.50",
    "req_seq_id": "20210919561166000",
    "req_date": "20210919",
    "resp_code": "00000100",
    "trade_type": "U_JSAPI",
    "huifu_id": "6666000018328947"
  },
  "sign": "fy2NgWhzpKQe...moQ=="
}
```

## 异步返回参数

异步报文有"间联模式/直联模式"（商户是否做了微信直连配置 `wx_zl_conf`）。

### 公共返回参数

- `resp_code` String(8) 必填：网关返回码（例：`00000000`）
- `resp_desc` String(512) 必填：网关返回信息（例：交易成功[000]）
- `sign` String(512) 必填：返回值签名
- `resp_data` Json 必填：返回业务数据（jsonObject）

### resp_data字段

- `resp_code` String(8) 必填：业务返回码
- `resp_desc` String(256) 必填：业务返回描述
- `huifu_id` String(32) 必填：商户号
- `req_seq_id` String(128) 必填：请求流水号（原样返回）
- `req_date` String(8) 必填：请求日期（原样返回）
- `trans_type` String(16) 选填：交易类型，取值同上
- `hf_seq_id` String(128) 选填：全局流水号
- `out_trans_id` String(64) 选填：用户账单上的交易订单号
- `party_order_id` String(64) 选填：用户账单上的商户订单号
- `trans_amt` String(14) 选填：交易金额（元）
- `pay_amt` String(14) 选填：消费者实付金额（元）
- `settlement_amt` String(16) 选填：结算金额（元）
- `end_time` String(14) 选填：支付完成时间 `yyyyMMddHHmmss`
- `acct_date` String(8) 选填：入账时间 `yyyyMMdd`
- `trans_stat` String(1) 选填：`S`成功、`F`失败（以此为准）
- `fee_flag` Integer(1) 选填：`1`外扣、`2`内扣
- `fee_formula_infos` Array 选填：手续费费率信息（成功时返回）
- `fee_amount` String(16) 选填：手续费金额（元）
- `trans_fee_allowance_info` Object 选填：手续费补贴信息
- `combinedpay_data` String 选填：补贴支付信息（jsonArray）
- `combinedpay_data_fee_info` String 选填：补贴支付手续费信息（jsonObject）
- `debit_type` String(1) 选填：`D`借记卡、`C`贷记卡、`0`其他
- `is_div` String(1) 必填：是否分账交易，`1`分账，`0`非分账
- `acct_split_bunch` Object 选填：分账对象
- `is_delay_acct` String(1) 必填：是否延时交易，`1`延迟，`0`非延迟
- `wx_user_id` String(128) 选填：微信用户唯一标识码
- `wx_response` Object 选填：微信响应报文
- `alipay_response` Object 选填：支付宝响应报文
- `dc_response` Object 选填：数字货币响应报文
- `unionpay_response` Object 选填：银联响应报文
- `device_type` String(2) 选填：终端类型
- `mer_dev_location` Object 选填：商户终端定位信息
- `bank_message` String(200) 选填：通道返回描述
- `remark` String(255) 选填：备注原样返回
- `fq_channels` String(20) 选填：分期资产方式（例：`alipayfq_cc`）
- `notify_type` Integer(1) 选填：`1`通道通知，`2`账务通知
- `split_fee_info` Object 选填：分账手续费信息
- `atu_sub_mer_id` String(32) 选填：ATU真实商户号
- `devs_id` String(32) 选填：汇付终端号（使用汇付机具交易时返回）
- `fund_freeze_stat` String(16) 选填：资金冻结状态，`FREEZE`/`UNFREEZE`

## Webhook说明

交易完成后除异步消息外，亦支持发送webhook事件，可配置多个接收端驱动业务流程（如财务入账、物流发货等）。

## 参数说明

### limit_pay_type取值

- `NO_CREDIT`：禁用信用卡（微信/支付宝），注意花呗支付时不能禁用信用卡
- `BALANCE`：禁用支付宝余额
- `MONEY_FUND`：禁用支付宝余额宝
- `BANK_PAY`：禁用网银（支付宝）
- `DEBIT_CARD_EXPRESS`：禁用借记卡快捷（支付宝）
- `CREDIT_CARD_EXPRESS`：禁用信用卡快捷（支付宝）
- `CREDIT_CARD_CARTOON`：禁用信用卡卡通（支付宝）
- `CARTOON`：禁用卡通（支付宝）
- `PCREDIT`：禁用支付宝花呗
- `PCREDIT_PAY_INSTALLMENT`：禁用支付宝花呗分期
- `CREDIT_GROUP`：禁用支付宝信用支付类型（卡通、快捷、花呗、花呗分期）
- `COUPON`：禁用支付宝红包
- `POINT`：禁用支付宝积分
- `PROMOTION`：禁用支付宝优惠（实时优惠+商户优惠）
- `VOUCHER`：禁用支付宝营销券
- `MDISCOUNT`：禁用支付宝商户优惠
- `HONEY_PAY`：禁用支付宝亲密付
- `MCARD`：禁用支付宝商户预存卡
- `PCARD`：禁用支付宝个人预存卡

## 业务返回码

- `00000000`：交易受理成功（注：交易状态以trans_stat为准）
- `00000100`：下单成功
- `10000000`：产品号不能为空
- `10000000`：交易类型不能为空
- `10000000`：%s不能为空（%s代指报错参数名）
- `10000000`：%s长度固定%d位（%s代指报错参数名、%d代指字段长度）
- `10000000`：%s最大长度为%d位（%s代指报错参数名、%d代指字段长度）
- `10000000`：%s的传入枚举[%s]不存在（%s代指报错参数名）
- `10000000`：%s不符合%s格式（%s代指报错参数名）。如：交易金额不符合金额格式
- `10000000`：订单已超时
- `20000000`：重复交易
- `21000000`：手续费金额、手续费收取方式、手续费扣款标识、手续费子客户号、手续费账户号，必须同时为空或同时必填
- `22000000`：产品号不存在
- `22000000`：产品号状态异常
- `22000002`：商户信息不存在
- `22000002`：商户状态异常
- `22000003`：延迟账户不存在
- `22000003`：商户账户信息不存在
- `22000004`：暂未开通分账权限
- `22000004`：暂未开通%s权限（%s代指报错参数名）
- `22000004`：暂未开通延迟入账权限
- `22000005`：手续费承担方必须参与分账
- `22000005`：分账列表必须包含主交易账户
- `22000005`：其他商户分账比例过高
- `22000005`：商户入驻信息配置有误（多通道）
- `22000005`：商户分期贴息未激活或分期交易不能重复激活
- `22000005`：手续费配置有误或商户贴息信息未配置
- `22000005`：花呗分期费率配置有误或分账配置有误
- `22000005`：分账配置未包含手续费承担方
- `22000005`：商户入驻配置信息有误或支付宝/微信/银联入驻信息配置有误
- `22000005`：商户贴息分期费率未配置渠道号或费率类型
- `22000005`：商户贴息分期费率配置有误或手续费费率未配置
- `22000005`：手续费计算错误或商户贴息信息配置有误
- `22000005`：商户未报名活动或活动已过期
- `22000005`：数字货币手续费费率未配置或配置有误
- `22000005`：商户未配置默认入驻信息（多通道）
- `23000003`：交易金额不足以支付内扣手续费
- `23000003`：优惠金额大于交易金额
- `23000004`：交易类型不支持
- `23000004`：当前交易类型不支持商户贴息
- `90000000`：业务执行失败；如：账户可用余额不足
- `90000000`：该功能已关闭，请联系客服
- `90000000`：交易失败，单日金额超限，请联系客服提额
- `90000000`：交易存在风险
- `91111119`：通道异常，请稍后重试
- `98888888`：系统错误