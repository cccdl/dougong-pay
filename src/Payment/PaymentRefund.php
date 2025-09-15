<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Payment;

use cccdl\DougongPay\Core\BaseCore;
use cccdl\DougongPay\Traits\Request;
use cccdl\DougongPay\Tools\SignTool;
use cccdl\DougongPay\Tools\PaymentValidator;
use cccdl\DougongPay\Exception\DougongException;

class PaymentRefund extends BaseCore
{
    use Request;

    private string $url;
    private array $params;
    private array $header;

    /**
     * 扫码交易退款接口
     *
     * 交易发生之后一段时间内，由于用户或者商户的原因需要退款时，
     * 商户可以通过本接口将支付款退还给用户，退款成功资金将原路返回。
     * 调用斗拱支付扫码交易退款接口：/v3/trade/payment/scanpay/refund
     *
     * 支持的支付类型退款：
     * - 微信公众号：T_JSAPI
     * - 微信小程序：T_MINIAPP
     * - 微信APP支付：T_APP
     * - 支付宝JS：A_JSAPI
     * - 支付宝正扫：A_NATIVE
     * - 银联二维码正扫：U_NATIVE
     * - 银联二维码JS：U_JSAPI
     * - 数字货币二维码支付：D_NATIVE
     * - 微信反扫：T_MICROPAY
     * - 支付宝反扫：A_MICROPAY
     * - 银联反扫：U_MICROPAY
     * - 数字人民币反扫：D_MICROPAY
     *
     * 退款期限：
     * - 微信：360天
     * - 支付宝：360天
     * - 银联二维码：360天
     *
     * 使用示例：
     * ```php
     * // 通过原交易全局流水号退款
     * $result = $paymentRefund->refund([
     *     'req_date' => date('Ymd'),
     *     'req_seq_id' => 'REFUND_' . date('YmdHis') . rand(1000, 9999),
     *     'huifu_id' => '6666000000000000',
     *     'ord_amt' => '0.01',
     *     'org_req_date' => '20240405',
     *     'org_hf_seq_id' => '0030default220825182711P099ac1f343f*****'
     * ]);
     *
     * // 通过原交易请求流水号退款
     * $result = $paymentRefund->refund([
     *     'req_date' => date('Ymd'),
     *     'req_seq_id' => 'REFUND_' . date('YmdHis') . rand(1000, 9999),
     *     'huifu_id' => '6666000000000000',
     *     'ord_amt' => '0.01',
     *     'org_req_date' => '20240405',
     *     'org_req_seq_id' => '2021091895616****'
     * ]);
     *
     * // 通过原交易商户单号退款
     * $result = $paymentRefund->refund([
     *     'req_date' => date('Ymd'),
     *     'req_seq_id' => 'REFUND_' . date('YmdHis') . rand(1000, 9999),
     *     'huifu_id' => '6666000000000000',
     *     'ord_amt' => '0.01',
     *     'org_req_date' => '20240405',
     *     'org_party_order_id' => '0323210919025510560****'
     * ]);
     * ```
     *
     * @param array $data 退款参数
     *                    必填参数：
     *                    - req_date: 请求日期（yyyyMMdd格式）
     *                    - req_seq_id: 请求流水号（同一huifu_id下当天唯一）
     *                    - huifu_id: 商户号（16位数字）
     *                    - ord_amt: 申请退款金额（元，保留2位小数，最低0.01）
     *                    - org_req_date: 原交易请求日期（yyyyMMdd格式）
     *
     *                    条件必填参数（三选一）：
     *                    - org_hf_seq_id: 原交易全局流水号
     *                    - org_party_order_id: 原交易微信支付宝的商户单号
     *                    - org_req_seq_id: 原交易请求流水号
     *
     *                    可选参数：
     *                    - acct_split_bunch: 分账对象（JSON字符串）
     *                    - wx_data: 聚合正扫微信拓展参数集合（JSON字符串）
     *                    - digital_currency_data: 数字货币扩展参数集合（JSON字符串）
     *                    - combinedpay_data: 补贴支付信息（JSON字符串）
     *                    - combinedpay_data_fee_info: 补贴支付手续费承担方信息（JSON字符串）
     *                    - remark: 备注（最长84字符，原样返回）
     *                    - loan_flag: 是否垫资退款（Y垫资/N普通，默认N）
     *                    - loan_undertaker: 垫资承担者（huifu_id）
     *                    - loan_acct_type: 垫资账户类型（01基本户/05充值户，默认充值户）
     *                    - risk_check_data: 安全信息（JSON字符串）
     *                    - terminal_device_data: 设备信息（JSON字符串）
     *                    - notify_url: 异步通知地址
     *                    - unionpay_data: 银联参数集合（JSON字符串）
     *
     * @return array 退款结果
     *               主要返回参数：
     *               - resp_code: 业务响应码（00000000成功/00000100处理中）
     *               - resp_desc: 业务响应信息
     *               - trans_stat: 交易状态（P处理中/S成功/F失败）
     *               - hf_seq_id: 全局流水号（可用于后续查询）
     *               - ord_amt: 退款金额（元）
     *               - actual_ref_amt: 实际退款金额（元）
     *               - trans_date: 退款交易发生日期
     *               - trans_time: 退款交易发生时间
     *               - trans_finish_time: 退款完成时间
     *               - bank_message: 通道返回描述
     *               - wx_response: 微信响应报文（JSON字符串）
     *               - alipay_response: 支付宝响应报文（JSON字符串）
     *               - unionpay_response: 银联响应报文（JSON字符串）
     *               - dc_response: 数字货币响应报文
     *
     *               业务返回码：
     *               - 00000000: 交易成功
     *               - 00000100: 交易处理中
     *               - 10000000: 入参数据不符合接口要求
     *               - 20000001: 并发冲突，请稍后重试
     *               - 21000000: 原交易请求流水号，原交易微信支付宝的商户单号，原交易全局流水号不能同时为空
     *               - 21000000: 数字货币交易退款原因必填
     *               - 22000000: 产品号不存在
     *               - 22000000: 产品号状态异常
     *               - 22000002: 商户信息不存在
     *               - 22000002: 商户状态异常
     *               - 22000003: 账户信息不存在
     *               - 22000004: 暂未开通退款权限
     *               - 22000004: 暂未开通分账退款权限
     *               - 22000005: 结算配置信息不存在
     *               - 23000000: 原交易未入账，不能发起退款
     *               - 23000001: 原交易不存在
     *               - 23000002: 退款手续费承担方和原交易手续费承担方不一致
     *               - 23000003: 申请退款金额大于可退余额
     *               - 23000003: 退款金额大于待确认金额
     *               - 23000003: 手续费退款金额大于可退手续费金额
     *               - 23000003: 申请退款金额大于可退款余额
     *               - 23000003: 退款分账金额总和必须等于退款订单金额
     *               - 23000003: 账户余额不足
     *               - 23000004: 不支持预授权撤销类交易
     *               - 23000004: 不支持刷卡撤销类交易
     *               - 23000004: 优惠交易不支持部分退款
     *               - 23000004: 该交易为部分退款，需传入分账串
     *               - 23000004: 优惠退款不支持传入分账串
     *               - 23000004: 分账串信息与原交易不匹配
     *               - 90000000: 业务执行失败，可用余额不足
     *               - 90000000: 交易存在风险
     *               - 98888888: 系统错误
     *               - 99999999: 系统异常，请稍后重试
     *
     * @throws DougongException 当参数验证失败时抛出异常
     *
     * @see https://api.huifu.com/v3/trade/payment/scanpay/refund 官方接口文档
     * @since 1.0.0
     */
    public function refund(array $data): array
    {
        $this->validateRefundParams($data);

        return $this->executeRefund($data);
    }

    /**
     * 验证退款参数
     *
     * @param array $data 退款参数
     * @throws DougongException 当参数验证失败时抛出异常
     */
    private function validateRefundParams(array $data): void
    {
        // 验证必填参数
        PaymentValidator::validateRequiredFields($data, [
            'req_date' => '请求日期',
            'req_seq_id' => '请求流水号',
            'huifu_id' => '商户号',
            'ord_amt' => '申请退款金额',
            'org_req_date' => '原交易请求日期'
        ]);

        // 验证商户号格式
        PaymentValidator::validateHuifuId($data['huifu_id']);

        // 验证日期格式
        PaymentValidator::validateDateFormat($data['req_date'], 'date', 'req_date');
        PaymentValidator::validateDateFormat($data['org_req_date'], 'date', 'org_req_date');

        // 验证退款金额格式
        PaymentValidator::validateTransAmount($data['ord_amt']);

        // 验证字符串长度
        PaymentValidator::validateStringLength($data['req_seq_id'], 128, 'req_seq_id');

        // 验证条件必填参数（三选一）
        $conditionalFields = ['org_hf_seq_id', 'org_party_order_id', 'org_req_seq_id'];
        PaymentValidator::validateConditionalRequired(
            $data,
            $conditionalFields,
            'org_hf_seq_id、org_party_order_id、org_req_seq_id 三个参数必填其一'
        );

        // 验证条件参数长度
        if (isset($data['org_hf_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_hf_seq_id'], 128, 'org_hf_seq_id');
        }

        if (isset($data['org_party_order_id'])) {
            PaymentValidator::validateStringLength($data['org_party_order_id'], 64, 'org_party_order_id');
        }

        if (isset($data['org_req_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_req_seq_id'], 128, 'org_req_seq_id');
        }

        // 验证可选参数
        if (isset($data['remark'])) {
            PaymentValidator::validateStringLength($data['remark'], 84, 'remark');
        }

        if (isset($data['loan_flag'])) {
            PaymentValidator::validateEnum($data['loan_flag'], ['Y', 'N'], 'loan_flag');
        }

        if (isset($data['loan_acct_type'])) {
            PaymentValidator::validateEnum($data['loan_acct_type'], ['01', '05'], 'loan_acct_type');
        }

        if (isset($data['notify_url'])) {
            PaymentValidator::validateStringLength($data['notify_url'], 512, 'notify_url');
        }
    }

    /**
     * 执行退款请求
     *
     * @param array $data 退款参数
     * @return array 退款结果
     * @throws DougongException
     */
    private function executeRefund(array $data): array
    {
        $this->url = $this->dougongConfig->baseUri . '/v3/trade/payment/scanpay/refund';

        $postData = [
            'req_date' => $data['req_date'],
            'req_seq_id' => $data['req_seq_id'],
            'huifu_id' => $data['huifu_id'],
            'ord_amt' => $data['ord_amt'],
            'org_req_date' => $data['org_req_date'],
        ];

        // 设置条件必填参数
        $conditionalParams = ['org_hf_seq_id', 'org_party_order_id', 'org_req_seq_id'];
        foreach ($conditionalParams as $param) {
            if (isset($data[$param])) {
                $postData[$param] = $data[$param];
            }
        }

        // 设置可选参数
        $optionalParams = [
            'acct_split_bunch', 'wx_data', 'digital_currency_data', 'combinedpay_data',
            'combinedpay_data_fee_info', 'remark', 'loan_flag', 'loan_undertaker',
            'loan_acct_type', 'risk_check_data', 'terminal_device_data',
            'notify_url', 'unionpay_data'
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