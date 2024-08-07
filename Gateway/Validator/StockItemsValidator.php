<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Validator;

class StockItemsValidator implements \Clearpay\Clearpay\Model\Spi\StockItemsValidatorInterface
{
    private  $isSingleSourceMode;
    private  $sourceValidatorService;
    private  $shipmentFactory;
    private  $getItemsToDeductFromShipment;
    private  $defaultSourceProvider;

    /**
     * We avoid strict types in constructor for create instances dynamically look at
     * \Clearpay\Clearpay\Model\StockItemsValidator\StockItemsValidatorProxy
     * @param \Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface $isSingleSourceMode
     * @param \Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface $defaultSourceProvider
     * @param \Magento\InventoryShipping\Model\GetItemsToDeductFromShipment $getItemsToDeductFromShipment
     * @param \Magento\InventoryShipping\Model\SourceDeductionRequestFromShipmentFactory $shipmentFactory
     * @param \Clearpay\Clearpay\Model\Spi\SourceValidatorServiceInterface $sourceValidatorService
     */
    public function __construct(
        $isSingleSourceMode,
        $defaultSourceProvider,
        $getItemsToDeductFromShipment,
        $shipmentFactory,
        $sourceValidatorService
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->getItemsToDeductFromShipment = $getItemsToDeductFromShipment;
        $this->shipmentFactory = $shipmentFactory;
        $this->sourceValidatorService = $sourceValidatorService;
    }

    /**
     * @inheridoc
     */
    public function validate(\Magento\Sales\Model\Order\Shipment $shipment): void
    {
        if ($shipment->getOrigData('entity_id')) {
            return;
        }

        if (!empty($shipment->getExtensionAttributes())
            && !empty($shipment->getExtensionAttributes()->getSourceCode())) {
            $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        } elseif ($this->isSingleSourceMode->execute()) {
            $sourceCode = $this->defaultSourceProvider->getCode();
        }

        $shipmentItems = $this->getItemsToDeductFromShipment->execute($shipment);

        if (!empty($shipmentItems)) {
            $sourceDeductionRequest = $this->shipmentFactory->execute(
                $shipment,
                $sourceCode,
                $shipmentItems
            );
            $this->sourceValidatorService->execute($sourceDeductionRequest);
        }
    }
}
