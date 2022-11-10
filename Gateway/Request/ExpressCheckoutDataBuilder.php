<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Request;

class ExpressCheckoutDataBuilder extends \Clearpay\Clearpay\Gateway\Request\Checkout\CheckoutDataBuilder
{
    public function build(array $buildSubject): array
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $buildSubject['quote'];
        $popupOriginUrl = $buildSubject['popup_origin_url'];
        $currentCurrencyCode = $quote->getQuoteCurrencyCode();
        $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
        $amount = $isCBTCurrencyAvailable ? $quote->getGrandTotal() : $quote->getBaseGrandTotal();
        $currencyCode = $isCBTCurrencyAvailable ? $currentCurrencyCode : $quote->getBaseCurrencyCode();

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
            'merchantReference' => $quote->getReservedOrderId()
        ];

        if ($discounts = $this->getDiscounts($quote)) {
            $data['discounts'] = $discounts;
        }

        return $data;
    }
}
