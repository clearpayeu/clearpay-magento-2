<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Http\TransferFactory;

class UserAgentProvider
{
    private \Magento\Framework\Module\ModuleListInterface $moduleList;
    private \Magento\Framework\App\ProductMetadataInterface $productMetadata;
    private \Magento\Framework\Util $util;
    private \Clearpay\Clearpay\Model\Config $config;
    private \Magento\Store\Model\Store $store;

    public function __construct(
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Util $util,
        \Clearpay\Clearpay\Model\Config $config,
        \Magento\Store\Model\Store $store
    ) {
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->util = $util;
        $this->config = $config;
        $this->store = $store;
    }

    public function provide(?int $websiteId = null): string
    {
        $clearpayModule = $this->moduleList->getOne('Clearpay_Clearpay');
        $moduleVersion = $clearpayModule['setup_version'] ?? null;
        $magentoProductName = $this->productMetadata->getName();
        $magentoProductEdition = $this->productMetadata->getEdition();
        $magentoVersion = $this->productMetadata->getVersion();
        $phpVersion = $this->util->getTrimmedPhpVersion();
        $clearpayMerchantId = $this->config->getMerchantId($websiteId);
        $clearpayMPId = $this->config->getPublicId($websiteId);
        $websiteDomain = $this->store->getBaseUrl();

        return "ClearpayMagento2Plugin $moduleVersion ($magentoProductName $magentoProductEdition $magentoVersion) " .
            "PHPVersion: PHP/$phpVersion MerchantID: $clearpayMerchantId; MPID/$clearpayMPId; URL: $websiteDomain";
    }
}
