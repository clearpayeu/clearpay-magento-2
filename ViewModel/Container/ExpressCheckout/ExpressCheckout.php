<?php declare(strict_types=1);

namespace Clearpay\Clearpay\ViewModel\Container\ExpressCheckout;

class ExpressCheckout extends \Clearpay\Clearpay\ViewModel\Container\Container
{
    public const COUNTRY_CURRENCY_MAP = [
        'AUD' => 'AU',
        'NZD' => 'NZ',
        'USD' => 'US',
        'CAD' => 'CA',
        'GBP' => 'GB'
    ];

    public function updateJsLayout(
        string $jsLayoutJson,
        bool $remove = false,
        string $containerNodeName = 'clearpay.express.checkout',
        array $config = []
    ): string {
        if (!$remove && $this->isContainerEnable()) {
            $config['minOrderTotal'] = $this->config->getMinOrderTotal();
            $config['maxOrderTotal'] = $this->config->getMaxOrderTotal();
            $config['countryCode'] = $this->getCountryCode();
        }
        return parent::updateJsLayout($jsLayoutJson, $remove, $containerNodeName, $config);
    }

    public function getCountryCode(): ?string
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        return static::COUNTRY_CURRENCY_MAP[$currencyCode] ?? null;
    }
}
