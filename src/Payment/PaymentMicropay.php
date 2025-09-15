<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Payment;

use cccdl\DougongPay\Core\BaseCore;
use cccdl\DougongPay\Traits\Request;
use cccdl\DougongPay\Tools\SignTool;
use cccdl\DougongPay\Tools\PaymentValidator;
use cccdl\DougongPay\Exception\DougongException;

class PaymentMicropay extends BaseCore
{
    use Request;

    private string $url;
    private array $params;
    private array $header;

    /**
     * 聚合反扫支付接口
     *
     * 商家通过扫码设备，扫描用户出示的付款码完成收款（支持微信/支付宝/银联二维码/数字人民币）。
     * 支持微信反扫、支付宝反扫、银联二维码反扫、数字人民币反扫交易。
     * 调用斗拱支付聚合反扫支付接口：/v3/trade/payment/micropay
     *
     * 适用对象：
     * - 开通微信/支付宝/银联二维码/数字人民币权限的商户
     *
     * 注意事项：
     * - 微信、支付宝交易有订单超时时间，默认两小时关单
     *
     * 支持的支付类型：
     * - 微信反扫：T_MICROPAY
     * - 支付宝反扫：A_MICROPAY
     * - 银联反扫：U_MICROPAY
     * - 数字人民币反扫：D_MICROPAY
     *
     * 使用示例：
     * ```php
     * // 基础反扫支付
     * $result = $paymentMicropay->pay([
     *     'req_date' => date('Ymd'),
     *     'req_seq_id' => 'MP_' . date('YmdHis') . rand(1000, 9999),
     *     'huifu_id' => '6666000000000000',
     *     'trans_amt' => '100.00',
     *     'goods_desc' => '商品描述',
     *     'auth_code' => '288413840870151****',
     *     'risk_check_data' => json_encode([
     *         'ip_addr' => '192.168.1.1',
     *         'base_station' => '192.168.1.1',
     *         'latitude' => '39.9',
     *         'longitude' => '116.4'
     *     ])
     * ]);
     *
     * // 带分账的反扫支付
     * $result = $paymentMicropay->pay([
     *     'req_date' => date('Ymd'),
     *     'req_seq_id' => 'MP_' . date('YmdHis') . rand(1000, 9999),
     *     'huifu_id' => '6666000000000000',
     *     'trans_amt' => '100.00',
     *     'goods_desc' => '商品描述',
     *     'auth_code' => '288413840870151****',
     *     'acct_split_bunch' => json_encode([
     *         'split_mode' => '02',
     *         'split_info' => [
     *             ['huifu_id' => '6666000000000000', 'div_amt' => '80.00'],
     *             ['huifu_id' => '6666000000000001', 'div_amt' => '20.00']
     *         ]
     *     ]),
     *     'risk_check_data' => json_encode([
     *         'ip_addr' => '192.168.1.1'
     *     ])
     * ]);
     * ```
     *
     * @param array $data 支付参数
     *                    必填参数：
     *                    - req_date: 请求日期（yyyyMMdd格式）
     *                    - req_seq_id: 请求流水号（同一huifu_id下当天唯一）
     *                    - huifu_id: 商户号（16位数字）
     *                    - trans_amt: 交易金额（元，保留2位小数，最低0.01）
     *                    - goods_desc: 商品描述（最长127字符）
     *                    - auth_code: 支付授权码（扫码设备读出的条形码或二维码信息）
     *                    - risk_check_data: 安全信息（JSON字符串）
     *
     *                    可选参数：
     *                    - time_expire: 交易有效期（yyyyMMddHHmmss格式）
     *                    - fee_flag: 手续费扣款标志（1外扣/2内扣）
     *                    - limit_pay_type: 禁用支付方式
     *                    - delay_acct_flag: 是否延迟交易（Y延迟/N不延迟）
     *                    - channel_no: 渠道号
     *                    - combinedpay_data: 补贴支付信息（JSON字符串）
     *                    - combinedpay_data_fee_info: 补贴支付手续费承担方信息（JSON字符串）
     *                    - pay_scene: 场景类型
     *                    - acct_split_bunch: 分账对象（JSON字符串）
     *                    - term_div_coupon_type: 传入分帐遇到优惠的处理规则（1按比例分/2按分账明细顺序保障/3只给交易商户）
     *                    - wx_data: 聚合反扫微信参数集合（JSON字符串）
     *                    - alipay_data: 支付宝扩展参数集合（JSON字符串）
     *                    - unionpay_data: 银联参数集合（JSON字符串）
     *                    - terminal_device_info: 设备信息（JSON字符串）
     *                    - notify_url: 异步通知地址
     *                    - remark: 交易备注（最长255字符）
     *                    - acct_id: 账户号（9位）
     *                    - trans_fee_allowance_info: 手续费补贴信息（JSON字符串）
     *
     * @return array 支付结果
     *               主要返回参数：
     *               - resp_code: 业务响应码（00000000成功/00000100处理中）
     *               - resp_desc: 业务响应信息
     *               - trans_stat: 交易状态（P处理中/S成功/F失败）
     *               - hf_seq_id: 全局流水号
     *               - out_trans_id: 用户账单上的交易订单号
     *               - party_order_id: 用户账单上的商户订单号
     *               - trans_amt: 交易金额（元）
     *               - pay_amt: 消费者实付金额（元）
     *               - settlement_amt: 结算金额（元）
     *               - trade_type: 交易类型（T_MICROPAY/A_MICROPAY/U_MICROPAY/D_MICROPAY）
     *               - acct_stat: 账务状态（P处理中/S成功/F失败）
     *               - debit_type: 借贷记标识（1借记卡/2贷记卡/3其他）
     *               - bank_desc: 外部通道返回描述
     *               - delay_acct_flag: 延时标记（Y延迟/N实时）
     *               - end_time: 支付完成时间（yyyyMMddHHMMSS）
     *               - wx_user_id: 微信用户唯一标识码
     *               - wx_response: 微信返回的响应报文（JSON字符串）
     *               - alipay_response: 支付宝返回的响应报文（JSON字符串）
     *               - dc_response: 数字货币返回报文（JSON字符串）
     *               - unionpay_response: 银联返回的响应报文（JSON字符串）
     *               - fee_amt: 手续费金额（元）
     *               - fee_flag: 手续费扣款标志（1外扣/2内扣）
     *               - device_type: 终端类型
     *               - fee_formula_infos: 手续费费率信息（JSON数组）
     *               - combinedpay_data: 补贴支付信息（JSON数组）
     *               - atu_sub_mer_id: ATU真实商户号
     *               - unconfirm_amt: 待确认金额（延迟交易场景）
     *
     *               业务返回码：
     *               - 00000000: 交易受理成功；注：交易状态以trans_stat为准
     *               - 00000100: 交易正在处理中
     *               - 10000000: 不支持交易类型
     *               - 10000000: 订单时间错误
     *               - 10000000: 付款码格式异常
     *               - 10000000: 请求日期必须是当前日期
     *               - 10000000: %s不能为空（%s代指报错参数名）
     *               - 10000000: %s不符合%s格式（%s代指报错参数名）
     *               - 10000000: %s长度固定%d位（%s代指报错参数名、%d代指字段长度）
     *               - 10000000: %s最大长度为%d位（%s代指报错参数名、%d代指字段长度）
     *               - 10000000: %s的传入枚举[%s]不存在（%s代指报错参数名）
     *               - 10000000: 产品号,原预授权交易请求流水,请求日期都不能为空
     *               - 21000000: 手续费金额、手续费收取方式、手续费扣款标识、手续费子客户号、手续费账户号，必须同时为空或同时必填
     *               - 22000002: 商户信息不存在
     *               - 22000002: 商户状态异常
     *               - 22000002: 商户和产品的关联信息有误
     *               - 22000003: 账户信息配置有误/账户信息不存在
     *               - 22000003: 默认账户配置有误
     *               - 22000004: 商户支付交易业务配置错误/细化指定需要的功能
     *               - 22000004: 暂未开通分账权限
     *               - 22000004: 暂未开通延时入账权限
     *               - 22000005: 分账串配置未包含手续费承担方
     *               - 22000005: 其他成员分账比例过高
     *               - 22000005: 分账列表必须包含主交易账户
     *               - 22000005: 内扣交易，手续费承担方必须参与分账
     *               - 22000005: 商户未配置默认入驻信息(多通道)
     *               - 22000005: 商户贴息分期费率有误
     *               - 22000005: 商户贴息分期费率未配置费率类型
     *               - 22000005: 商户贴息分期费率未配置渠道号
     *               - 22000005: 商户多通道配置有误
     *               - 22000005: 商户入驻信息配置有误(多通道)
     *               - 22000005: 分账配置有误
     *               - 22000005: 分账比例配置有误
     *               - 22000005: 该交易暂未配置支付费率
     *               - 22000005: 商户支付宝微信入驻信息配置有误
     *               - 22000005: 多通道入驻信息配置有误
     *               - 22000005: 商户银联入驻信息配置有误
     *               - 23000001: 原预授权交易不存在
     *               - 23000003: 交易金额不足以支付手续费
     *               - 23000003: 优惠金额大于交易金额
     *               - 23000003: 分账金额总和必须等于交易金额
     *               - 23000004: 交易类型不支持
     *               - 90000000: 业务执行失败，付款码无效，请重新扫码
     *               - 90000000: 业务执行失败，每个二维码仅限使用一次，请刷新再试
     *               - 90000000: 业务执行失败，付款码已过期，请退出重试
     *               - 90000000: 业务执行失败，当前商户需补齐相关资料后，才可进行支付交易，请商户联系服务商
     *               - 90000000: 交易存在风险
     *               - 91111119: 通道异常，请稍后重试
     *               - 98888888: 系统错误
     *               - 99999999: 系统异常,请重试
     *
     * @throws DougongException 当参数验证失败时抛出异常
     *
     * @see https://api.huifu.com/v3/trade/payment/micropay 官方接口文档
     * @since 1.0.0
     */
    public function pay(array $data): array
    {
        $this->validatePayParams($data);

        return $this->executePay($data);
    }

    /**
     * 验证支付参数
     *
     * @param array $data 支付参数
     * @throws DougongException 当参数验证失败时抛出异常
     */
    private function validatePayParams(array $data): void
    {
        // 验证必填参数
        PaymentValidator::validateRequiredFields($data, [
            'req_date' => '请求日期',
            'req_seq_id' => '请求流水号',
            'huifu_id' => '商户号',
            'trans_amt' => '交易金额',
            'goods_desc' => '商品描述',
            'auth_code' => '支付授权码',
            'risk_check_data' => '安全信息'
        ]);

        // 验证商户号格式
        PaymentValidator::validateHuifuId($data['huifu_id']);

        // 验证日期格式
        PaymentValidator::validateDateFormat($data['req_date'], 'date', 'req_date');

        // 验证交易金额格式
        PaymentValidator::validateTransAmount($data['trans_amt']);

        // 验证字符串长度
        PaymentValidator::validateStringLength($data['req_seq_id'], 128, 'req_seq_id');
        PaymentValidator::validateStringLength($data['goods_desc'], 127, 'goods_desc');
        PaymentValidator::validateStringLength($data['auth_code'], 128, 'auth_code');

        // 验证交易有效期格式（如果提供）
        if (isset($data['time_expire'])) {
            PaymentValidator::validateDateFormat($data['time_expire'], 'datetime', 'time_expire');
        }

        // 验证手续费扣款标志（如果提供）
        if (isset($data['fee_flag'])) {
            PaymentValidator::validateEnum($data['fee_flag'], ['1', '2'], 'fee_flag');
        }

        // 验证延迟交易标志（如果提供）
        if (isset($data['delay_acct_flag'])) {
            PaymentValidator::validateEnum($data['delay_acct_flag'], ['Y', 'N'], 'delay_acct_flag');
        }

        // 验证分账处理规则（如果提供）
        if (isset($data['term_div_coupon_type'])) {
            PaymentValidator::validateEnum($data['term_div_coupon_type'], ['1', '2', '3'], 'term_div_coupon_type');
        }

        // 验证可选参数长度
        if (isset($data['limit_pay_type'])) {
            PaymentValidator::validateStringLength($data['limit_pay_type'], 128, 'limit_pay_type');
        }

        if (isset($data['channel_no'])) {
            PaymentValidator::validateStringLength($data['channel_no'], 32, 'channel_no');
        }

        if (isset($data['pay_scene'])) {
            PaymentValidator::validateStringLength($data['pay_scene'], 2, 'pay_scene');
        }

        if (isset($data['notify_url'])) {
            PaymentValidator::validateStringLength($data['notify_url'], 512, 'notify_url');
        }

        if (isset($data['remark'])) {
            PaymentValidator::validateStringLength($data['remark'], 255, 'remark');
        }

        if (isset($data['acct_id'])) {
            PaymentValidator::validateStringLength($data['acct_id'], 9, 'acct_id');
        }
    }

    /**
     * 执行支付请求
     *
     * @param array $data 支付参数
     * @return array 支付结果
     * @throws DougongException
     */
    private function executePay(array $data): array
    {
        $this->url = $this->dougongConfig->baseUri . '/v3/trade/payment/micropay';

        $postData = [
            'req_date' => $data['req_date'],
            'req_seq_id' => $data['req_seq_id'],
            'huifu_id' => $data['huifu_id'],
            'trans_amt' => $data['trans_amt'],
            'goods_desc' => $data['goods_desc'],
            'auth_code' => $data['auth_code'],
            'risk_check_data' => $data['risk_check_data'],
        ];

        // 设置可选参数
        $optionalParams = [
            'time_expire', 'fee_flag', 'limit_pay_type', 'delay_acct_flag',
            'channel_no', 'combinedpay_data', 'combinedpay_data_fee_info',
            'pay_scene', 'acct_split_bunch', 'term_div_coupon_type',
            'wx_data', 'alipay_data', 'unionpay_data', 'terminal_device_info',
            'notify_url', 'remark', 'acct_id', 'trans_fee_allowance_info'
        ];

        foreach ($optionalParams as $param) {
            if (isset($data[$param])) {
                $postData[$param] = $data[$param];
            }
        }

        $signTool = new SignTool($this->dougongConfig);

        $this->params = [
            'sys_id' => $this->dougongConfig->sysId,
            'product_id' => $this->dougongConfig->productId,
            'data' => $postData,
            'sign' => $signTool->sign($postData),
        ];

        return $this->postRequest();
    }
}