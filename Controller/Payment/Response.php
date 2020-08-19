<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Controller\Payment;

use \Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class Response
 * @package Clearpay\Clearpay\Controller\Payment
 */
class Response extends \Magento\Framework\App\Action\Action
{
    const DEFAULT_REDIRECT_PAGE = 'checkout/cart';

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $_resultForwardFactory;
    protected $response;
    protected $_helper;
    protected $_checkoutSession;
    protected $_jsonHelper;
    protected $_clearpayConfig;
    protected $_directCapture;
    protected $_authRequest;
    protected $_tokenCheck;
    protected $_quoteManagement;
    protected $_transactionBuilder;
    protected $_orderSender;
    protected $_orderRepository;
    protected $_paymentRepository;
    protected $_transactionRepository;
	protected $_notifierPool;
	protected $_paymentCapture;
	protected $_quoteValidator;
	protected $_timezone;
	protected $_clearpayApiPayment;
	
    /**
     * Response constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param \Clearpay\Clearpay\Model\Response $response
     * @param \Clearpay\Clearpay\Helper\Data $helper
     * @param \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderDirectCapture $directCapture
     * @param \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderAuthRequest $authRequest
     * @param \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderTokenCheck $tokenCheck
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Clearpay\Clearpay\Model\Config\Payovertime $clearpayConfig
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Sales\Model\Order\Payment\Repository $paymentRepository
     * @param \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository
     * @param \Magento\Framework\Notification\NotifierInterface $notifierPool
     * @param \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderPaymentCapture $paymentCapture
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Clearpay\Clearpay\Model\Response $response,
        \Clearpay\Clearpay\Helper\Data $helper,
        \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderDirectCapture $directCapture,
        \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderAuthRequest $authRequest,
        \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderTokenCheck $tokenCheck,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Clearpay\Clearpay\Model\Config\Payovertime $clearpayConfig,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Order\Payment\Repository $paymentRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
		\Magento\Framework\Notification\NotifierInterface $notifierPool,
		\Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderPaymentCapture $paymentCapture,
		\Magento\Quote\Model\QuoteValidator $quoteValidator,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
		\Clearpay\Clearpay\Model\Adapter\ClearpayPayment $clearpayApiPayment
    ) {
        $this->_resultForwardFactory = $resultForwardFactory; 
		$this->response = $response;
		$this->_helper = $helper;
		$this->_checkoutSession = $checkoutSession;
        $this->_jsonHelper = $jsonHelper;
        $this->_directCapture = $directCapture;
		$this->_authRequest = $authRequest;
        $this->_tokenCheck = $tokenCheck;
        $this->_clearpayConfig = $clearpayConfig;
        $this->_quoteManagement = $quoteManagement;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_orderSender = $orderSender;
        $this->_orderRepository = $orderRepository;
        $this->_paymentRepository = $paymentRepository;
        $this->_transactionRepository = $transactionRepository;
        $this->_notifierPool = $notifierPool;
		$this->_paymentCapture = $paymentCapture;
		$this->_quoteValidator = $quoteValidator;
		$this->_timezone = $timezone;
		$this->_clearpayApiPayment = $clearpayApiPayment;
		
        parent::__construct($context);
    }

    /**
     * Actual action when accessing url
     */
    public function execute()
    {
        // debug mode
        $this->_helper->debug('Start \Clearpay\Clearpay\Controller\Payment\Response::execute() with request ' . $this->_jsonHelper->jsonEncode($this->getRequest()->getParams()));

        $query = $this->getRequest()->getParams();
        $order = $this->_checkoutSession->getLastRealOrder();

        // Check if not fraud detected not doing anything (let cron update the order if payment successful)
        if ($this->_clearpayConfig->getPaymentAction() == AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
            //Steven - Bypass the response and do capture
            $redirect = $this->_processAuthCapture($query);
        } elseif (!$this->response->validCallback($order, $query)) {
            $this->_helper->debug('Request redirect url is not valid.');
        }
        // debug mode
        $this->_helper->debug('Finished \Clearpay\Clearpay\Controller\Payment\Response::execute()');

        // Redirect to cart
        $this->_redirect($redirect);
    }

    private function _processAuthCapture($query)
    {
        $redirect = self::DEFAULT_REDIRECT_PAGE;
        try {
            switch ($query['status']) {
                case \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_CANCELLED:
                    $this->messageManager->addError(__('You have cancelled your Clearpay payment. Please select an alternative payment method.'));
                    break;
                case \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_FAILURE:
                    $this->messageManager->addError(__('Clearpay payment failure. Please select an alternative payment method.'));
                    break;
                case \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_SUCCESS:
                    //Steven - Capture here
                    $quote = $this->_checkoutSession->getQuote();

                    $payment = $quote->getPayment();
					
					$this->_quoteValidator->validateBeforeSubmit($quote);
                    
					$token = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);
                    $merchant_order_id = $quote->getReservedOrderId();

                    $response_check = $this->_tokenCheck->generate($token);
                    $response_check = $this->_jsonHelper->jsonDecode($response_check->getBody());

                    /**
                     * Validation to check between session and post request
                     */
                    if (!$response_check) {
                        // Check the order token being use
                        throw new \Magento\Framework\Exception\LocalizedException(__('There are issues when processing your payment. Invalid Token'));
                    } elseif ($merchant_order_id != $response_check['merchantReference']) {
                        // Check order id
                        throw new \Magento\Framework\Exception\LocalizedException(__('There are issues when processing your payment. Invalid Merchant Reference'));
                    } elseif (round($quote->getGrandTotal(), 2) != round($response_check['amount']['amount'], 2)) {
                        // Check the order amount
                        throw new \Magento\Framework\Exception\LocalizedException(__('There are issues when processing your payment. Invalid Amount'));
                    }

					if(!$this->_helper->getConfig('payment/clearpaypayovertime/payment_flow') || $this->_helper->getConfig('payment/clearpaypayovertime/payment_flow')=="immediate" || $quote->getIsVirtual()){
						
						$this->_helper->debug("Starting Payment Capture request.");
						$response = $this->_directCapture->generate($token, $merchant_order_id);
					}
					else{
						
						$this->_helper->debug("Starting Auth request.");
						$response = $this->_authRequest->generate($token, $merchant_order_id);
					}
					
					$response = $this->_jsonHelper->jsonDecode($response->getBody());

                    if (empty($response['status'])) {
                        $response['status'] = \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_DECLINED;
                        $this->_helper->debug("Transaction Exception (Empty Response): " . json_encode($response));
                    }

                    switch ($response['status']) {
                        case \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_APPROVED:
							$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID, $response['id']);
                            
							$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS,$response['paymentState']);
							
							if($response['paymentState']==\Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_AUTH_APPROVED && array_key_exists('events',$response)){
								try{
									$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::AUTH_EXPIRY,$this->_timezone->date($response['events'][0]['expires'])->format('Y-m-d H:i T'));
								}
								catch(\Exception $e){
									$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::AUTH_EXPIRY,$this->_timezone->date($response['events'][0]['expires'],null,false)->format('Y-m-d H:i T'));
									$this->_helper->debug($e->getMessage());
								}
							}
							
							$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT, array_key_exists('openToCaptureAmount',$response) && !empty($response['openToCaptureAmount']) ? $response['openToCaptureAmount']['amount'] : "0.00");

                            $this->_checkoutSession
                                ->setLastQuoteId($quote->getId())
                                ->setLastSuccessQuoteId($quote->getId())
                                ->clearHelperData();

                            //Store Customer email address in temporary variable
                            $customerEmailAddress=$quote->getCustomerEmail();
                            
                            // Create Order From Quote
                            
                            $quote->collectTotals();
                            
                            // Restore Customer email address if it becomes null/blank
                            if(empty($quote->getCustomerEmail())){
                                $quote->setCustomerEmail($customerEmailAddress);
                            }
                            //Catch the deadlock exception while creating the order and retry 3 times
                            
                            $tries = 0;
                            do
                            {
	                          $retry = false;
							  
	                          try{
								  $this->_helper->debug("Trying Order Creation. Try number:".$tries);
		                          $order = $this->_quoteManagement->submit($quote);
                              }
	                            catch (\Exception $e) {
								
		                         if (preg_match('/SQLSTATE\[40001\]: Serialization failure: 1213 Deadlock found/', $e->getMessage()) && $tries<2){
			                       $this->_helper->debug("Waiting for a second before retrying the Order Creation");
			                       $retry = true;
			                       sleep(1);
		                         } 
								 else{
									 //Reverse or void the order
									$orderId = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID);
									$paymentStatus = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS);
									
									if($paymentStatus == \Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_AUTH_APPROVED){
										$voidResponse = $this->_clearpayApiPayment->voidOrder($orderId);
										$voidResponse = $this->_jsonHelper->jsonDecode($voidResponse->getBody());
											
										if(!array_key_exists("errorCode",$voidResponse)) {
											$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS, $voidResponse['paymentState']);
											
											if(array_key_exists('openToCaptureAmount',$voidResponse) && !empty($voidResponse['openToCaptureAmount'])){
												$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT,$voidResponse['openToCaptureAmount']['amount']);
											}
											
                                            $this->_helper->debug('Order Exception : There was a problem with order creation. Clearpay Order ' .$orderId. ' Voided.'.$e->getMessage());
											throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem placing your order. Your Clearpay order ' .$orderId. ' is refunded.'));
										}
										else{
											$this->_helper->debug("Transaction Exception : " . json_encode($voidResponse));
											$this->_notifierPool->addMajor(
											'Clearpay Order Failed',
											'There was a problem with a Clearpay order. Order number : '.$response['id'].' and the merchant order number : '.$merchant_order_id,
											''
											);
											throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem placing your order.'));						
										}
									}
									else{
										$orderTotal = $quote->getGrandTotal();
										
										$refundResponse = $this->_clearpayApiPayment->refund(number_format($orderTotal, 2, '.', ''),$orderId,$quote->getQuoteCurrencyCode());

										$refundResponse = $this->_jsonHelper->jsonDecode($refundResponse->getBody());

										if (!empty($refundResponse['refundId'])) {
											$this->_helper->debug('Order Exception : There was a problem with order creation. Clearpay Order ' .$orderId. ' refunded.'.$e->getMessage());
											throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem placing your order. Your Clearpay order ' .$orderId. ' is refunded.'));
										} else {
											$this->_helper->debug("Transaction Exception : " . json_encode($refundResponse));
											$this->_notifierPool->addMajor(
											'Clearpay Order Failed',
											'There was a problem with a Clearpay order. Order number : '.$response['id'].' and the merchant order number : '.$merchant_order_id,
											''
											);
											throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem placing your order.'));
										}
									} 
								 }
	                            }
								$tries++;
                            }while($tries<3 && $retry);

                            if ($order) {
								
								$payment = $order->getPayment();
								
								if($payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS)==\Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_AUTH_APPROVED){
									$totalDiscount = $this->_calculateTotalDiscount($order);
									if($totalDiscount > 0){
										$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_DISCOUNT,$this->_calculateTotalDiscount($order));
									}
									$this->_captureVirtual($order,$payment);
								}
                                
							    $this->_checkoutSession->setLastOrderId($order->getId())
                                                   ->setLastRealOrderId($order->getIncrementId())
                                                   ->setLastOrderStatus($order->getStatus());

                                $this->_createTransaction($order, $response,$payment);
								
								$this->messageManager->addSuccess("Clearpay Transaction Completed");

								$redirect = 'checkout/onepage/success';
								
                            } else {
	                           $this->_helper->debug("Order Exception : There was a problem with order creation.");
                            }
                            break;
                        case \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_DECLINED:
                            $this->messageManager->addError(__('Clearpay payment declined. Please select an alternative payment method.'));
                            break;
                        default:
                            $this->messageManager->addError($response);
                            break;
                    }
                    break;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_helper->debug("Transaction Exception: " . $e->getMessage());
            $this->messageManager->addError(
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->_helper->debug("Transaction Exception: " . $e->getMessage());
            $this->messageManager->addError("There was a problem in placing your order.");
        }
        
        return $redirect;
    }

    private function _createTransaction($order = null, $paymentData = [],$payment=null)
    {
        try {
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );
 
            $message = __('The authorized amount is %1.', $formatedPrice);
            //get the object of builder class
            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData['id'])
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
 
            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $this->_paymentRepository->save($payment);
			
			$order->setBaseCustomerBalanceInvoiced(null);
			$order->setCustomerBalanceInvoiced(null);
            $this->_orderRepository->save($order);
			
            $transaction = $this->_transactionRepository->save($transaction);
 
            return  $transaction->getTransactionId();
        } catch (\Exception $e) {
            //log errors here
            $this->_helper->debug("Transaction Exception: There was a problem with creating the transaction. ".$e->getMessage());
        }
    }
	
	private function _captureVirtual($order = null,$payment = null)
	{
		$totalCaptureAmount = 0.00;
		
		foreach($order->getAllItems() as $items) {  			
			if($items->getIsVirtual()) {
				$itemPrice = ($items->getQtyOrdered() * $items->getPrice())+$items->getBaseTaxAmount();
				$totalCaptureAmount = $totalCaptureAmount + ($itemPrice - $items->getDiscountAmount());
			}
		}
		
		$totalDiscountAmount = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_DISCOUNT);
			
		if($totalDiscountAmount!=0){
			if($totalCaptureAmount >= $totalDiscountAmount){
				$totalCaptureAmount = $totalCaptureAmount - $totalDiscountAmount;
				$totalDiscountAmount = 0.00;
			}
			else if($totalCaptureAmount < $totalDiscountAmount){
				$totalDiscountAmount = $totalDiscountAmount  - $totalCaptureAmount;
				$totalCaptureAmount = 0.00;
				
			}
			$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_DISCOUNT, number_format($totalDiscountAmount, 2, '.', ''));
		}

		if($totalCaptureAmount >= 1){
			$clearpay_order_id = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID);
			$merchant_order_id = $order->getIncrementId();
			$currencyCode      = $order->getOrderCurrencyCode();
			
			$totalAmount= [
                        'amount'   => number_format($totalCaptureAmount, 2, '.', ''),
                        'currency' => $currencyCode
                    ];	
			
			$response = $this->_paymentCapture->send($totalAmount,$merchant_order_id,$clearpay_order_id);
			$response = $this->_jsonHelper->jsonDecode($response->getBody());
			
			if(!array_key_exists("errorCode",$response)) {
				$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS,$response['paymentState']);
				if(array_key_exists('openToCaptureAmount',$response) && !empty($response['openToCaptureAmount'])){
					$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT,$response['openToCaptureAmount']['amount']);
				}
			}
			else{
				$this->_helper->debug("Transaction Exception : " . json_encode($response));
			}
		}
		else{
			if($totalCaptureAmount < 1 && $totalCaptureAmount > 0){
				$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_AMOUNT, number_format($totalCaptureAmount, 2, '.', ''));
			}
		}
		
	}
	
   /*
    Calculate Total Discount for the given order
   */
    private function _calculateTotalDiscount($order){
	  $storeCredit    =  $order->getCustomerBalanceAmount();
	  $giftCardAmount =  $order->getGiftCardsAmount();
	  $totalDiscountAmount = $storeCredit + $giftCardAmount;
	  return number_format($totalDiscountAmount, 2, '.', '');
    }
}