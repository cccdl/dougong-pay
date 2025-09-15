<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Payment;

use cccdl\DougongPay\Core\BaseCore;
use cccdl\DougongPay\Traits\Request;
use cccdl\DougongPay\Tools\SignTool;
use cccdl\DougongPay\Tools\PaymentValidator;
use cccdl\DougongPay\Exception\DougongException;

class PaymentClose extends BaseCore
{
    use Request;

    private string $url;
    private array $params;
    private array $header;

    /**
     * 扫码交易关单接口
     *
     * 服务商/商户系统通过本接口发起订单关闭请求。
     * 调用斗拱支付扫码交易关单接口：/v2/trade/payment/scanpay/close
     *
     * 适用对象：
     * - 开通微信/支付宝权限的商户
     *
     * 注意事项：
     * - 银联、数字货币订单不支持关单
     * - 原交易已是终态（成功/失败）的，关单会失败
     * - 不允许关闭一分钟以内的订单
     *
     * 使用示例：
     * ```php
     * // 通过原交易请求流水号关单
     * $result = $paymentClose->close([
     *     'req_date' => date('Ymd'),
     *     'req_seq_id' => 'CLOSE_' . date('YmdHis') . rand(1000, 9999),
     *     'huifu_id' => '6666000000000000',
     *     'org_req_date' => '20240405',
     *     'org_req_seq_id' => '2021091895616****'
     * ]);
     *
     * // 通过原交易全局流水号关单
     * $result = $paymentClose->close([
     *     'req_date' => date('Ymd'),
     *     'req_seq_id' => 'CLOSE_' . date('YmdHis') . rand(1000, 9999),
     *     'huifu_id' => '6666000000000000',
     *     'org_req_date' => '20240405',
     *     'org_hf_seq_id' => '0030default220825182711P099ac1f343f*****'
     * ]);
     * ```
     *
     * @param array $data 关单参数
     *                    必填参数：
     *                    - req_date: 请求日期（yyyyMMdd格式）
     *                    - req_seq_id: 请求流水号（同一huifu_id下当天唯一）
     *                    - huifu_id: 商户号（16位数字）
     *                    - org_req_date: 原交易请求日期（yyyyMMdd格式）
     *
     *                    条件必填参数（二选一）：
     *                    - org_hf_seq_id: 原交易返回的全局流水号
     *                    - org_req_seq_id: 原交易请求流水号
     *
     * @return array 关单结果
     *               主要返回参数：
     *               - resp_code: 业务响应码（00000000表示成功）
     *               - resp_desc: 业务响应信息
     *               - trans_stat: 关单状态（P处理中/S成功/F失败）
     *               - org_trans_stat: 原交易状态（P处理中/S成功/F失败）
     *               - req_date: 请求日期（原样返回）
     *               - req_seq_id: 请求流水号（原样返回）
     *               - huifu_id: 商户号
     *               - org_req_date: 原交易请求日期
     *               - org_req_seq_id: 原交易请求流水号
     *               - org_hf_seq_id: 原交易的全局流水号
     *
     *               业务返回码：
     *               - 00000000: 交易成功
     *               - 00000100: 交易处理中
     *               - 10000000: 入参数据不符合接口要求
     *               - 20000001: 不允许关闭一分钟以内的订单
     *               - 20000001: 并发冲突，请稍后重试
     *               - 21000000: 原请求流水号和原全局流水号不能同时为空
     *               - 22000000: 产品号不存在
     *               - 22000000: 产品号状态异常
     *               - 23000000: 原订单已为终态，无法发起关单操作
     *               - 23000000: 关单状态为终态，不能重复关单
     *               - 23000001: 原交易不存在
     *               - 23000004: 原订单为银联二维码交易，不支持关单
     *               - 90000000: 业务执行失败
     *               - 98888888: 系统错误
     *               - 99999999: 系统异常，请稍后重试
     *
     * @throws DougongException 当参数验证失败时抛出异常
     *
     * @see https://api.huifu.com/v2/trade/payment/scanpay/close 官方接口文档
     * @since 1.0.0
     */
    public function close(array $data): array
    {
        $this->validateCloseParams($data);

        return $this->executeClose($data);
    }

    /**
     * 验证关单参数
     *
     * @param array $data 关单参数
     * @throws DougongException 当参数验证失败时抛出异常
     */
    private function validateCloseParams(array $data): void
    {
        // 验证必填参数
        PaymentValidator::validateRequiredFields($data, [
            'req_date' => '请求日期',
            'req_seq_id' => '请求流水号',
            'huifu_id' => '商户号',
            'org_req_date' => '原交易请求日期'
        ]);

        // 验证商户号格式
        PaymentValidator::validateHuifuId($data['huifu_id']);

        // 验证日期格式
        PaymentValidator::validateDateFormat($data['req_date'], 'date', 'req_date');
        PaymentValidator::validateDateFormat($data['org_req_date'], 'date', 'org_req_date');

        // 验证字符串长度
        PaymentValidator::validateStringLength($data['req_seq_id'], 128, 'req_seq_id');

        // 验证条件必填参数（二选一）
        $conditionalFields = ['org_hf_seq_id', 'org_req_seq_id'];
        PaymentValidator::validateConditionalRequired(
            $data,
            $conditionalFields,
            'org_hf_seq_id、org_req_seq_id 两个参数必填其一'
        );

        // 验证条件参数长度
        if (isset($data['org_hf_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_hf_seq_id'], 128, 'org_hf_seq_id');
        }

        if (isset($data['org_req_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_req_seq_id'], 128, 'org_req_seq_id');
        }
    }

    /**
     * 执行关单请求
     *
     * @param array $data 关单参数
     * @return array 关单结果
     * @throws DougongException
     */
    private function executeClose(array $data): array
    {
        $this->url = $this->dougongConfig->baseUri . '/v2/trade/payment/scanpay/close';

        $postData = [
            'req_date' => $data['req_date'],
            'req_seq_id' => $data['req_seq_id'],
            'huifu_id' => $data['huifu_id'],
            'org_req_date' => $data['org_req_date'],
        ];

        // 设置条件必填参数
        $conditionalParams = ['org_hf_seq_id', 'org_req_seq_id'];
        foreach ($conditionalParams as $param) {
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