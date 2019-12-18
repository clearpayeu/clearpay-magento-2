<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2019 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Block\Catalog;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product as Product;
use Magento\Framework\Registry as Registry;
use Magento\Directory\Model\Currency as Currency;
use Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use Clearpay\Clearpay\Model\Payovertime as ClearpayPayovertime;
use Magento\Framework\Component\ComponentRegistrar as ComponentRegistrar;

class Installments extends Template
{
    /**
     * @var Product
     */
    protected $product;
    protected $registry;
    protected $currency;
    protected $clearpayConfig;
    protected $clearpayPayovertime;
    protected $componentRegistrar;

    /**
     * Installments constructor.
     * @param Template\Context $context
     * @param Product $product
     * @param Registry $registry
     * @param Currency $currency
     * @param ClearpayConfig $clearpayConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Product $product,
        Registry $registry,
        Currency $currency,
        ClearpayConfig $clearpayConfig,
        ClearpayPayovertime $clearpayPayovertime,
        ComponentRegistrar $componentRegistrar,
        array $data
    ) {
        $this->product = $product;
        $this->registry = $registry;
        $this->currency = $currency;
        $this->clearpayConfig = $clearpayConfig;
        $this->clearpayPayovertime = $clearpayPayovertime;
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
     * @return string
     */
    public function getInstallmentsAmount()
    {
        // get product
        $product = $this->registry->registry('product');

        // set if final price is exist
        if ($price = $product->getFinalPrice()) {
            return $this->currency->getCurrencySymbol() . number_format($price / 4, 2);
        }
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
		else{
			if($this->clearpayConfig->getCurrencyCode()){
				if($this->clearpayPayovertime->canUseForCurrency($this->clearpayConfig->getCurrencyCode())){
					$excluded_categories=$this->clearpayConfig->getExcludedCategories();
					if($excluded_categories!=""){
						$excluded_categories_array =  explode(",",$excluded_categories);
						$product = $this->registry->registry('product');
						$categoryids = $product->getCategoryIds();
						foreach($categoryids as $k)
						{
							if(in_array($k,$excluded_categories_array)){
								return false;
							}
						}
					}
					return true;				
				}
				else{
					return false;
				}
			} 
			else {
				return false;
			}
		}
    }
	/**
     * @return bool
     */
	public function isProductEligible(){
	
		$product = $this->registry->registry('product');
		if ($product->getFinalPrice() > $this->clearpayConfig->getMaxOrderLimit() // greater than max order limit
                || $product->getFinalPrice() < $this->clearpayConfig->getMinOrderLimit()) { // lower than min order limit
           return false;
        }
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
     * Calculate region specific Instalment Text
     * @return string
     */
    public function getInstalmentText()
    {
        $currencyCode = $this->clearpayConfig->getCurrencyCode();
        $assetsPath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Clearpay_Clearpay');
        $assets_product_page = [];
        if(file_exists($assetsPath.'/assets.ini'))
            {
                $assets = parse_ini_file($assetsPath.'/assets.ini',true);
                if(isset($assets[$currencyCode]['product_page1']))
                {
                    $assets_product_page['snippet1'] = $assets[$currencyCode]['product_page1'];
					if($this->getTypeOfProduct()=="bundle"){
						$assets_product_page['snippet1'] = $assets[$currencyCode]['product_page_from'];
					}  
                    $assets_product_page['snippet2'] = $assets[$currencyCode]['product_page2'];
                } else {
                    $assets_product_page['snippet1'] = '';
                    $assets_product_page['snippet2'] = '';
                }
            } 
           return $assets_product_page;
    }

    
    /**
     * @return float
     */
    public function getMaxOrderLimit()
    {
        return $this->clearpayConfig->getMaxOrderLimit();
    }

    /**
     * @return float
     */
    public function getMinOrderLimit()
    {
        return $this->clearpayConfig->getMinOrderLimit();
    }

	/**
     * @return string
     */
    public function getTypeOfProduct()
    {
        $product = $this->registry->registry('product');
		return $product->getTypeId();
    }
}
