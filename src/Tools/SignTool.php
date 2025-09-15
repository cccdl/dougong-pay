<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Tools;

use cccdl\DougongPay\Core\BaseCore;

class SignTool extends BaseCore
{
    public function sign(array $data): string
    {
        ksort($data);
        $signString = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $privateKey = openssl_pkey_get_private($this->dougongConfig->rsaPrivateKey);
        if (!$privateKey) {
            throw new \Exception('私钥格式错误');
        }

        openssl_sign($signString, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);

        return base64_encode($signature);
    }
}