<?php declare(strict_types=1);

namespace Clearpay\Clearpay\ViewModel\Container\ExpressCheckout;

use Clearpay\Clearpay\Model\Config;
use Clearpay\Clearpay\Model\Config\Source\ApiMode;
use Clearpay\Clearpay\Model\ResourceModel\NotAllowedProductsProvider;
use Clearpay\Clearpay\ViewModel\Container\Container;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;

class ExpressCheckout extends Container
{
    public const COUNTRY_CURRENCY_MAP = [
        'AUD' => 'AU',
        'NZD' => 'NZ',
        'USD' => 'US',
        'CAD' => 'CA',
        'GBP' => 'GB'
    ];

    protected $localeResolver;

    public function __construct(
        SerializerInterface $serializer,
        Config $config,
        NotAllowedProductsProvider $notAllowedProductsProvider,
        StoreManagerInterface $storeManager,
        Resolver $localeResolver
    ) {
        parent::__construct($serializer, $config, $notAllowedProductsProvider, $storeManager);
        $this->localeResolver = $localeResolver;
    }

    public function updateJsLayout(
        string $jsLayoutJson,
        bool   $remove = false,
        string $containerNodeName = 'clearpay.express.checkout',
        array  $config = []
    ): string {
        if (!$remove && $this->isContainerEnable()) {
            $config['minOrderTotal'] = $this->config->getMinOrderTotal();
            $config['maxOrderTotal'] = $this->config->getMaxOrderTotal();
            $config['countryCode'] = $this->getCountryCode();
            $config['buttonImageUrl'] = $this->getImageurl();
        }

        return parent::updateJsLayout($jsLayoutJson, $remove, $containerNodeName, $config);
    }

    public function getCountryCode(): ?string
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

        return static::COUNTRY_CURRENCY_MAP[$currencyCode] ?? null;
    }

    public function getImageurl(): string
    {
        $urlPrefix = $this->config->getApiMode() === ApiMode::SANDBOX ? 'static.sandbox' : 'static';
        $localePart = str_replace('_', '-', $this->localeResolver->getLocale());

        return "https://$urlPrefix.afterpay.com/$localePart/integration/button/checkout-with-clearpay/white-on-black.svg";
    }
}
