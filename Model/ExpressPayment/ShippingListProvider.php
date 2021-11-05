<?php declare(strict_types=1);

/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.clearpay.com
 */

namespace Clearpay\Clearpay\Model\ExpressPayment;

class ShippingListProvider
{
    /**
     * @var \Magento\Checkout\Api\TotalsInformationManagementInterface
     */
    private $totalsInformationManagement;
    /**
     * @var \Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory
     */
    private $totalsInformationFactory;
    /**
     * @var \Clearpay\Clearpay\Model\Adapter\ClearpayExpressPayment
     */
    private $clearpayExpressPayment;
    /**
     * @var \Magento\Quote\Api\ShipmentEstimationInterface
     */
    private $shipmentEstimation;

    public function __construct(
        \Magento\Checkout\Api\TotalsInformationManagementInterface $totalsInformationManagement,
        \Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory $totalsInformationFactory,
        \Clearpay\Clearpay\Model\Adapter\ClearpayExpressPayment $clearpayExpressPayment,
        \Magento\Quote\Api\ShipmentEstimationInterface $shipmentEstimation
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
