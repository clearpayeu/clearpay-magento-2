<?php declare(strict_types=1);

namespace Clearpay\Clearpay\ViewModel\Container;

class Container implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    protected const CONTAINERS_LAYOUT_KEYS = [
        'components',
        'children',
        'minicart_content',
        'extra_info'
    ];

    protected \Clearpay\Clearpay\Model\Config $config;
    protected \Magento\Framework\Serialize\SerializerInterface $serializer;
    protected \Clearpay\Clearpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider;

    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Clearpay\Clearpay\Model\Config $config,
        \Clearpay\Clearpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider
    ) {
        $this->serializer = $serializer;
        $this->config = $config;
        $this->notAllowedProductsProvider = $notAllowedProductsProvider;
    }

    public function isContainerEnable(): bool
    {
        return $this->config->getIsPaymentActive() &&
            $this->config->getMinOrderTotal() !== null &&
            $this->config->getMaxOrderTotal() !== null &&
            in_array($this->config->getMerchantCountry(), $this->config->getSpecificCountries());
    }

    public function updateJsLayout(
        string $jsLayoutJson,
        bool $remove = false,
        string $containerNodeName = 'clearpay.container',
        array $config = []
    ): string {
        /** @var array $jsLayout */
        $jsLayout = $this->serializer->unserialize($jsLayoutJson);
        $updatedJsLayout = $this->updateContainer($jsLayout, $remove, $containerNodeName, $config);
        $updatedJsLayout = $this->serializer->serialize($updatedJsLayout);
        return is_string($updatedJsLayout) ? $updatedJsLayout : $jsLayoutJson;
    }

    protected function updateContainer(array $jsLayout, bool $remove, string $containerNodeName, array $config): array
    {
        if (isset($jsLayout[$containerNodeName])) {
            if ($remove) {
                unset($jsLayout[$containerNodeName]);
                return $jsLayout;
            }
            if (!isset($jsLayout[$containerNodeName]['config'])) {
                $jsLayout[$containerNodeName]['config'] = [];
            }
            foreach ($config as $key => $value) {
                $jsLayout[$containerNodeName]['config'][$key] = $value;
            }
            $jsLayout[$containerNodeName]['config']['notAllowedProducts'] = $this->notAllowedProductsProvider
                ->provideIds();
            return $jsLayout;
        }

        foreach (self::CONTAINERS_LAYOUT_KEYS as $containerLayoutKey) {
            if (isset($jsLayout[$containerLayoutKey])) {
                $jsLayout[$containerLayoutKey] = $this->updateContainer(
                    $jsLayout[$containerLayoutKey],
                    $remove,
                    $containerNodeName,
                    $config
                );
                break;
            }
        }
        return $jsLayout;
    }
}
