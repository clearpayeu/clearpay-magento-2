<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\StockItemsValidator;

use Magento\Framework\App\ObjectManager;

class StockItemsValidatorProxy implements \Clearpay\Clearpay\Model\Spi\StockItemsValidatorInterface, \Magento\Framework\ObjectManager\NoninterceptableInterface// @codingStandardsIgnoreLine
{
    private ?\Clearpay\Clearpay\Model\Spi\StockItemsValidatorInterface $subject = null;
    private \Clearpay\Clearpay\Gateway\Validator\StockItemsValidatorFactory $stockItemValidatorFactory;
    private \Clearpay\Clearpay\Model\SourceValidatorServiceFactory $sourceValidatorServiceFactory;
    private \Magento\Framework\Module\Manager $moduleManager;

    public function __construct(
        \Clearpay\Clearpay\Gateway\Validator\StockItemsValidatorFactory $stockItemsValidatorFactory,
        \Clearpay\Clearpay\Model\SourceValidatorServiceFactory $sourceValidatorServiceFactory,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->stockItemValidatorFactory = $stockItemsValidatorFactory;
        $this->sourceValidatorServiceFactory = $sourceValidatorServiceFactory;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Check msi functionality existing if no then skip validation
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function validate(\Magento\Sales\Model\Order\Shipment $shipment): void
    {
        if (!$this->moduleManager->isEnabled('Magento_InventoryCatalogApi') ||
            !$this->moduleManager->isEnabled('Magento_InventoryShipping') ||
            !$this->moduleManager->isEnabled('Magento_InventorySourceDeductionApi') ||
            !$this->moduleManager->isEnabled('Magento_InventoryConfigurationApi') ||
            !$this->moduleManager->isEnabled('Magento_InventorySalesApi')
        ) {
            return ;
        }
        $stockItemsValidator = $this->getStockItemValidator();
        $stockItemsValidator->validate($shipment);
    }

    private function getStockItemValidator(): \Clearpay\Clearpay\Model\Spi\StockItemsValidatorInterface
    {
        if ($this->subject == null) {
            $objectManager = ObjectManager::getInstance();
            $sourceValidatorService = $this->sourceValidatorServiceFactory->create([
               'getSourceItemBySourceCodeAndSku' => $objectManager->create('\\Magento\\InventorySourceDeductionApi\\Model\\GetSourceItemBySourceCodeAndSku'), // @codingStandardsIgnoreLine
               'getStockItemConfiguration' => $objectManager->create('\\Magento\\InventoryConfigurationApi\\Api\\GetStockItemConfigurationInterface'), // @codingStandardsIgnoreLine
               'getStockBySalesChannel' => $objectManager->create('\\Magento\\InventorySalesApi\\Api\\GetStockBySalesChannelInterface'),
            ]);
            $this->subject = $this->stockItemValidatorFactory->create([
                'isSingleSourceMode' => $objectManager->create('\\Magento\\InventoryCatalogApi\\Model\\IsSingleSourceModeInterface'),  // @codingStandardsIgnoreLine
                'defaultSourceProvider' => $objectManager->create('\\Magento\\InventoryCatalogApi\\Api\\DefaultSourceProviderInterface'),  // @codingStandardsIgnoreLine
                'getItemsToDeductFromShipment' => $objectManager->create('\\Magento\\InventoryShipping\\Model\\GetItemsToDeductFromShipment'),  // @codingStandardsIgnoreLine
                'sourceDeductionRequestFromShipmentFactory' => $objectManager->create('\\Magento\\InventoryShipping\\Model\\SourceDeductionRequestFromShipmentFactory'),  // @codingStandardsIgnoreLine
                'sourceValidatorService' => $sourceValidatorService,
            ]);
        }
        return $this->subject;
    }
}
