<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Block\Cta;

use Clearpay\Clearpay\Model\Config;
use Magento\Framework\View\Element\Template;

class Cart extends Template
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
        if ($this->config->getIsEnableCtaCartPage() && !$this->config->getIsEnableCartPageHeadless()) {
            return parent::_toHtml();
        }

        return '';
    }
}
