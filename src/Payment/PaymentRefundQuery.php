<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Payment;

use cccdl\DougongPay\Core\BaseCore;
use cccdl\DougongPay\Traits\Request;
use cccdl\DougongPay\Tools\SignTool;
use cccdl\DougongPay\Tools\PaymentValidator;
use cccdl\DougongPay\Exception\DougongException;

class PaymentRefundQuery extends BaseCore
{
    use Request;

    private string $url;
    private array $params;
    private array $header;

    /**
     * 扫码交易退款查询接口
     *
     * 商家调用退款接口未收到状态返回，调用本接口查询退款结果。
     * 调用斗拱支付扫码交易退款查询接口：/v3/trade/payment/scanpay/refundquery
     *
     * 支持的退款交易查询：
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
     * 使用示例：
     * ```php
     * // 通过退款全局流水号查询
     * $result = $paymentRefundQuery->query([
     *     'huifu_id' => '6666000000000000',
     *     'org_hf_seq_id' => '0030default220825182711P099ac1f343f*****'
     * ]);
     *
     * // 通过退款请求流水号查询
     * $result = $paymentRefundQuery->query([
     *     'huifu_id' => '6666000000000000',
     *     'org_req_seq_id' => '20211021001210****',
     *     'org_req_date' => '20240405'
     * ]);
     *
     * // 通过终端订单号查询
     * $result = $paymentRefundQuery->query([
     *     'huifu_id' => '6666000000000000',
     *     'mer_ord_id' => '166726708335243****',
     *     'org_req_date' => '20240405'
     * ]);
     * ```
     *
     * @param array $data 查询参数
     *                    必填参数：
     *                    - huifu_id: 商户号（16位数字）
     *
     *                    条件必填参数（三选一）：
     *                    - org_hf_seq_id: 退款全局流水号
     *                    - org_req_seq_id: 退款请求流水号
     *                    - mer_ord_id: 终端订单号
     *
     *                    可选参数：
     *                    - org_req_date: 退款请求日期（yyyyMMdd格式，传入org_hf_seq_id时非必填）
     *
     * @return array 查询结果
     *               主要返回参数：
     *               - resp_code: 业务响应码（00000000表示成功）
     *               - resp_desc: 业务响应信息
     *               - trans_stat: 交易状态（P处理中/S成功/F失败/I初始，以此字段为准）
     *               - ord_amt: 退款金额（元）
     *               - actual_ref_amt: 实际退款金额（元）
     *               - trans_date: 交易发生日期
     *               - trans_time: 交易发生时间
     *               - trans_finish_time: 退款完成时间
     *               - trans_type: 交易类型（TRANS_REFUND）
     *               - org_hf_seq_id: 退款全局流水号
     *               - org_req_seq_id: 退款请求流水号
     *               - mer_ord_id: 终端订单号
     *               - org_party_order_id: 原交易用户账单上的商户订单号
     *               - fee_amt: 手续费金额
     *               - is_refund_fee_flag: 是否退还手续费
     *               - bank_message: 通道返回描述
     *               - wx_response: 微信响应报文（JSON字符串）
     *               - alipay_response: 支付宝响应报文（JSON字符串）
     *               - unionpay_response: 银联响应报文（JSON字符串）
     *               - dc_response: 数字货币响应报文
     *
     *               业务返回码：
     *               - 00000000: 查询成功
     *               - 10000000: 入参数据不符合接口要求
     *               - 20000001: 并发冲突，请稍后重试
     *               - 21000000: 原退款全局流水号，原退款请求流水号，外部订单号不能同时为空
     *               - 22000000: 产品号不存在
     *               - 22000000: 产品号状态异常
     *               - 23000001: 原交易不存在
     *               - 99999999: 系统异常，请稍后重试
     *
     * @throws DougongException 当参数验证失败时抛出异常
     *
     * @see https://api.huifu.com/v3/trade/payment/scanpay/refundquery 官方接口文档
     * @since 1.0.0
     */
    public function query(array $data): array
    {
        $this->validateQueryParams($data);

        return $this->executeQuery($data);
    }

    /**
     * 验证查询参数
     *
     * @param array $data 查询参数
     * @throws DougongException 当参数验证失败时抛出异常
     */
    private function validateQueryParams(array $data): void
    {
        // 验证必填参数
        PaymentValidator::validateRequiredFields($data, ['huifu_id' => '商户号']);

        // 验证商户号格式
        PaymentValidator::validateHuifuId($data['huifu_id']);

        // 验证条件必填参数（三选一）
        $conditionalFields = ['org_hf_seq_id', 'org_req_seq_id', 'mer_ord_id'];
        PaymentValidator::validateConditionalRequired(
            $data,
            $conditionalFields,
            'org_hf_seq_id、org_req_seq_id、mer_ord_id 三个参数必填其一'
        );

        // 验证退款请求日期格式（如果提供）
        if (isset($data['org_req_date'])) {
            PaymentValidator::validateDateFormat($data['org_req_date'], 'date', 'org_req_date');
        }

        // 验证字符串长度
        if (isset($data['org_hf_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_hf_seq_id'], 128, 'org_hf_seq_id');
        }

        if (isset($data['org_req_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_req_seq_id'], 128, 'org_req_seq_id');
        }

        if (isset($data['mer_ord_id'])) {
            PaymentValidator::validateStringLength($data['mer_ord_id'], 50, 'mer_ord_id');
        }
    }

    /**
     * 执行查询请求
     *
     * @param array $data 查询参数
     * @return array 查询结果
     */
    private function executeQuery(array $data): array
    {
        $this->url = $this->dougongConfig->baseUri . '/v3/trade/payment/scanpay/refundquery';

        $postData = [
            'huifu_id' => $data['huifu_id'],
        ];

        // 设置条件必填参数
        $conditionalParams = ['org_hf_seq_id', 'org_req_seq_id', 'mer_ord_id'];
        foreach ($conditionalParams as $param) {
            if (isset($data[$param])) {
                $postData[$param] = $data[$param];
            }
        }

        // 设置可选参数
        if (isset($data['org_req_date'])) {
            $postData['org_req_date'] = $data['org_req_date'];
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