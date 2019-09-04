<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2019 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Block\Cart;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\Currency as Currency;
use Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use Clearpay\Clearpay\Model\Payovertime as ClearpayPayovertime;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Component\ComponentRegistrar as ComponentRegistrar;

class Button extends Template
{
    /**
     * @var ClearpayConfig
     */
    protected $clearpayConfig;
    protected $clearpayPayovertime;
    protected $checkoutSession;
    protected $currency;
    protected $customerSession;
    protected $componentRegistrar;

    /**
     * Button constructor.
     * @param Template\Context $context
     * @param ClearpayConfig $clearpayConfig
     * @param CheckoutSession $checkoutSession
     * @param Currency $currency
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ClearpayConfig $clearpayConfig,
        ClearpayPayovertime $clearpayPayovertime,
        CheckoutSession $checkoutSession,
        Currency $currency,
        CustomerSession $customerSession,
        ComponentRegistrar $componentRegistrar,
        array $data
    ) {
        $this->clearpayConfig = $clearpayConfig;
        $this->clearpayPayovertime = $clearpayPayovertime;
        $this->checkoutSession = $checkoutSession;
        $this->currency = $currency;
        $this->customerSession = $customerSession;
        $this->componentRegistrar = $componentRegistrar;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    protected function _getPaymentIsActive()
    {
        return $this->clearpayConfig->isActive();
    }

    /**
     * @return float
     */
    public function getInstallmentsTotal()
    {
        $quote = $this->checkoutSession->getQuote();

        if ($grandTotal = $quote->getGrandTotal()) {
            return $grandTotal / 4;
        }
    }

    /**
     * @return string
     */
    public function getInstallmentsTotalHtml()
    {
        return $this->getCurrency()->getCurrencySymbol() . number_format($this->getInstallmentsTotal(), 2);
    }

    /**
     * @return Currency
     */
    protected function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return bool
     */
    public function canShow()
    {
        // check if payment is active
        if (!$this->_getPaymentIsActive()) {
            return false;
        }

        // get grand total (final amount need to be paid)
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();

        // check if total is still in limit range
        if ($this->clearpayConfig->getMaxOrderLimit() < $grandTotal // greater than max order total
            || $this->clearpayConfig->getMinOrderLimit() > $grandTotal) { // lower than min order total
            return false;
        }

        // all ok
        return true;
    }

    /**
     * @return boolean
     */
    public function canUseCurrency()
    {
        //Check for Supported currency
        if($this->clearpayConfig->getCurrencyCode())
        {
            return $this->clearpayPayovertime->canUseForCurrency($this->clearpayConfig->getCurrencyCode());
        } else {
            return false;
        }
    }

    /**
     * Calculate region specific Instalment Text for Cart page
     * @return string
     */
    public function getCartPageText()
    {
        $currencyCode = $this->clearpayConfig->getCurrencyCode();
        $assetsPath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Clearpay_Clearpay');
        $assets_cart_page = '';

        if(file_exists($assetsPath.'/assets.ini'))
        {
            $assets = parse_ini_file($assetsPath.'/assets.ini',true);
            if(isset($assets[$currencyCode]['cart_page']))
            {
                $assets_cart_page = $assets[$currencyCode]['cart_page'];
                $assets_cart_page = str_replace(array('[modal-href]'), 
                    array('javascript:void(0)'), $assets_cart_page);
            } 
        } 
        return $assets_cart_page;
    }
}
