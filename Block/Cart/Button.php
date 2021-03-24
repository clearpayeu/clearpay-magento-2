<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Block\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;

use Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use Clearpay\Clearpay\Model\Payovertime as ClearpayPayovertime;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Locale\Resolver as Resolver;


class Button extends \Clearpay\Clearpay\Block\JsConfig
{
    /**
     * @var ClearpayConfig
     */
    protected $clearpayConfig;
    protected $clearpayPayovertime;
    protected $checkoutSession;
    protected $customerSession;

    /**
     * Button constructor.
     * @param Context $context
     * @param ClearpayConfig $clearpayConfig
     * @param ClearpayPayovertime $clearpayPayovertime
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param array $data
     * @param Resolver $localeResolver
     */
    public function __construct(
        Context $context,
        ClearpayConfig $clearpayConfig,
        ClearpayPayovertime $clearpayPayovertime,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        array $data=[],
        Resolver $localeResolver
    ) {
        $this->clearpayConfig = $clearpayConfig;
        $this->clearpayPayovertime = $clearpayPayovertime;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        parent::__construct($clearpayConfig,$context, $localeResolver,$data);
    }

    /**
     * @return bool
     */
    protected function _getPaymentIsActive()
    {
        return $this->clearpayConfig->isActive();
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
			//Check for Supported currency
			if($this->clearpayConfig->getCurrencyCode()){
				
				$quote = $this->checkoutSession->getQuote();
				// get grand total (final amount need to be paid)
				$grandTotal =$quote->getGrandTotal();
				$excluded_categories=$this->clearpayConfig->getExcludedCategories();
				
				if($this->clearpayPayovertime->canUseForCurrency($this->clearpayConfig->getCurrencyCode()) ){ 
					
					if($excluded_categories !=""){
						$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
						$productRepository = $objectManager->get('\Magento\Catalog\Model\ProductRepository');
						$excluded_categories_array =  explode(",",$excluded_categories);
						
						foreach ($quote->getAllVisibleItems() as $item) {
							$productid = $item->getProductId();
							$product=$productRepository->getById($productid);
							$categoryids = $product->getCategoryIds();
							
							foreach($categoryids as $k)
							{
								if(in_array($k,$excluded_categories_array)){
									return false;
								}
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
     * @return string
     */
    public function getFinalAmount()
    {
           
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
       
        return !empty($grandTotal)?number_format($grandTotal, 2,".",""):"0.00";
        
    }
    /* 
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
