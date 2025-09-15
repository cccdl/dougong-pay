# 斗拱支付 - 官方协议文档导航

> ⚠️ 本文档已重新组织为模块化结构，请参考以下专门文档

## 文档结构

### 📋 基础协议规则
**文件**: `base-protocol.md`
- 协议规则 (HTTPS、POST、JSON、UTF-8)
- 请求/响应体模型
- 标准字段及返回码
- 接入步骤
- 本库实现映射

### 🔐 签名验签
**文件**: `sign-verify.md`
- 认证与安全
- 证书生成
- 联调公私钥参数获取
- v2版接口加签验签
- 接口加密解密说明

### 📨 异步消息
**文件**: `async-notification.md`
- 异步消息简介
- HTTP(S) 异步通知使用说明
- 返回报文示例
- PHP 处理示例
- 注意事项

### 💳 聚合正扫接口
**文件**: `aggregate-scan-interface.md`
- 应用场景
- 支持的支付类型
- 接口参数详解
- 请求/响应示例
- 业务返回码
- 支付产品场景映射

### 📱 微信APP支付
**文件**: `wechat-app-payment.md`
- 产品介绍
- 接入前准备
- 开发指引
- 系统调用流程
- 常见问题 FAQ

### 💰 支付宝支付
**文件**: `alipay-payment.md`
- 产品介绍
- 接入前准备
- 开发指引
- 异步通知处理
- 参数说明

## 快速查找

| 需求 | 文档 |
|------|------|
| 了解基本协议规则 | `base-protocol.md` |
| 实现签名验签 | `sign-verify.md` |
| 处理异步通知 | `async-notification.md` |
| 调用支付接口 | `aggregate-scan-interface.md` |
| 微信支付集成 | `wechat-app-payment.md` |
| 支付宝支付集成 | `alipay-payment.md` |

## 历史说明

原 `OFFICIAL_PROTOCOL.md` 文档因内容过于庞大 (1022行)，已拆分为上述专门文档，便于查找和维护。所有技术内容已完整迁移至新文档结构中。