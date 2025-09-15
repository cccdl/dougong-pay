<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Core;

class DougongConfig
{
    public $productId;
    public $sysId;
    public $rsaPrivateKey;
    public $rsaPublicKey;
    public $baseUri;

    public function __construct($config)
    {
        $this->productId = $config['product_id'];
        $this->sysId = $config['sys_id'];
        $this->rsaPrivateKey = $config['rsa_private_key'];
        $this->rsaPublicKey = $config['rsa_public_key'];
        $this->baseUri = $config['base_uri'] ?? 'https://api.huifu.com';
    }
}