<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\Cron;

use \Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use \Clearpay\Clearpay\Helper\Data as ClearpayHelper;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use \Magento\Framework\Stdlib\DateTime\TimezoneInterface as Timezone;
use \Clearpay\Clearpay\Model\Payovertime as Payovertime;
use \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender as CreditmemoSender;
use \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader as CreditmemoLoader;
use \Magento\Framework\Registry as Registry;
use \Magento\Sales\Model\Order\Payment\Repository as PaymentRepository;
class Status
{
    protected $_storeManager;
    protected $_helper;
    protected $_jsonHelper;
    protected $_objectManagerInterface;
    protected $_clearpayConfig;
    protected $_timezone;
    protected $_payovertime;
    protected $_creditmemoSender;
    protected $_creditmemoLoader;
    protected $_registry;
    protected $_paymentRepository;

    public function __construct(
        StoreManagerInterface $storeManager,
        ClearpayHelper $helper,
        JsonHelper $jsonHelper,
        ObjectManagerInterface $objectManagerInterface,
		ClearpayConfig $clearpayConfig,
		OrderCollectionFactory $orderCollectionFactory,
		Timezone $timezone,
		Payovertime $payovertime,
		CreditmemoSender $creditmemoSender,
		CreditmemoLoader $creditmemoLoader,
		Registry $registry,
		PaymentRepository $paymentRepository
    ) {
        $this->_storeManager = $storeManager;
		$this->_helper = $helper;
        $this->_jsonHelper = $jsonHelper;
        $this->_objectManagerInterface = $objectManagerInterface;
        $this->_clearpayConfig = $clearpayConfig;
		$this->_orderCollectionFactory = $orderCollectionFactory;
		$this->_timezone = $timezone;
		$this->_payovertime = $payovertime;
		$this->_creditmemoSender = $creditmemoSender;
		$this->_creditmemoLoader = $creditmemoLoader;
		$this->_registry = $registry;
		$this->_paymentRepository = $paymentRepository;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        $websites = $this->_getWebsites();
		if ($websites && count($websites) > 1) {
            foreach ($websites as $key => $website) {
                $this->_updateOrders($website);
            }
        }
		$this->_helper->debug("Cron executed successfully");
    }

    /**
     * @return array
     */
    private function _getWebsites()
    {
        $websites = $this->_storeManager->getWebsites();
        return $websites;
    }

    /**
     * @return bool
     */
    private function _updateOrders($website)
    {
        
        $website_id = $website["website_id"];
		$now   = $this->_timezone->date();
		$now->setTimezone(new \DateTimeZone($this->_timezone->getConfigTimezone('website', $website["website_id"])));
		$fromCreatedDate = $this->_timezone->date(strtotime('-60 days', strtotime($now->format('Y-m-d H:i'))))->format('Y-m-d H:i');
		$toCreatedDate = $this->_timezone->date(strtotime('-1 days', strtotime($now->format('Y-m-d H:i'))))->format('Y-m-d H:i');

		if($this->_clearpayConfig->isActive([ "website_id" => $website_id ])){	
			
			 $collection = $this->_orderCollectionFactory->create()
			 ->addFieldToSelect('increment_id')
			 ->addFieldToFilter('created_at',
				['lteq' => $toCreatedDate]
				)
			 ->addFieldToFilter('created_at',
				['gteq' => $fromCreatedDate]
				)
			->addFieldToFilter('state',
				['eq' => 'processing']
				);
			
			$collection->getSelect()
			->join(
				["sop" => "sales_order_payment"],
				'main_table.entity_id = sop.parent_id',
				array('method','additional_information')
			)
			->where('sop.method = ?',\Clearpay\Clearpay\Model\Payovertime::METHOD_CODE);
			
			$collection->addAttributeToFilter('additional_information',array('like'=>'%'.\Clearpay\Clearpay\Model\Payovertime::AUTH_EXPIRY.'%'));
			$collection->setOrder(
				'created_at',
				'desc'
			);
			
			foreach($collection->getData() as $clearpayOrders){
				$itemToCredit     = [];
				$data             = [];
				$amountDifference = 0.00;
				try{
					$additionalInformation = $this->_jsonHelper->jsonDecode($clearpayOrders['additional_information']);
				}
				catch (\Exception $e) {
					$additionalInformation = [];
					$this->_helper->debug($e->getMessage());
				}
				
				
				if(array_key_exists('clearpay_auth_expiry_date',$additionalInformation) && array_key_exists('clearpay_open_to_capture_amount',$additionalInformation)){
					
					if($this->_timezone->date($additionalInformation['clearpay_auth_expiry_date'])->format('Y-m-d H:i') < $now->format('Y-m-d H:i') && $additionalInformation['clearpay_open_to_capture_amount'] > 0.00){
						
						$order    = $this->_objectManagerInterface->create('Magento\Sales\Model\Order')->loadByIncrementId($clearpayOrders['increment_id']);
						$payment  = $order->getPayment();
	
						if($order->getState() && $order->getStore()->getWebsiteId() == $website_id){
							//Get items to refund
							foreach($order->getItemsCollection() as $orderItem){
								if(!$orderItem->getParentItem() && !$orderItem->getIsVirtual()){
									$qtyShipped   = $orderItem->getQtyShipped();
									$qtyOrdered     = $orderItem->getQtyOrdered();
									$QtyRefunded    = $orderItem->getQtyRefunded();
									$itemLeftToShip = $qtyOrdered - ($qtyShipped + $QtyRefunded);
									if($itemLeftToShip > 0){
										$orderItemId = $orderItem->getItemId();
										$itemToCredit[$orderItemId] = ['qty'=>$itemLeftToShip];
									}	
								}
							}
							
							$data['items'] = $itemToCredit;
							$data['do_offline'] = 1;
						    $data['send_email'] = 1;
							$data['refund_customerbalance_return_enable'] = 0;
							
							if($order->getShipmentsCollection()->count()==0){
								$data['shipping_amount'] = $order->getBaseShippingAmount()-($order->getShippingRefunded());
							}
							else{
								$data['shipping_amount'] = '0';
							}
					

							try {
								$this->_creditmemoLoader->setOrderId($order->getId()); //pass order id
						    	$this->_creditmemoLoader->setCreditmemo($data);
								$creditmemo = $this->_creditmemoLoader->load();
								if ($creditmemo) {
									
									if (!$creditmemo->isValidGrandTotal()) {
										throw new \Magento\Framework\Exception\LocalizedException(
											__('The credit memo\'s total must be positive.')
										);
									}
									
									$grandTotal = $creditmemo->getGrandTotal();
									$amountDifference = number_format($additionalInformation['clearpay_open_to_capture_amount'] - $grandTotal, 2, '.', '');
									
								    //Adjust Refund
									if($amountDifference > 0.00){
										$creditmemo->setAdjustmentPositive($amountDifference);
										$creditmemo->setGrandTotal(number_format($grandTotal + $amountDifference, 2, '.', ''));
									}
					
									$creditmemoManagement = $this->_objectManagerInterface->create(\Magento\Sales\Api\CreditmemoManagementInterface::class);
									$creditmemo->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
									//create credit memo
									$creditmemoManagement->refund($creditmemo, (bool)$data['do_offline']);
									
									if (!empty($data['send_email']) && $data['send_email']) {
										$this->_creditmemoSender->send($creditmemo);
									}
									
									//change payment additional information
									if($payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_REFUND) > 0){
										$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_REFUND, "0.00");
									}
									
									if($payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_DISCOUNT) > 0){
										$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_DISCOUNT, "0.00");
									}
									
									if($payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_AMOUNT) > 0){
										$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_AMOUNT, "0.00");
									}
									
									$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT, "0.00");
									$clearpayPaymentStatus = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS);
									
									if($clearpayPaymentStatus == \Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_AUTH_APPROVED){
										$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS, \Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_VOIDED);
									}
									elseif($clearpayPaymentStatus == \Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_PARTIALLY_CAPTURED){
										$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS, \Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_CAPTURED);
									}
									
									//save payment
									$this->_paymentRepository->save($payment);
									$this->_helper->debug("Creditmemo created for order number : ".$clearpayOrders['increment_id']);
									$this->_registry->unregister('current_creditmemo');
								}
								 
							} catch (\Magento\Framework\Exception\LocalizedException $e) {
							   $this->_helper->debug("Creditmemo Not Created ".$e->getMessage());
							} catch (\Exception $e) {
							   $this->_helper->debug("Creditmemo Not Created ".$e->getMessage());
							}
						}
					}
				}
			}
			return true;
		}
    }
}
