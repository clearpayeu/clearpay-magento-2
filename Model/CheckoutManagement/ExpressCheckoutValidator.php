<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\CheckoutManagement;

class ExpressCheckoutValidator implements \Clearpay\Clearpay\Model\Spi\CheckoutValidatorInterface
{
    private \Clearpay\Clearpay\Model\Config $config;
    private \Clearpay\Clearpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider;

    public function __construct(
        \Clearpay\Clearpay\Model\Config $config,
        \Clearpay\Clearpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider
    ) {
        $this->config = $config;
        $this->notAllowedProductsProvider = $notAllowedProductsProvider;
    }

    /**
     * @inheritDoc
     */
    public function validate(\Magento\Quote\Model\Quote $quote): void
    {
        $restrictedSkus = $this->notAllowedProductsProvider->getRestrictedSkusFromQuote($quote);
        sort($restrictedSkus);

        if ($restrictedSkus) {
            throw new RestrictedProductsException(
                __(
                    'Clearpay Express Checkout is unavailable because your cart contains restricted products: %1. '
                    . 'Remove these products from your cart and try again.',
                    implode(', ', $restrictedSkus)
                ),
                $restrictedSkus
            );
        }

        $grandTotal = $quote->getBaseGrandTotal();
        if ($grandTotal < $this->config->getMinOrderTotal() ||
            $grandTotal > $this->config->getMaxOrderTotal()) {
            throw new \Magento\Framework\Validation\ValidationException(
                __('Order amount exceed Clearpay order limit.')
            );
        }
    }
}
