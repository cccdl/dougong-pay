<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Payment;

use cccdl\DougongPay\Core\BaseCore;
use cccdl\DougongPay\Traits\Request;
use cccdl\DougongPay\Tools\SignTool;
use cccdl\DougongPay\Tools\PaymentValidator;
use cccdl\DougongPay\Exception\DougongException;

class PaymentCloseQuery extends BaseCore
{
    use Request;

    private string $url;
    private array $params;
    private array $header;

    /**
     * 扫码交易关单查询接口
     *
     * 服务商/商户发起关单请求后，未收到关单结果，可通过本接口查询关单状态。
     * 调用斗拱支付扫码交易关单查询接口：/v2/trade/payment/scanpay/closequery
     *
     * 注意：只能通过原交易查询关单
     *
     * 使用示例：
     * ```php
     * // 通过原交易请求流水号查询关单状态
     * $result = $paymentCloseQuery->query([
     *     'req_date' => '20240425',
     *     'req_seq_id' => '20240425104052910l0c5dsjxmqp****',
     *     'huifu_id' => '6666000000000001',
     *     'org_req_date' => '20240328',
     *     'org_req_seq_id' => '20240129555522220211711612****'
     * ]);
     *
     * // 通过原交易全局流水号查询关单状态
     * $result = $paymentCloseQuery->query([
     *     'req_date' => '20240425',
     *     'req_seq_id' => '20240425104052910l0c5dsjxmqp****',
     *     'huifu_id' => '6666000000000001',
     *     'org_req_date' => '20240328',
     *     'org_hf_seq_id' => '0030default220825182711P099ac1f343f*****'
     * ]);
     * ```
     *
     * @param array $data 查询参数
     *                    必填参数：
     *                    - req_date: 请求日期（yyyyMMdd格式）
     *                    - req_seq_id: 请求流水号（同一huifu_id下当天唯一）
     *                    - huifu_id: 商户号（16位数字）
     *                    - org_req_date: 原交易请求日期（yyyyMMdd格式）
     *
     *                    条件必填参数（二选一）：
     *                    - org_req_seq_id: 原交易请求流水号
     *                    - org_hf_seq_id: 原交易返回的全局流水号
     *
     * @return array 查询结果
     *               主要返回参数：
     *               - resp_code: 业务响应码（00000000表示成功）
     *               - resp_desc: 业务响应信息
     *               - huifu_id: 商户号
     *               - req_date: 请求日期（原样返回）
     *               - req_seq_id: 请求流水号（原样返回）
     *               - org_req_date: 原交易请求日期
     *               - org_req_seq_id: 原交易请求流水号
     *               - org_hf_seq_id: 原交易的全局流水号
     *               - org_trans_stat: 原交易状态（P处理中/S成功/F失败）
     *               - trans_stat: 关单状态（P处理中/S成功/F失败，以此字段为准）
     *
     *               业务返回码：
     *               - 00000000: 查询成功
     *               - 10000000: 入参数据不符合接口要求
     *               - 21000000: 原请求流水号和原全局流水号不能同时为空
     *               - 22000000: 产品号不存在
     *               - 22000000: 产品号状态异常
     *               - 23000001: 原交易不存在
     *               - 98888888: 系统错误
     *
     * @throws DougongException 当参数验证失败时抛出异常
     *
     * @see https://api.huifu.com/v2/trade/payment/scanpay/closequery 官方接口文档
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
        PaymentValidator::validateRequiredFields($data, [
            'req_date' => '请求日期',
            'req_seq_id' => '请求流水号',
            'huifu_id' => '商户号',
            'org_req_date' => '原交易请求日期'
        ]);

        // 验证商户号格式
        PaymentValidator::validateHuifuId($data['huifu_id']);

        // 验证条件必填参数（二选一）
        $conditionalFields = ['org_req_seq_id', 'org_hf_seq_id'];
        PaymentValidator::validateConditionalRequired(
            $data,
            $conditionalFields,
            'org_req_seq_id、org_hf_seq_id 两个参数必填其一'
        );

        // 验证日期格式
        PaymentValidator::validateDateFormat($data['req_date'], 'date', 'req_date');
        PaymentValidator::validateDateFormat($data['org_req_date'], 'date', 'org_req_date');

        // 验证字符串长度
        PaymentValidator::validateStringLength($data['req_seq_id'], 128, 'req_seq_id');

        if (isset($data['org_req_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_req_seq_id'], 128, 'org_req_seq_id');
        }

        if (isset($data['org_hf_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_hf_seq_id'], 128, 'org_hf_seq_id');
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
        $this->url = $this->dougongConfig->baseUri . '/v2/trade/payment/scanpay/closequery';

        $postData = [
            'req_date' => $data['req_date'],
            'req_seq_id' => $data['req_seq_id'],
            'huifu_id' => $data['huifu_id'],
            'org_req_date' => $data['org_req_date'],
        ];

        // 设置条件必填参数
        $conditionalParams = ['org_req_seq_id', 'org_hf_seq_id'];
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