<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\CheckoutManagement;

class ExpressCheckoutValidator implements \Clearpay\Clearpay\Model\Spi\CheckoutValidatorInterface
{
    private $config;

    public function __construct(
        \Clearpay\Clearpay\Model\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function validate(\Magento\Quote\Model\Quote $quote): void
    {
        $grandTotal = $quote->getBaseGrandTotal();
        if ($grandTotal < $this->config->getMinOrderTotal() ||
            $grandTotal > $this->config->getMaxOrderTotal()) {
            throw new \Magento\Framework\Validation\ValidationException(
                __('Order amount exceed Clearpay order limit.')
            );
        }
    }
}
