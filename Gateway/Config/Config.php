<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const CODE = 'clearpay';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($scopeConfig, self::CODE);
    }
}
