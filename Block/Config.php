<?php

namespace Clearpay\Clearpay\Block;

use Magento\Framework\View\Element\Template;
use Clearpay\Clearpay\Model\Config\Payovertime;
use Clearpay\Clearpay\Model\Payovertime as ClearpayPayovertime;
use Magento\Framework\Json\Helper\Data;

class Config extends Template
{
    /**
     * @var Payovertime $_payOverTime
     */
    protected $_payOverTime;

    /**
     * @var Data $_dataHelper
     */
    protected $_dataHelper;
    private $localeResolver;
    /**
     * Config constructor.
     *
     * @param Payovertime $payovertime
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Payovertime $payovertime,
        Data $dataHelper,
        Template\Context $context,
        ClearpayPayovertime $clearpayPayovertime,
        array $data,
        \Magento\Framework\Locale\Resolver $localeResolver
    ) {
    
        $this->_payOverTime = $payovertime;
        $this->_dataHelper = $dataHelper;
        $this->clearpayPayovertime = $clearpayPayovertime;
        $this->localeResolver = $localeResolver;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        return $this;
    }

    /**
     * Get URL to afterpay.js
     *
     * @return bool|string
     */
    public function getClearpayJsUrl()
    {
        return $this->_payOverTime->getWebUrl();
    }
	/**
     * @return bool
     */
	public function checkCurrency()
    {
        return $this->clearpayPayovertime->canUseForCurrency($this->_payOverTime->getCurrencyCode()) && $this->_payOverTime->isActive();
		
    }
    /**
     * Get Store Locale
     */
    public function getStoreLocale()
    {
        return $this->localeResolver->getLocale();
    }
    /**
     * Get URL to afterpay.js
     *
     * @return bool|string
     */
    public function getClearpayJsLibUrl()
    {
        return $this->_payOverTime->getJSLibUrl('afterpay-1.x.js');
    }
}
