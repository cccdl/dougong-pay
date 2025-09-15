<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Payment;

use cccdl\DougongPay\Core\BaseCore;
use cccdl\DougongPay\Traits\Request;
use cccdl\DougongPay\Tools\SignTool;
use cccdl\DougongPay\Tools\PaymentValidator;
use cccdl\DougongPay\Exception\DougongException;

class PaymentQuery extends BaseCore
{
    use Request;

    private string $url;
    private array $params;
    private array $header;

    /**
     * 扫码交易查询接口
     *
     * 服务商/商户系统因网络原因未收到交易状态，可以通过本接口主动查询订单状态。
     * 调用斗拱支付扫码交易查询接口：/v3/trade/payment/scanpay/query
     *
     * 支持的交易类型查询：
     * - 微信公众号支付：T_JSAPI
     * - 微信小程序支付：T_MINIAPP
     * - 微信APP支付：T_APP
     * - 支付宝JS支付：A_JSAPI
     * - 支付宝正扫：A_NATIVE
     * - 银联二维码正扫：U_NATIVE
     * - 银联二维码JS：U_JSAPI
     * - 数字货币二维码支付：D_NATIVE
     * - 微信反扫：T_MICROPAY
     * - 支付宝反扫：A_MICROPAY
     * - 银联反扫：U_MICROPAY
     * - 数字人民币反扫：D_MICROPAY
     * - 微信直连H5支付：T_H5
     *
     * 使用示例：
     * ```php
     * // 通过请求流水号查询
     * $result = $paymentQuery->query([
     *     'huifu_id' => '6666000000000000',
     *     'org_req_seq_id' => '2024040522182635****',
     *     'org_req_date' => '20240405'
     * ]);
     *
     * // 通过全局流水号查询
     * $result = $paymentQuery->query([
     *     'huifu_id' => '6666000000000000',
     *     'org_hf_seq_id' => '00290TOP1GR210919004230P853ac132622*****'
     * ]);
     *
     * // 通过服务订单号查询
     * $result = $paymentQuery->query([
     *     'huifu_id' => '6666000000000000',
     *     'out_ord_id' => '1234323JKHDFE124****'
     * ]);
     * ```
     *
     * @param array $data 查询参数
     *                    必填参数：
     *                    - huifu_id: 汇付商户号（16位数字）
     *
     *                    条件必填参数（三选一）：
     *                    - out_ord_id: 汇付服务订单号
     *                    - org_hf_seq_id: 创建服务订单返回的汇付全局流水号
     *                    - org_req_seq_id: 服务订单创建请求流水号
     *
     *                    可选参数：
     *                    - org_req_date: 原机构请求日期（yyyyMMdd格式，传入org_hf_seq_id时非必填）
     *
     * @return array 查询结果
     *               主要返回参数：
     *               - resp_code: 业务响应码（00000000表示成功）
     *               - resp_desc: 业务响应信息
     *               - trans_stat: 交易状态（P处理中/S成功/F失败/I初始，以此字段为准）
     *               - trans_amt: 交易金额
     *               - pay_amt: 消费者实付金额
     *               - trans_type: 交易类型
     *               - end_time: 支付完成时间（yyyyMMddHHMMSS）
     *               - org_hf_seq_id: 全局流水号
     *               - org_req_seq_id: 原请求流水号
     *               - out_trans_id: 用户账单上的交易订单号
     *               - party_order_id: 用户账单上的商户订单号
     *               - delay_acct_flag: 是否延时交易（Y/N）
     *               - div_flag: 是否分账交易（Y/N）
     *               - fee_amt: 手续费金额
     *               - fee_type: 手续费扣款标志（INNER内扣/OUTSIDE外扣）
     *               - wx_response: 微信响应报文（JSON字符串）
     *               - alipay_response: 支付宝响应报文（JSON字符串）
     *               - unionpay_response: 银联响应报文（JSON字符串）
     *               - dc_response: 数字货币响应报文（JSON字符串）
     *
     *               业务返回码：
     *               - 10000000: 请求内容体不能为空
     *               - 10000000: %s不能为空（%s代指报错参数名）
     *               - 10000000: %s长度固定%d位（%s代指报错参数名、%d代指字段长度）
     *               - 10000000: %s最大长度为%d位（%s代指报错参数名、%d代指字段长度）
     *               - 10000000: %s的传入枚举[%s]不存在（%s代指报错参数名）
     *               - 10000000: %s不符合%s格式（%s代指报错参数名）
     *               - 21000000: 原机构请求流水号、交易返回的全局流水号、用户账单上的商户订单号、用户账单上的交易订单号、外部订单号、终端订单号不能同时为空
     *               - 22000000: 产品号不存在
     *               - 22000000: 产品状态异常
     *               - 23000001: 交易不存在
     *               - 91111119: 通道异常，请稍后重试
     *               - 98888888: 系统错误
     *
     * @throws DougongException 当参数验证失败时抛出异常
     *
     * @see https://api.huifu.com/v3/trade/payment/scanpay/query 官方接口文档
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
        $conditionalFields = ['out_ord_id', 'org_hf_seq_id', 'org_req_seq_id'];
        PaymentValidator::validateConditionalRequired(
            $data,
            $conditionalFields,
            'out_ord_id、org_hf_seq_id、org_req_seq_id 三个参数必填其一'
        );

        // 验证原机构请求日期格式（如果提供）
        if (isset($data['org_req_date'])) {
            PaymentValidator::validateDateFormat($data['org_req_date'], 'date', 'org_req_date');
        }

        // 验证字符串长度
        if (isset($data['out_ord_id'])) {
            PaymentValidator::validateStringLength($data['out_ord_id'], 32, 'out_ord_id');
        }

        if (isset($data['org_hf_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_hf_seq_id'], 128, 'org_hf_seq_id');
        }

        if (isset($data['org_req_seq_id'])) {
            PaymentValidator::validateStringLength($data['org_req_seq_id'], 128, 'org_req_seq_id');
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
        $this->url = $this->dougongConfig->baseUri . '/v3/trade/payment/scanpay/query';

        $postData = [
            'huifu_id' => $data['huifu_id'],
        ];

        // 设置条件必填参数
        $conditionalParams = ['out_ord_id', 'org_hf_seq_id', 'org_req_seq_id'];
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