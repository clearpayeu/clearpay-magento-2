<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Spi;

interface StockItemsValidatorInterface
{
    /**
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function validate(\Magento\Sales\Model\Order\Shipment $shipment): void;
}
