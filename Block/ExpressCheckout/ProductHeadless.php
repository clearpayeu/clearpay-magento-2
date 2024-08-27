<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Block\ExpressCheckout;

use Clearpay\Clearpay\Model\Config;
use Magento\Framework\View\Element\Template;

class ProductHeadless extends Template
{
    private Config $config;

    public function __construct(
        Template\Context $context,
        Config           $config,
        array            $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    protected function _toHtml()
    {
        /** @var \Clearpay\Clearpay\ViewModel\Container\ExpressCheckout\Headless $viewModel */
        $viewModel = $this->getViewModel();
        if ($viewModel && $viewModel->isContainerEnable()
            && $this->config->getIsEnableExpressCheckoutProductPage() && $this->config->getIsEnableProductPageHeadless()) {
            return parent::_toHtml();
        }

        return '';
    }
}