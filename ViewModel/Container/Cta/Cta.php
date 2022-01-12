<?php declare(strict_types=1);

namespace Clearpay\Clearpay\ViewModel\Container\Cta;

use Magento\Store\Model\Store;

class Cta extends \Clearpay\Clearpay\ViewModel\Container\Container
{
    private $storeManager;
    private $localeResolver;

    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Clearpay\Clearpay\Model\Config $config,
        \Clearpay\Clearpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\Resolver $localeResolver
    ) {
        parent::__construct($serializer, $config, $notAllowedProductsProvider);
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
    }

    public function updateJsLayout(
        string $jsLayoutJson,
        bool $remove = false,
        string $containerNodeName = 'clearpay.cta',
        array $config = []
    ): string {
        if (!$remove && $this->isContainerEnable()) {
            $store = $this->storeManager->getStore();
            $config['dataCurrency'] = $store->getCurrentCurrencyCode();
            $config['dataLocale'] = $this->localeResolver->getLocale();
            $config['dataShowLowerLimit'] = $this->config->getMinOrderTotal() >= 1;
            $config['dataCbtEnabled'] = count($this->config->getSpecificCountries()) > 1;
        }
        return parent::updateJsLayout($jsLayoutJson, $remove, $containerNodeName, $config);
    }
}
