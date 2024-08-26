<?php

declare(strict_types=1);

namespace Clearpay\Clearpay\Plugin\Checkout\Block\Cart;

class Sidebar
{
    private \Clearpay\Clearpay\ViewModel\Container\Cta\Cta $ctaContainerViewModel;
    private \Clearpay\Clearpay\Model\Config $config;
    private \Clearpay\Clearpay\ViewModel\Container\ExpressCheckout\ExpressCheckout $expressCheckoutViewModel;

    public function __construct(
        \Clearpay\Clearpay\ViewModel\Container\Cta\Cta $ctaContainerViewModel,
        \Clearpay\Clearpay\Model\Config $config,
        \Clearpay\Clearpay\ViewModel\Container\ExpressCheckout\ExpressCheckout $expressCheckoutViewModel
    ) {
        $this->ctaContainerViewModel = $ctaContainerViewModel;
        $this->config = $config;
        $this->expressCheckoutViewModel = $expressCheckoutViewModel;
    }

    /**
     * @param string $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetJsLayout(\Magento\Checkout\Block\Cart\Sidebar $sidebar, $result): string
    {
        if (is_string($result) &&
            $this->config->getIsPaymentActive() &&
            $this->config->getMinOrderTotal() !== null &&
            $this->config->getMaxOrderTotal() !== null
        ) {
            $result = $this->ctaContainerViewModel->updateJsLayout(
                $result,
                !($this->config->getIsEnableCtaMiniCart()
                    && $this->ctaContainerViewModel->isContainerEnable()
                    && !$this->config->getIsEnableMiniCartHeadless())
            );
            $result = $this->expressCheckoutViewModel->updateJsLayout(
                $result,
                !($this->config->getIsEnableExpressCheckoutMiniCart() &&
                    $this->expressCheckoutViewModel->isContainerEnable()
                    && !$this->config->getIsEnableMiniCartHeadless())
            );
        }

        return $result;
    }
}
