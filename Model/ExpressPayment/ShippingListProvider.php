<?php declare(strict_types=1);

/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.clearpay.com
 */

namespace Clearpay\Clearpay\Model\ExpressPayment;

use Clearpay\Clearpay\Model\Adapter\ClearpayExpressPayment;
use Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory;
use Magento\Checkout\Api\TotalsInformationManagementInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;

class ShippingListProvider
{
    /**
     * @var TotalsInformationManagementInterface
     */
    private $totalsInformationManagement;
    /**
     * @var TotalsInformationInterfaceFactory
     */
    private $totalsInformationFactory;
    /**
     * @var ClearpayExpressPayment
     */
    private $clearpayExpressPayment;
    /**
     * @var ShipmentEstimationInterface
     */
    private $shipmentEstimation;

    public function __construct(
        TotalsInformationManagementInterface $totalsInformationManagement,
        TotalsInformationInterfaceFactory $totalsInformationFactory,
        ClearpayExpressPayment $clearpayExpressPayment,
        ShipmentEstimationInterface $shipmentEstimation
    ) {
        $this->totalsInformationManagement = $totalsInformationManagement;
        $this->totalsInformationFactory = $totalsInformationFactory;
        $this->clearpayExpressPayment = $clearpayExpressPayment;
        $this->shipmentEstimation = $shipmentEstimation;
    }

    public function provide(\Magento\Quote\Model\Quote $quote): array
    {
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress(
            $quote->getId(),
            $quote->getShippingAddress()
        );
        $shippingList = [];
        foreach ($shippingMethods as $shippingMethod) {
            if (!$shippingMethod->getAvailable()) {
                continue;
            }

            /** @var \Magento\Checkout\Api\Data\TotalsInformationInterface $totalsInformation */
            $totalsInformation = $this->totalsInformationFactory->create()
                ->setAddress($quote->getShippingAddress())
                ->setShippingCarrierCode($shippingMethod->getCarrierCode())
                ->setShippingMethodCode($shippingMethod->getMethodCode());

            $quote->setTotalsCollectedFlag(false);
            $calculatedTotals = $this->totalsInformationManagement->calculate($quote->getId(), $totalsInformation);

            if ($this->clearpayExpressPayment->isValidOrderAmount($calculatedTotals->getBaseGrandTotal())) {
                $shippingList[] = $this->createShippingOptionByMethod($shippingMethod, $quote, $calculatedTotals);
            }
        }
        return $shippingList;
    }

    private function createShippingOptionByMethod(
        \Magento\Quote\Api\Data\ShippingMethodInterface $shippingMethod,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\TotalsInterface $totals
    ): array {
        return [
            'id' => $shippingMethod->getCarrierCode() . "_" . $shippingMethod->getMethodCode(),
            'name' => $shippingMethod->getCarrierTitle(),
            'description' => $shippingMethod->getCarrierTitle(),
            'shippingAmount' => [
                'amount' => $this->clearpayExpressPayment->formatAmount($totals->getBaseShippingAmount()),
                'currency' => $quote->getStoreCurrencyCode()
            ],
            'taxAmount' => [
                'amount' => $this->clearpayExpressPayment->formatAmount($totals->getBaseTaxAmount()),
                'currency' => $quote->getStoreCurrencyCode()
            ],
            'orderAmount' => [
                'amount' => $this->clearpayExpressPayment->formatAmount($totals->getBaseGrandTotal()),
                'currency' => $quote->getStoreCurrencyCode()
            ]
        ];
    }
}
