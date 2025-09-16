<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Tools;

use cccdl\DougongPay\Core\BaseCore;
use cccdl\DougongPay\Exception\DougongException;

/**
 * 签名验签和加密解密工具类
 *
 * 提供斗拱支付接口所需的签名验签功能和敏感信息加密解密功能：
 * 1. 签名验签：用于接口请求和响应的安全验证
 * 2. 加密解密：用于处理银行卡号、手机号、身份证号等敏感信息
 *
 * 签名算法：RSA + SHA256
 * 加密算法：RSA
 * 编码格式：Base64
 */
class SignTool extends BaseCore
{
    /**
     * 对数据进行签名
     *
     * 用于斗拱支付接口请求时对data字段进行签名验证。
     * 签名流程：
     * 1. 对数据按key进行字典序排序
     * 2. JSON序列化（不转义斜杠和Unicode字符）
     * 3. 使用商户私钥进行SHA256签名
     * 4. Base64编码返回
     *
     * @param array $data 待签名的数据数组
     * @return string Base64编码的签名字符串
     * @throws DougongException 当私钥格式错误或签名失败时抛出异常
     *
     * @example
     * ```php
     * $signTool = new SignTool($config);
     * $data = ['req_seq_id' => '123', 'trans_amt' => '0.01'];
     * $signature = $signTool->sign($data);
     * ```
     */
    public function sign(array $data): string
    {
        ksort($data);
        $signString = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $privateKey = openssl_pkey_get_private($this->dougongConfig->rsaPrivateKey);
        if (!$privateKey) {
            throw new DougongException('私钥格式错误');
        }

        openssl_sign($signString, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);

        return base64_encode($signature);
    }

    /**
     * 验证同步返参签名
     *
     * 专用于验证斗拱支付接口同步返回数据的签名有效性。
     * 注意：此方法仅适用于同步返参，不适用于异步通知。
     * 异步通知请使用 verifyAsyncSign() 方法。
     *
     * 验签流程：
     * 1. 对数据按key进行字典序排序
     * 2. JSON序列化（不转义斜杠和Unicode字符）
     * 3. 使用斗拱公钥验证SHA256签名
     * 4. 返回验证结果
     *
     * @param array $data 待验证的数据数组
     * @param string $sign Base64编码的签名字符串
     * @return bool 验证结果，true为验证通过，false为验证失败
     * @throws DougongException 当公钥格式错误时抛出异常
     *
     * @example
     * ```php
     * $signTool = new SignTool($config);
     * $isValid = $signTool->verifySign($responseData, $responseSign);
     * if ($isValid) {
     *     // 同步返参签名验证通过，数据可信
     * }
     * ```
     */
    public function verifySign(array $data, string $sign): bool
    {
        ksort($data);
        $signString = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $publicKey = openssl_pkey_get_public($this->dougongConfig->rsaPublicKey);
        if (!$publicKey) {
            throw new DougongException('公钥格式错误');
        }

        $signature = base64_decode($sign);
        $result = openssl_verify($signString, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($publicKey);

        return $result === 1;
    }

    /**
     * 加密敏感信息
     *
     * 使用斗拱提供的公钥对敏感信息进行RSA加密。
     * 适用于以下敏感字段：
     * - 银行卡号 (bank_card_no)
     * - 手机号 (mobile_no)
     * - 身份证号 (cert_no)
     * - 银行卡CVV2 (cvv2)
     * - 银行卡有效期 (valid_date)
     *
     * 加密流程：
     * 1. 使用斗拱公钥进行RSA加密
     * 2. Base64编码返回
     *
     * @param string $content 待加密的明文内容
     * @return string Base64编码的加密结果
     * @throws DougongException 当公钥格式错误或加密失败时抛出异常
     *
     * @example
     * ```php
     * $signTool = new SignTool($config);
     * $encryptedCardNo = $signTool->encrypt('6214830100000000');
     * $encryptedMobile = $signTool->encrypt('13800138000');
     * ```
     */
    public function encrypt(string $content): string
    {
        $publicKey = openssl_pkey_get_public($this->dougongConfig->rsaPublicKey);
        if (!$publicKey) {
            throw new DougongException('公钥格式错误');
        }

        if (!openssl_public_encrypt($content, $encrypted, $publicKey)) {
            openssl_free_key($publicKey);
            throw new DougongException('加密失败');
        }

        openssl_free_key($publicKey);
        return base64_encode($encrypted);
    }

    /**
     * 验证异步通知签名
     *
     * 专用于验证斗拱支付异步通知的签名有效性。
     * 与同步返参不同，异步通知验签时不对数据排序，直接对原文进行验签。
     *
     * 验签流程：
     * 1. 直接对原始数据进行JSON序列化（不排序）
     * 2. 使用斗拱公钥验证SHA256签名
     * 3. 返回验证结果
     *
     * @param array $data 异步通知原始数据数组
     * @param string $sign Base64编码的签名字符串
     * @return bool 验证结果，true为验证通过，false为验证失败
     * @throws DougongException 当公钥格式错误时抛出异常
     *
     * @example
     * ```php
     * $signTool = new SignTool($config);
     * $notificationData = json_decode($postData, true);
     * $isValid = $signTool->verifyAsyncSign($notificationData, $signature);
     * if ($isValid) {
     *     // 异步通知签名验证通过
     * }
     * ```
     */
    public function verifyAsyncSign(array $data, string $sign): bool
    {
        // 异步通知不排序，直接对原文验签
        $signString = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $publicKey = openssl_pkey_get_public($this->dougongConfig->rsaPublicKey);
        if (!$publicKey) {
            throw new DougongException('公钥格式错误');
        }

        $signature = base64_decode($sign);
        $result = openssl_verify($signString, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($publicKey);

        return $result === 1;
    }

    /**
     * 解密敏感信息
     *
     * 使用商户私钥对斗拱返回的加密敏感信息进行RSA解密。
     * 通常用于解密接口返回结果中的敏感字段。
     *
     * 解密流程：
     * 1. Base64解码加密内容
     * 2. 使用商户私钥进行RSA解密
     * 3. 返回明文内容
     *
     * @param string $encryptedContent Base64编码的加密内容
     * @return string 解密后的明文内容
     * @throws DougongException 当私钥格式错误或解密失败时抛出异常
     *
     * @example
     * ```php
     * $signTool = new SignTool($config);
     * $decryptedCardNo = $signTool->decrypt($response['encrypted_bank_card_no']);
     * $decryptedMobile = $signTool->decrypt($response['encrypted_mobile_no']);
     * ```
     */
    public function decrypt(string $encryptedContent): string
    {
        $privateKey = openssl_pkey_get_private($this->dougongConfig->rsaPrivateKey);
        if (!$privateKey) {
            throw new DougongException('私钥格式错误');
        }

        $encrypted = base64_decode($encryptedContent);
        if (!openssl_private_decrypt($encrypted, $decrypted, $privateKey)) {
            openssl_free_key($privateKey);
            throw new DougongException('解密失败');
        }

        openssl_free_key($privateKey);
        return $decrypted;
    }
}