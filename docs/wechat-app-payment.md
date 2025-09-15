# 微信APP支付

最近更新时间：2024.9.1 作者：小树

## 1. 产品介绍

### 1.1 简介
为手机、平板、POS等智能终端的APP提供支付服务接口，覆盖收款、查询、结算、对账等完整流程。支持在主流应用市场完成认证的移动端应用接入支付功能，目前支持iOS与Android。

### 1.2 应用场景
当客户拥有自有APP，为了在APP内达成交易闭环，需要接入斗拱的SaaS支付服务接口。

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
  - **微信支付**：提供商户或服务商主体的小程序AppID（用于小程序拉起与支付）

### 2.2 对接准备
1) **密钥获取**：参见"联调公私钥参数获取"

2) **公共参数获取**：登录服务商/商户控制台，在"开发设置-开发者信息"获取 `sys_id`、`product_id`

3) **业务开通与配置**：
- **服务商模式**：
  - 步骤一：服务商功能与权限开通；汇付审核资料并开通支付与费率配置
  - 步骤二：为商户开通相应功能与权限；可在服务商控台配置或调用"商户业务开通"接口
- **直签商户**：与客户经理确认功能、费率与AppID配置已完成

## 3. 开发指引

### 3.1 对接规范
- 接口均为POST，数据格式JSON；SDK示例参考Java；加签验签遵循"v2版接口加签验签"

### 3.2 确认业务配置
- **微信侧**：
  - 商户在微信公众平台拥有账号且有小程序，开发阶段可用体验版本，建议先上线并通过审核
- **斗拱侧**：
  - 商户微信小程序业务开通、AppID/费率配置完成；可在控台或通过"微信配置查询"接口确认
  - 商户微信实名认证完成且已授权；可在控台或通过"微信实名认证状态查询"接口确认

### 3.3 系统调用流程

#### 3.3.1 交易流程

**1) APP跳转微信小程序：**
- 下载微信SDK并按微信官方说明接入
- 配置准备：
  - 微信开放平台有已通过审核的移动应用
  - 微信公众平台有小程序（建议发布体验版）
  - 在微信开放平台建立移动应用与小程序的关联

iOS示例（微信建议应用启动时调用）：
```objc
[WXApi registerApp:@"wx_app_id"]; // wx_app_id 为移动应用的 AppID

// 跳转小程序
WXLaunchMiniProgramReq *launchMiniProgramReq = [WXLaunchMiniProgramReq object];
launchMiniProgramReq.userName = @"gh_4fxxxxxx";  // 小程序原始ID
launchMiniProgramReq.path = @"pages/index/index?query='test'"; // 可带参路径
launchMiniProgramReq.miniProgramType = WXMiniProgramTypePreview; // 拉起类型
[WXApi sendReq:launchMiniProgramReq];
```

**2) 通过授权获取用户open_id：**
- 小程序端 `wx.login` 获取 `code`：
```javascript
wx.login({
  success (res) {
    if (res.code) {
      wx.request({ url: 'https://test.com/onLogin', data: { code: res.code } })
    } else {
      console.log('登录失败！' + res.errMsg)
    }
  }
})
```

- 商户服务端使用 `code` 换取 `open_id`：
```bash
GET https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code
```

**3) 下单请求支付信息：**
- 从APP打开的path例如 `pages/index/index`，`options.scene` 可能为1069表示从APP打开；`options.query.*` 为传参
- 服务端调用"聚合正扫"接口下单，传入 `open_id` 获取支付 `pay_info`

斗拱端调用说明：
- 使用"聚合正扫"接口拉起支付；将前端获取的 `open_id` 传入 `wx_data.sub_openid`，并传入 `wx_data.sub_appid`
- 聚合正扫需关注字段：
  - `trade_type`：交易类型。`T_MINIAPP`（微信小程序）
  - `time_expire`：交易有效期，未指定时微信默认2小时
  - `wx_data`：微信扩展参数集合（如 `sub_appid`、`sub_openid`）
  - `sub_appid`：子商户在微信申请的应用ID（子商户场景）
  - `sub_openid`：在 `sub_appid` 下用户唯一标识；公众号与小程序场景必填
  注：`wx_data` 中若填写 `sub_openid` 则必须填写 `sub_appid`

同步返回关注：
- `trans_stat`：交易状态（同步多为"处理中"，终态以异步为准）
- `resp_desc`：业务响应信息
- `bank_message`：通道返回描述
- `pay_info`：后续调用微信支付所需
- `hf_seq_id`：汇付全局流水号（可用于后续查询）

**4) 小程序端发起支付：**
使用 `pay_info` 调用 `wx.requestPayment`：
```javascript
wx.requestPayment({
  timeStamp: '',
  nonceStr: '',
  package: '',
  signType: 'MD5',
  paySign: '',
  success: function(res) {},
  fail: function(res) {},
  complete: function(res) {}
})
```

**5) 小程序返回APP：**
- 支付成功后附带结果回跳至APP：
```html
<button open-type="launchApp" app-parameter="wechat" binderror="launchAppError">打开APP</button>
```
```javascript
Page({
  launchAppError (e) { console.log(e.detail.errMsg) }
})
```
- 注意：APP需设置正确URL scheme才能从微信正确回调

#### 3.3.2 退款流程
- 退款将资金原路返回；支持多次部分退款，总额不得超过原交易金额；以异步结果为最终结果；特殊场景注意退款时效
- 关键字段：
  - `org_req_date`：原交易请求日期（Y）
  - `org_req_seq_id`：原交易请求流水号（C）
  - `org_hf_seq_id`：原交易全局流水号（C）
  - `org_party_order_id`：原交易微信/支付宝商户单号（C）
  - `ord_amt`：申请退款金额（Y）
  注：三项流水号三选一；退款金额不得大于交易金额

#### 3.3.3 对账流程
- 控台下载或接口获取对账文件（结算/分账/出金，日/月交易数据等）

#### 3.3.4 异步通知
- 参见"异步消息"；注意实现幂等与非终态反查

## 4. API列表

- **聚合正扫**：商户服务端传入 `pay_info` 完成最终交易
- **交易查询**：查询支付交易信息
- **交易退款**：申请退款
- **交易退款查询**：查询退款进度及结果
- **交易关单**：长时间未支付的关单处理
- **交易关单查询**：关单状态查询
- **微信用户标识查询**：辅助类接口

## 5. 常见问题（FAQ）

### 5.1 微信支付报错："sub_mch_id与sub_appid不匹配"
- **原因**：微信公众号/小程序支付时，未正确在微信侧配置对应的AppID
- **方案**：
  - 渠道商控台为商户配置（微信APPID配置）
  - 或通过接口配置：微信商户配置（链接参考官方文档）

### 5.2 微信支付报错："当前商户需补齐相关资料后，才可进行相应的支付交易，请商户联系对接的微信支付服务商"
- **原因**：商户未完成微信实名认证
- **方案**：完成微信实名认证（控台、扫码验证或调用实名认证接口）

### 5.3 微信支付返回：redirect_uri域名与后台配置不一致
- **原因**：网页授权页面未正确配置
- **方案**：登录服务商微信后台，在"设置与开发 -> 公众号设置 -> 功能设置"中配置网页授权域名

### 5.4 支付接口调用报错：`resp_desc: 数据权限认证失败`
- **原因**：商户信息校验未通过
- **方案**：检查 `product_id`、`sys_id` 与 `huifu_id` 的从属关系是否正确

### 5.5 为什么交易成功以后会收到2条异步通知？
交易异步与账务异步并存，通过 `notify_type` 区分：
- notify_type = '1'（交易异步）：
  - `trans_stat = 'F'` 时，不推送账务异步
  - `trans_stat = 'S'` 时，随后会推送账务异步
- notify_type = '2'（账务异步）：
  - `trans_stat = 'S'` 且 `acct_stat = 'S'`：交易成功-入账成功
  - `trans_stat = 'S'` 且 `acct_stat = 'F'`：交易成功-入账失败（异常需联系技术支持）

更多问题详见斗拱开发者社区：`https://service.dougong.net/t/qa`