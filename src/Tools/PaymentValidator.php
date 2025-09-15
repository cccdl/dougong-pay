<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Tools;

use cccdl\DougongPay\Exception\DougongException;

class PaymentValidator
{
    /**
     * 验证商户号格式
     *
     * @param string $huifuId 商户号
     * @throws DougongException 当格式错误时抛出异常
     */
    public static function validateHuifuId(string $huifuId): void
    {
        if (!preg_match('/^\d{16}$/', $huifuId)) {
            throw new DougongException('huifu_id 格式错误，应为16位数字');
        }
    }

    /**
     * 验证日期格式
     *
     * @param string $date 日期字符串
     * @param string $format 格式类型 ('date' 为 yyyyMMdd, 'datetime' 为 yyyyMMddHHmmss)
     * @param string $fieldName 字段名称
     * @throws DougongException 当格式错误时抛出异常
     */
    public static function validateDateFormat(string $date, string $format, string $fieldName): void
    {
        $pattern = $format === 'datetime' ? '/^\d{14}$/' : '/^\d{8}$/';
        $formatDesc = $format === 'datetime' ? 'yyyyMMddHHmmss' : 'yyyyMMdd';

        if (!preg_match($pattern, $date)) {
            throw new DougongException($fieldName . ' 格式错误，应为' . $formatDesc);
        }
    }

    /**
     * 验证字符串长度
     *
     * @param string $value 要验证的值
     * @param int $maxLength 最大长度
     * @param string $fieldName 字段名称
     * @throws DougongException 当长度超限时抛出异常
     */
    public static function validateStringLength(string $value, int $maxLength, string $fieldName): void
    {
        if (strlen($value) > $maxLength) {
            throw new DougongException($fieldName . ' 最大长度为' . $maxLength . '位');
        }
    }

    /**
     * 验证交易金额格式
     *
     * @param string $amount 交易金额
     * @throws DougongException 当格式错误时抛出异常
     */
    public static function validateTransAmount(string $amount): void
    {
        if (!is_numeric($amount) || (float)$amount < 0.01) {
            throw new DougongException('trans_amt 必须为数字且最低0.01元');
        }
    }

    /**
     * 验证交易类型
     *
     * @param string $tradeType 交易类型
     * @throws DougongException 当类型无效时抛出异常
     */
    public static function validateTradeType(string $tradeType): void
    {
        $validTradeTypes = [
            'A_NATIVE', 'A_JSAPI', 'T_MINIAPP', 'T_JSAPI', 'U_NATIVE',
            'U_JSAPI', 'D_NATIVE', 'T_H5', 'T_APP', 'T_NATIVE'
        ];

        if (!in_array($tradeType, $validTradeTypes)) {
            throw new DougongException('trade_type 参数值无效，支持的类型：' . implode(', ', $validTradeTypes));
        }
    }

    /**
     * 验证枚举值
     *
     * @param string $value 要验证的值
     * @param array $validValues 有效值列表
     * @param string $fieldName 字段名称
     * @throws DougongException 当值无效时抛出异常
     */
    public static function validateEnum(string $value, array $validValues, string $fieldName): void
    {
        if (!in_array($value, $validValues)) {
            throw new DougongException($fieldName . ' 参数值无效，仅支持' . implode('或', $validValues));
        }
    }

    /**
     * 验证必填参数
     *
     * @param array $data 数据数组
     * @param array $requiredFields 必填字段映射 ['field' => '字段描述']
     * @throws DougongException 当必填参数缺失时抛出异常
     */
    public static function validateRequiredFields(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field => $desc) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                throw new DougongException($desc . ' 参数必填');
            }
        }
    }

    /**
     * 验证条件必填参数（多选一）
     *
     * @param array $data 数据数组
     * @param array $conditionalFields 条件字段列表
     * @param string $errorMessage 错误信息
     * @throws DougongException 当条件不满足时抛出异常
     */
    public static function validateConditionalRequired(array $data, array $conditionalFields, string $errorMessage): void
    {
        $hasValidParam = false;

        foreach ($conditionalFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null) {
                $hasValidParam = true;
                break;
            }
        }

        if (!$hasValidParam) {
            throw new DougongException($errorMessage);
        }
    }
}