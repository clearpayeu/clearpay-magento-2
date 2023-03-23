<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Request;

class ExpressCheckoutDataBuilder extends \Clearpay\Clearpay\Gateway\Request\Checkout\CheckoutDataBuilder
{
    /**
     * @var \Clearpay\Clearpay\Api\Data\Quote\ExtendedShippingInformationInterface
     */
    private $extendedShippingInformation;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Clearpay\Clearpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability,
        \Clearpay\Clearpay\Api\Data\Quote\ExtendedShippingInformationInterface $extendedShippingInformation
    ) {
        parent::__construct($url, $productRepository, $searchCriteriaBuilder, $checkCBTCurrencyAvailability);
        $this->extendedShippingInformation = $extendedShippingInformation;
    }

    public function build(array $buildSubject): array
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $buildSubject['quote'];
        $popupOriginUrl = $buildSubject['popup_origin_url'];
        $currentCurrencyCode = $quote->getQuoteCurrencyCode();
        $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
        $amount = $isCBTCurrencyAvailable ? $quote->getGrandTotal() : $quote->getBaseGrandTotal();
        $currencyCode = $isCBTCurrencyAvailable ? $currentCurrencyCode : $quote->getBaseCurrencyCode();
        $lastSelectedShippingRate = $this->extendedShippingInformation->getParam(
            $quote,
            \Afterpay\Afterpay\Api\Data\Quote\ExtendedShippingInformationInterface::LAST_SELECTED_SHIPPING_RATE
        );

        $data = [
            'mode' => 'express',
            'storeId' => $quote->getStoreId(),
            'amount' => [
                'amount' => $this->formatPrice($amount),
                'currency' => $currencyCode
            ],
            'merchant' => [
                'popupOriginUrl' => $popupOriginUrl
            ],
            'items' => $this->getItems($quote),
            'merchantReference' => $quote->getReservedOrderId(),
            'shippingOptionIdentifier' => $lastSelectedShippingRate
        ];

        if ($discounts = $this->getDiscounts($quote)) {
            $data['discounts'] = $discounts;
        }

        return $data;
    }
}
