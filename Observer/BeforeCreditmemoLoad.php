<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\ClearpayEurope\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Clearpay\ClearpayEurope\Helper\Data as Helper;

/**
 * Class BeforeCreditmemoLoad
 * @package Clearpay\ClearpayEurope\Observer
 */
class BeforeCreditmemoLoad implements ObserverInterface
{ 
  protected $_helper;
  protected $_layout;
  protected $_registry;
  
  public function __construct(
	Helper $helper,
	\Magento\Framework\View\LayoutInterface $layout,
	\Magento\Framework\Registry $registry
  )
  {
    $this->_helper = $helper;
	$this->_layout = $layout;
	$this->_registry = $registry;
  }

 public function execute(\Magento\Framework\Event\Observer $observer)
  {
	$block = $observer->getEvent()->getBlock();
	$layout = $block->getLayout();

	if($layout->hasElement('sales_creditmemo_create')){
		$creditmemo = $this->_registry->registry('current_creditmemo');
		if($creditmemo){
			$order      = $creditmemo->getOrder();
			$payment    = $order->getPayment();
			
			if($payment->getMethod() == \Clearpay\ClearpayEurope\Model\Payovertime::METHOD_CODE ){
				$clearpayPaymentStatus = $payment->getAdditionalInformation(\Clearpay\ClearpayEurope\Model\Payovertime::PAYMENT_STATUS);
				if($clearpayPaymentStatus == \Clearpay\ClearpayEurope\Model\Response::PAYMENT_STATUS_AUTH_APPROVED || $clearpayPaymentStatus == \Clearpay\ClearpayEurope\Model\Response::PAYMENT_STATUS_PARTIALLY_CAPTURED){
					$block->unsetChild(
						'submit_offline'
					);
					if($layout->hasElement('customerbalance.creditmemo')){
						$layout->unsetElement('customerbalance.creditmemo');
					}
				}
			}
		}
	}
  }
}
?>