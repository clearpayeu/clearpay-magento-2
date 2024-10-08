<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Block\Cta;

use Clearpay\Clearpay\Model\Config;
use Magento\Framework\View\Element\Template;

class MiniCartHeadless extends Template
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
        /** @var \Clearpay\Clearpay\ViewModel\Container\Cta\Headless $viewModel */
        $viewModel = $this->getViewModel();
        if ($viewModel && $viewModel->isContainerEnable() && $this->isEnabledForMinicart()) {
            return parent::_toHtml();
        }

        return '';
    }

    public function isEnabledForMinicart(): bool
    {
        return $this->config->getIsEnableCtaMiniCart() && $this->config->getIsEnableMiniCartHeadless();
    }
}
