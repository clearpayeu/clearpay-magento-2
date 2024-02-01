<?php

declare(strict_types=1);

namespace Clearpay\Clearpay\ViewModel\Container\Cta;

use Clearpay\Clearpay\Model\Config;
use Clearpay\Clearpay\Model\Url\Lib\LibUrlProvider;
use Magento\Store\Model\StoreManagerInterface;

class Lib extends \Clearpay\Clearpay\ViewModel\Container\Lib
{
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        Config                $config,
        LibUrlProvider        $libUrlProvider,
        ?string               $containerConfigPath = null
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($config, $libUrlProvider, $containerConfigPath);
    }

    public function getMinTotalValue(): ?string
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $cbtLimits = $this->config->getCbtCurrencyLimits();
        if (isset($cbtLimits[$currencyCode])) {
            return $cbtLimits[$currencyCode]['minimumAmount']['amount'] ?? '0';
        }

        return $this->config->getMinOrderTotal();
    }

    public function getMaxTotalValue(): ?string
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $cbtLimits = $this->config->getCbtCurrencyLimits();
        if (isset($cbtLimits[$currencyCode])) {
            return $cbtLimits[$currencyCode]['maximumAmount']['amount'];
        }

        return $this->config->getMaxOrderTotal();
    }
}
