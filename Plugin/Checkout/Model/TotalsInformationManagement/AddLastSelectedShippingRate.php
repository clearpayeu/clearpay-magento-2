<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Plugin\Checkout\Model\TotalsInformationManagement;

use Magento\Checkout\Api\Data\TotalsInformationInterface;

class AddLastSelectedShippingRate
{
    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    private $cartRepository;
    /** @var \Clearpay\Clearpay\Api\Data\Quote\ExtendedShippingInformationInterface */
    private $extendedShippingInformation;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Clearpay\Clearpay\Api\Data\Quote\ExtendedShippingInformationInterface $extendedShippingInformation
    ) {
        $this->cartRepository = $cartRepository;
        $this->extendedShippingInformation = $extendedShippingInformation;
    }

    public function beforeCalculate(
        \Magento\Checkout\Api\TotalsInformationManagementInterface $subject,
        $cartId,
        \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $shippingRate = '';

        if ($addressInformation->getShippingMethodCode() &&  $addressInformation->getShippingCarrierCode()) {
            $shippingRate = $addressInformation->getShippingMethodCode() . '_' . $addressInformation->getShippingCarrierCode();
        }

        if ($shippingRate) {
            $this->extendedShippingInformation->update(
                $quote,
                \Clearpay\Clearpay\Api\Data\Quote\ExtendedShippingInformationInterface::LAST_SELECTED_SHIPPING_RATE,
                $shippingRate
            );
        }

        return [$cartId, $addressInformation];
    }
}
