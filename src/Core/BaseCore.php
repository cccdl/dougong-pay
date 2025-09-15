<?php

declare(strict_types=1);

namespace cccdl\DougongPay\Core;

use cccdl\DougongPay\Exception\DougongException;

class BaseCore
{
    protected $dougongConfig;

    public function __construct($dougongConfig)
    {
        if (!($dougongConfig instanceof DougongConfig)) {
            throw new DougongException('配置异常');
        }
        $this->dougongConfig = $dougongConfig;
    }
}