<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Payment;

use cccdl\DougongPay\Core\BaseCore;
use cccdl\DougongPay\Traits\Request;
use cccdl\DougongPay\Tools\SignTool;
use cccdl\DougongPay\Tools\PaymentValidator;
use cccdl\DougongPay\Exception\DougongException;

class Payment extends BaseCore
{
    use Request;

    private string $url;
    private array $params;
    private array $header;

    /**
     * 聚合正扫支付接口
     *
     * 支持多种支付方式的统一下单接口，通过trade_type区分不同支付类型。
     * 调用斗拱支付 v3 版本聚合正扫接口：/v3/trade/payment/jspay
     *
     * 应用场景：
     * - 场景1-台牌码：用户扫描聚合静态二维码，输入订单金额完成支付
     * - 场景2-公众号/小程序商城：用户在公众号/小程序下单后输入密码完成支付
     * - 场景3-商户选择支付方式后，调用该接口生成二维码，用户使用对应App扫一扫完成支付
     *
     * 支持的支付类型：
     * - A_NATIVE：支付宝原生支付（扫码支付，返回qr_code。适用于商户APP通过扫码完成支付）
     * - A_JSAPI：支付宝JS支付（返回pay_info。需要支付宝小程序，适用于APP跳转支付宝完成支付）
     * - T_MINIAPP：微信小程序支付（返回pay_info用于wx.requestPayment）
     * - T_JSAPI：微信公众号支付（返回pay_info用于微信JS API）
     * - U_NATIVE：银联二维码正扫（返回qr_code）
     * - U_JSAPI：银联二维码JS支付（返回pay_info）
     * - D_NATIVE：数字人民币正扫（返回qr_code）
     * - T_H5：微信直连H5支付
     * - T_APP：微信APP支付
     * - T_NATIVE：微信正扫（返回qr_code）
     *
     * 使用示例：
     * ```php
     * // 支付宝原生支付
     * $result = $payment->create([
     *     'trade_type' => 'A_NATIVE',
     *     'req_seq_id' => 'ORDER_' . date('YmdHis') . rand(1000, 9999),
     *     'huifu_id' => '6666000000000000',
     *     'goods_desc' => '测试商品',
     *     'trans_amt' => '0.01',
     *     'notify_url' => 'https://your-domain.com/notify'
     * ]);
     *
     * // 微信小程序支付
     * $result = $payment->create([
     *     'trade_type' => 'T_MINIAPP',
     *     'req_seq_id' => 'ORDER_' . date('YmdHis') . rand(1000, 9999),
     *     'huifu_id' => '6666000000000000',
     *     'goods_desc' => '测试商品',
     *     'trans_amt' => '0.01',
     *     'wx_data' => json_encode([
     *         'sub_appid' => 'wx1234567890abcdef',
     *         'sub_openid' => 'oABCD1234567890'
     *     ])
     * ]);
     * ```
     *
     * @param array $data 支付参数
     *                    必填参数：
     *                    - trade_type: 交易类型（见上述支持类型）
     *                    - req_seq_id: 请求流水号（商户唯一，建议格式：前缀+时间戳+随机数）
     *                    - huifu_id: 商户号（渠道与一级代理商的直属商户ID）
     *                    - goods_desc: 商品描述（最长127字符）
     *                    - trans_amt: 交易金额（元，保留2位小数，最低0.01）
     *
     *                    可选参数：
     *                    - acct_id: 收款账户号（基本户/现金户，不填默认基本户）
     *                    - time_expire: 交易有效期（yyyyMMddHHmmss，微信/支付宝默认2小时关单）
     *                    - notify_url: 异步通知地址（HTTP/HTTPS）
     *                    - wx_data: 微信参数集合（JSON字符串，如sub_appid、sub_openid等）
     *                    - alipay_data: 支付宝参数集合（JSON字符串）
     *                    - unionpay_data: 银联参数集合（JSON字符串）
     *                    - dc_data: 数字人民币参数集合（JSON字符串）
     *                    - delay_acct_flag: 是否延迟交易（Y延迟/N实时，默认N）
     *                    - fee_flag: 手续费扣款标志（1外扣/2内扣，默认取控台配置）
     *                    - acct_split_bunch: 分账对象（JSON字符串）
     *                    - limit_pay_type: 禁用支付方式（如NO_CREDIT禁用信用卡）
     *                    - channel_no: 渠道号（自有渠道请联系运维获取）
     *                    - pay_scene: 场景类型（需与channel_no配合使用）
     *                    - remark: 备注（最长255字符，原样返回）
     *
     * @return array 支付结果
     *               同步返回参数：
     *               - resp_code: 业务返回码（00000000受理成功，00000100下单成功）
     *               - resp_desc: 业务返回描述
     *               - trans_stat: 交易状态（P处理中/S成功/F失败，以此字段为准）
     *               - req_date: 请求日期（原样返回）
     *               - req_seq_id: 请求流水号（原样返回）
     *               - hf_seq_id: 全局流水号（可用于后续查询）
     *               - trade_type: 交易类型
     *               - trans_amt: 交易金额
     *               - huifu_id: 商户号
     *               - pay_info: JSAPI支付信息（适用于公众号/小程序支付，JSON字符串）
     *               - qr_code: 二维码链接（适用于NATIVE支付）
     *               - bank_message: 通道返回描述
     *               - delay_acct_flag: 延时标记
     *               - remark: 备注（原样返回）
     *               - alipay_response: 支付宝响应报文（JSON字符串）
     *               - wx_response: 微信响应报文（JSON字符串）
     *               - unionpay_response: 银联响应报文（JSON字符串）
     *
     *               业务返回码：
     *               - 00000000: 交易受理成功（注：交易状态以trans_stat为准）
     *               - 00000100: 下单成功
     *               - 10000000: 产品号不能为空
     *               - 10000000: 交易类型不能为空
     *               - 10000000: %s不能为空（%s代指报错参数名）
     *               - 10000000: %s长度固定%d位（%s代指报错参数名、%d代指字段长度）
     *               - 10000000: %s最大长度为%d位（%s代指报错参数名、%d代指字段长度）
     *               - 10000000: %s的传入枚举[%s]不存在（%s代指报错参数名）
     *               - 10000000: %s不符合%s格式（%s代指报错参数名）。如：交易金额不符合金额格式
     *               - 10000000: 订单已超时
     *               - 20000000: 重复交易
     *               - 21000000: 手续费金额、手续费收取方式、手续费扣款标识、手续费子客户号、手续费账户号，必须同时为空或同时必填
     *               - 22000000: 产品号不存在
     *               - 22000000: 产品号状态异常
     *               - 22000002: 商户信息不存在
     *               - 22000002: 商户状态异常
     *               - 22000003: 延迟账户不存在
     *               - 22000003: 商户账户信息不存在
     *               - 22000004: 暂未开通分账权限
     *               - 22000004: 暂未开通%s权限（%s代指报错参数名）
     *               - 22000004: 暂未开通延迟入账权限
     *               - 22000005: 手续费承担方必须参与分账
     *               - 22000005: 分账列表必须包含主交易账户
     *               - 22000005: 其他商户分账比例过高
     *               - 22000005: 商户入驻信息配置有误(多通道)
     *               - 22000005: 商户分期贴息未激活
     *               - 22000005: 分期交易不能重复激活
     *               - 22000005: 手续费配置有误
     *               - 22000005: 商户贴息信息未配置
     *               - 22000005: 花呗分期费率配置有误
     *               - 22000005: 分账配置有误
     *               - 22000005: 分账配置未包含手续费承担方
     *               - 22000005: 商户入驻配置信息有误
     *               - 22000005: 商户支付宝/微信入驻信息配置有误
     *               - 22000005: 商户银联入驻信息配置有误
     *               - 22000005: 商户贴息分期费率未配置渠道号
     *               - 22000005: 商户贴息分期费率未配置费率类型
     *               - 22000005: 商户贴息分期费率配置有误
     *               - 22000005: 手续费费率未配置
     *               - 22000005: 手续费计算错误
     *               - 22000005: 商户贴息信息配置有误
     *               - 22000005: 商户未报名活动或活动已过期
     *               - 22000005: 数字货币手续费费率未配置
     *               - 22000005: 数字货币手续费配置有误
     *               - 22000005: 商户未配置默认入驻信息（多通道）
     *               - 23000003: 交易金额不足以支付内扣手续费
     *               - 23000003: 优惠金额大于交易金额
     *               - 23000004: 交易类型不支持
     *               - 23000004: 当前交易类型不支持商户贴息
     *               - 90000000: 业务执行失败；如：账户可用余额不足
     *               - 90000000: 该功能已关闭，请联系客服
     *               - 90000000: 交易失败，单日金额超限，请联系客服提额
     *               - 90000000: 交易存在风险
     *               - 91111119: 通道异常，请稍后重试
     *               - 98888888: 系统错误
     *
     * @throws DougongException 当trade_type参数缺失时抛出异常
     *
     * @see https://api.huifu.com/v3/trade/payment/jspay 官方接口文档
     * @since 1.0.0
     */
    public function create(array $data): array
    {
        $this->validateRequiredParams($data);
        $this->validateParamFormats($data);

        return $this->createPayment($data);
    }

    private function createPayment(array $data): array
    {
        $this->url = $this->dougongConfig->baseUri . '/v3/trade/payment/jspay';

        $postData = [
            'req_date' => date('Ymd'),
            'req_seq_id' => $data['req_seq_id'],
            'huifu_id' => $data['huifu_id'],
            'trade_type' => $data['trade_type'],
            'goods_desc' => $data['goods_desc'],
            'trans_amt' => $data['trans_amt'],
        ];

        // 批量设置可选参数
        $postData = $this->setOptionalParams($data, $postData);

        $signTool = new SignTool($this->dougongConfig);

        $this->params = [
            'sys_id' => $this->dougongConfig->sysId,
            'product_id' => $this->dougongConfig->productId,
            'data' => $postData,
            'sign' => $signTool->sign($postData),
        ];

        return $this->postRequest();
    }

    /**
     * 验证必填参数
     *
     * @param array $data 请求参数
     * @throws DougongException 当必填参数缺失或格式错误时抛出异常
     */
    private function validateRequiredParams(array $data): void
    {
        $requiredFields = [
            'req_seq_id' => '请求流水号',
            'huifu_id' => '商户号',
            'trade_type' => '交易类型',
            'goods_desc' => '商品描述',
            'trans_amt' => '交易金额'
        ];

        PaymentValidator::validateRequiredFields($data, $requiredFields);
    }

    /**
     * 验证参数格式
     *
     * @param array $data 请求参数
     * @throws DougongException 当参数格式错误时抛出异常
     */
    private function validateParamFormats(array $data): void
    {
        // 验证交易类型
        PaymentValidator::validateTradeType($data['trade_type']);

        // 验证交易金额格式
        PaymentValidator::validateTransAmount($data['trans_amt']);

        // 验证商户号格式
        PaymentValidator::validateHuifuId($data['huifu_id']);

        // 验证请求流水号长度
        PaymentValidator::validateStringLength($data['req_seq_id'], 128, 'req_seq_id');

        // 验证商品描述长度
        PaymentValidator::validateStringLength($data['goods_desc'], 127, 'goods_desc');

        // 验证时间格式（如果提供）
        if (isset($data['time_expire'])) {
            PaymentValidator::validateDateFormat($data['time_expire'], 'datetime', 'time_expire');
        }

        // 验证延迟交易标志
        if (isset($data['delay_acct_flag'])) {
            PaymentValidator::validateEnum($data['delay_acct_flag'], ['Y', 'N'], 'delay_acct_flag');
        }

        // 验证手续费扣款标志
        if (isset($data['fee_flag'])) {
            PaymentValidator::validateEnum((string)$data['fee_flag'], ['1', '2'], 'fee_flag');
        }
    }

    /**
     * 批量设置可选参数
     *
     * @param array $data 原始请求参数
     * @param array $postData 目标数组
     * @return array 设置后的数组
     */
    private function setOptionalParams(array $data, array $postData): array
    {
        $optionalParams = [
            'acct_id', 'time_expire', 'notify_url', 'wx_data', 'alipay_data',
            'unionpay_data', 'dc_data', 'delay_acct_flag', 'fee_flag',
            'acct_split_bunch', 'term_div_coupon_type', 'combinedpay_data',
            'combinedpay_data_fee_info', 'limit_pay_type', 'fq_mer_discount_flag',
            'channel_no', 'pay_scene', 'remark', 'risk_check_data',
            'terminal_device_data', 'trans_fee_allowance_info'
        ];

        foreach ($optionalParams as $param) {
            if (isset($data[$param])) {
                $postData[$param] = $data[$param];
            }
        }

        return $postData;
    }
}