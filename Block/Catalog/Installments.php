<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Block\Catalog;

use Clearpay\Clearpay\Block\JsConfig;
use Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use Clearpay\Clearpay\Model\Payovertime as ClearpayPayovertime;
use Magento\Framework\Locale\Resolver as Resolver;
use Magento\Framework\Registry as Registry;
use Magento\Framework\View\Element\Template\Context;

class Installments extends JsConfig
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ClearpayConfig
     */
    private $clearpayConfig;
    /**
     * @var ClearpayPayovertime
     */
    private $clearpayPayovertime;
    
    /**
     * @var Resolver
     */
    private $localeResolver;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ClearpayConfig $clearpayConfig
     * @param ClearpayPayovertime $clearpayPayovertime
     * @param array $data
     * @param Resolver $localeResolver
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ClearpayConfig $clearpayConfig,
        ClearpayPayovertime $clearpayPayovertime,
        array $data,
        Resolver $localeResolver
    ) {
        $this->registry = $registry;
        $this->clearpayConfig = $clearpayConfig;
        $this->clearpayPayovertime = $clearpayPayovertime;
        $this->localeResolver = $localeResolver;
        parent::__construct($clearpayConfig,$context, $localeResolver,$data);
    }

    /**
     * @return bool
     */
    public function canShow(): bool
    {
        // check if payment is active
        if ($this->_getPaymentIsActive() &&
            $this->clearpayConfig->getCurrencyCode() &&
            $this->clearpayPayovertime->canUseForCurrency($this->clearpayConfig->getCurrencyCode())
        ) {
            $excluded_categories = $this->clearpayConfig->getExcludedCategories();
            if ($excluded_categories != "") {
                $excluded_categories_array = explode(",", $excluded_categories);
                $product = $this->registry->registry('product');
                $categoryids = $product->getCategoryIds();
                foreach ($categoryids as $k) {
                    if (in_array($k, $excluded_categories_array)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTypeOfProduct()
    {
        $product = $this->registry->registry('product');
		return $product->getTypeId();
    }
    
    /**
     * @return string
     */
    public function getFinalAmount()
    {
        // get product
        $product = $this->registry->registry('product');
        
        // set if final price is exist
        $price = $product->getPriceInfo()->getPrice('final_price')->getValue();

        return !empty($price) ? number_format($price, 2, ".", "") : "0.00";
    }

    /**
     * @return boolean
     */
    public function canUseCurrency()
    {
        $canUse=false;
        //Check for Supported currency
        if($this->clearpayConfig->getCurrencyCode())
        {
            $canUse= $this->clearpayPayovertime->canUseForCurrency($this->clearpayConfig->getCurrencyCode());
        } 
        
        return $canUse;
        
    }
    
}
