<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2019 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model;

/**
 * Class Response
 * @package Clearpay\Clearpay\Model
 */
class Response
{
    /**
     * constant variable
     */
    const RESPONSE_STATUS_SUCCESS   = 'SUCCESS';
    const RESPONSE_STATUS_CANCELLED = 'CANCELLED';
    const RESPONSE_STATUS_FAILURE   = 'FAILURE';

    /* Order payment statuses */
    const RESPONSE_STATUS_APPROVED = 'APPROVED';
    const RESPONSE_STATUS_PENDING  = 'PENDING';
    const RESPONSE_STATUS_FAILED   = 'FAILED';
    const RESPONSE_STATUS_DECLINED = 'DECLINED';
    
	const PAYMENT_STATUS_AUTH_APPROVED = 'AUTH_APPROVED';
	const PAYMENT_STATUS_CAPTURED = 'CAPTURED';
	const PAYMENT_STATUS_PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    protected $checkoutSession;
    protected $request;
    protected $orderService;
    protected $invoiceService;
    protected $transactionFactory;
    protected $clearpayApiPayment;
    protected $helper;
    protected $jsonHelper;
    protected $salesOrderConfig;
    protected $status;
    protected $_orderRepository;
    protected $_paymentRepository;
    protected $_transactionRepository;
    protected $_quoteRepository;
    protected $paymentCapture;

    /**
     * Response constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param Adapter\ClearpayPayment $clearpayApiPayment
     * @param \Clearpay\Clearpay\Model\Adapter\ClearpayPayment $clearpayApiPayment
     * @param \Clearpay\Clearpay\Helper\Data $helper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Sales\Model\Order\Config $salesOrderConfig
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Sales\Model\Order\Payment\Repository $paymentRepository
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteRepository
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Clearpay\Clearpay\Model\Adapter\ClearpayPayment $clearpayApiPayment,
        \Clearpay\Clearpay\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Sales\Model\Order\Config $salesOrderConfig,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Order\Payment\Repository $paymentRepository,
        \Magento\Quote\Model\ResourceModel\Quote $quoteRepository,
		\Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderPaymentCapture $paymentCapture
    ) {
        $this->objectManager = $objectManager;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->orderService = $orderService;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->clearpayApiPayment = $clearpayApiPayment;
        $this->helper = $helper;
        $this->jsonHelper = $jsonHelper;
        $this->salesOrderConfig = $salesOrderConfig;
        $this->_orderRepository = $orderRepository;
        $this->_paymentRepository = $paymentRepository;
        $this->_quoteRepository = $quoteRepository;
        $this->paymentCapture = $paymentCapture;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $response
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validCallback(\Magento\Sales\Model\Order $order, $response = [])
    {
        // check if no order given and no response status
        if (!array_key_exists('status', $response) || !array_key_exists('entity_id', $order->getData())) {
            return false;
        }

        // check if request not same as session i.e detetcted fraud
        $additionalInfo = $order->getPayment()->getAdditionalInformation();
        if ($this->request->getParam('orderToken') ===  $additionalInfo[\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN]) {
            return true;
        }
        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param bool $comment
     * @return $this
     */
    public function cancelOrder(\Magento\Sales\Model\Order $order, $comment = false)
    {
        if (!$order->isCanceled() &&
            $order->getState() !== \Magento\Sales\Model\Order::STATE_COMPLETE &&
            $order->getState() !== \Magento\Sales\Model\Order::STATE_CLOSED) {
            // perform this before order process or cancel
            $this->_beforeUpdateOrder($order);

            // perform adding comment
            if ($comment) {
                $order->addStatusHistoryComment($comment);
            }

            // then canceling it
            $order->cancel();
            $this->_orderRepository->save($order);

            // debug mode
            $this->helper->debug('Cancel order for Magento order ' . $order->getIncrementId());
        }
        return $this;
    }

    /**
     * Return product to cart
     *
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     */
    public function returnProductsToCart(\Magento\Sales\Model\Order $order)
    {
        //$quote = $this->objectManager->create('Magento\Quote\Model\Quote')->load($order->getQuoteId());
        $quote = $this->objectManager->create('Magento\Quote\Model\QuoteRepository')->get($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(1)->setReservedOrderId(null);
            $this->_quoteRepository->save($quote);
            $this->checkoutSession->replaceQuote($quote);

            // debug mode
            $this->helper->debug('Reactivate cart session for order ' . $order->getIncrementId());
        }
        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws bool
     */
    public function processSuccessPayment(\Magento\Sales\Model\Order $order, $orderId)
    {
        // if order has been process and possible timeout on first request
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PROCESSING) {
            return true;
        }

        $this->_beforeUpdateOrder($order);

        // make sure the order can be invoiced and correct order
        if ($order->canInvoice() && $this->_shouldInvoiced($order, $orderId)) {
            // adding order ID to payment and last transaction Id
            $this->updatePayment($order, $orderId);

            // only approved can create invoice
            switch ($this->status) {
                case \Clearpay\Clearpay\Model\Status::STATUS_APPROVED:
                    // create invoice and update order
                    $this->createInvoiceAndUpdateOrder($order, $orderId);
                    break;
                case \Clearpay\Clearpay\Model\Status::STATUS_PENDING:
                    $order->addStatusHistoryComment(__('Payment under review by Clearpay'));
                    $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
                    $order->setStatus('payment_review');
                    $this->_orderRepository->save($order);
                    break;
            }

            return true;
        }

        return false;
    }

    /**
     * On processing or canceling the order, payment_review cannot be changed.
     * Perform this task first before processing or canceling the order
     *
     * @param $order
     * @return $this
     */
    protected function _beforeUpdateOrder($order)
    {
        // change the order status if payment review
        if ($order->isPaymentReview()) {
            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->setStatus('pending_payment');
        }
        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderId
     */
    public function updatePayment(\Magento\Sales\Model\Order $order, $orderId)
    {
        // adding Clearpay order id to the payment
        $payment = $order->getPayment();
        $payment->setTransactionId($orderId);
        $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID, $orderId);
        // have save here to link clearpay order id right after checking the API
        $this->_paymentRepository->save($payment);
        
        // debug mode
        $this->helper->debug('Added Clearpay Payment ID ' . $orderId . ' for Magento order ' . $order->getIncrementId());
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws bool
     */
    public function createInvoiceAndUpdateOrder(\Magento\Sales\Model\Order $order, $orderId)
    {
        /**
         * Set the state of order to be processing, run in transaction along with creating invoice
         * Making sure the order won't change to processing if invoice not created.
         *
         * So then, cron will handle this gracefully.
         */
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
            ->setStatus($this->salesOrderConfig->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING));

        $order->addStatusHistoryComment(__('Payment approved by Clearpay'));

        // prepare invoice and generate it
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE); // set to be capture offline because the capture has been done previously
        $invoice->register();

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($order)
            ->addObject($invoice)
            ->addObject($invoice->getOrder())->save();

        // debug mode
        $this->helper->debug('Invoice created and update status for Magento order ' . $order->getIncrementId());
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function _shouldInvoiced(\Magento\Sales\Model\Order $order, $clearpayOrderId)
    {
        // if already has invoice
        if ($order->hasInvoices()) {
            return false;
        }

        // only process clearpay method
        if ($order->getPayment()->getMethod() !== \Clearpay\Clearpay\Model\Payovertime::METHOD_CODE) {
            return false;
        }

        // checking with API to make sure the payment exist with correct status in API
        $response = $this->clearpayApiPayment->getPayment($clearpayOrderId);
        $response = $this->jsonHelper->jsonDecode($response->getBody());

        $this->status = $response['status'];

        if ($response['token'] == $order->getPayment()->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN) &&
            ($response['status'] == \Clearpay\Clearpay\Model\Status::STATUS_APPROVED || $response['status'] == \Clearpay\Clearpay\Model\Status::STATUS_PENDING)
        ) {
            return true;
        }

        return false;
    }
	/**
     * @param InfoInterface $payment
     * @param float $amount
     * @return array
     */
	public function clearpayProcessRefund($payment, $amount,$additional_info = [])
	{
		$clearpayRefund   = false;
		$clearpayVoid     = false;
		$result           = [];
		$override         = [];
		$storeCredit      = 0.00;
		$orderId          = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID);
		
		if($orderId) {
			$order = $payment->getOrder();
			if($amount > 0){
				if ($payment->getOrder()->getStore()->getWebsiteId() > 1) {
					$override = ["website_id" => $order->getStore()->getWebsiteId()];
				}
			   
				$clearpayPaymentStatus = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS);
				
				if($clearpayPaymentStatus == self::PAYMENT_STATUS_CAPTURED){
					$clearpayRefund = true;
				}
				elseif($clearpayPaymentStatus == self::PAYMENT_STATUS_PARTIALLY_CAPTURED || $clearpayPaymentStatus == self::PAYMENT_STATUS_AUTH_APPROVED){	
					
					$orderTotal               = $order->getGrandTotal();
			        $storeCredit              = $order->getCustomerBalanceAmount();
					$giftCard                 = $order->getGiftCardsAmount();
					$shippingToTefund         = 0.00;
					$amountCaptured           = 0.00;
					$amountToCapture          = 0.00;
					$refundedDiscount         = 0.00;
					$orderDiscount			  = $storeCredit + $giftCard;
					$totalDiscountAmount      = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_DISCOUNT);
					$discountAmount           = $orderDiscount - $totalDiscountAmount;
					
					if(array_key_exists('amountCaptured',$additional_info)){
						$amountCaptured = $additional_info['amountCaptured'];
					}
					if(array_key_exists('captureShipment',$additional_info)){
						$captureShipment = $additional_info['captureShipment'];
					}
					
					if($captureShipment && $orderDiscount > 0){
						$refundedDiscount = $payment->getCreditmemo()->getCustomerBalanceAmount() + $payment->getCreditmemo()->getGiftCardsAmount();
					}
					$openToCaptureAmount   = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT);
					$rolloverRefund        = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_REFUND);
					$refundAmountAvailable = $orderTotal - $openToCaptureAmount;
					$openToCaptureAmount   = $openToCaptureAmount - $rolloverRefund ;
					
					
					if(number_format($amount - $orderTotal, 2, '.', '') == 0.00){
						if($openToCaptureAmount != $orderTotal){
							$amount = $amount - $openToCaptureAmount;
							$clearpayRefund = true;
						}
						$clearpayVoid = true;		
					}
					else
					{
						$amountToRefund = 0.00;
						if($amountCaptured > 1){
							if($amountCaptured > $refundAmountAvailable){
								$amountToRefund = ($amount - $refundAmountAvailable);
								$amount = $refundAmountAvailable;
							}
							else{
								$amountToRefund = ($amount - $amountCaptured);
								$amount = $amountCaptured;
							}
							
							$clearpayRefund = true;
						}
						else{
							$amountToRefund = $amount;
							$amount = 0.00;
 						}
						if($orderDiscount > 0 && $refundedDiscount > 0){
							
							$amountToCheck = $amountToRefund + $discountAmount;
							if(number_format($amountToCheck - $openToCaptureAmount, 2, '.', '') == 0.00){
								$amountToCapture = $discountAmount;
								$clearpayVoid = true;
							}
							elseif($amountToCheck > $openToCaptureAmount){
								$amountToCapture = $discountAmount - ($amountToCheck- $openToCaptureAmount);
								$clearpayVoid = true;
							}
						}
						
						if(number_format($amountToRefund - $openToCaptureAmount, 2, '.', '') == 0.00){
							$clearpayVoid = true;
						}
						elseif($amountToRefund < $openToCaptureAmount){
							if($order->getShipmentsCollection()->count()==0 && $captureShipment){
								$shippingToRefund     = $payment->getCreditmemo()->getShippingInclTax();
								$orderShippingAmount  = $order->getShippingInclTax();
								$amountInclShipping = $amountToRefund + ($orderShippingAmount - $shippingToRefund);
								
								if($shippingToRefund < $orderShippingAmount && (number_format($amountInclShipping -  $orderTotal, 2, '.', '') == 0.00|| number_format($amountInclShipping -  $openToCaptureAmount, 2, '.', '') == 0.00)){
									$merchant_order_id = $order->getIncrementId();
									$amountToCapture = $orderShippingAmount - $shippingToRefund;
				
									$clearpayVoid = true;										
								}
								else{
									$rolloverRefund = $rolloverRefund + $amountToRefund;
									$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_REFUND, number_format($rolloverRefund, 2, '.', ''));
									$result['success'] = true;
								}
							}
						}
						elseif($amountToRefund > $openToCaptureAmount){
							$amount = $amount + ($amountToRefund - $openToCaptureAmount);
							$clearpayRefund = true;
							$clearpayVoid = true;
						}
					}
				}
			}
			else{
				if($storeCredit){
					$storeCredit = $payment->getCreditmemo()->getCustomerBalanceAmount();
					$result['success'] = true;
				}
			}
			
			if($storeCredit){
				$order->setBaseCustomerBalanceRefunded($storeCredit);
			    $order->setCustomerBalanceRefunded($storeCredit);
			}

			//Refund reqest
			if($clearpayRefund && $amount > 0){
			
				$refundResponse = $this->clearpayApiPayment->refund(number_format($amount, 2, '.', ''),$orderId,$order->getOrderCurrencyCode(),$override);

				$refundResponse = $this->jsonHelper->jsonDecode($refundResponse->getBody());

				if (!empty($refundResponse['refundId'])) {
					$result['success'] = true;
					
				} else {
					$this->helper->debug('Clearpay API Error: ' . $refundResponse['message']);
					throw new \Magento\Framework\Exception\LocalizedException(__('Clearpay API Error: ' .$refundResponse['message']));
				}
			}
			if($clearpayVoid){
				if($amountToCapture > 0){
					$merchant_order_id = $order->getIncrementId();
					$totalAmount= [
						'amount'   => number_format($amountToCapture, 2, '.', ''),
						'currency' => $order->getOrderCurrencyCode()
					];
				  
					$captureResponse = $this->paymentCapture->send($totalAmount,$merchant_order_id,$orderId,$override);
					$captureResponse = $this->jsonHelper->jsonDecode($captureResponse->getBody());

					if(!array_key_exists("errorCode",$captureResponse)) {
						$result['success'] = true;
					}
					else{
						$this->helper->debug("Transaction Exception : " . json_encode($captureResponse));
						throw new \Magento\Framework\Exception\LocalizedException(__('Clearpay API Error: ' .$captureResponse['message']));
					}	
				}
				//Void request
				$voidResponse = $this->clearpayApiPayment->voidOrder($orderId,$override);
				$voidResponse = $this->jsonHelper->jsonDecode($voidResponse->getBody());
					
				if(!array_key_exists("errorCode",$voidResponse)) {
					$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS, $voidResponse['paymentState']);
					
					if(array_key_exists('openToCaptureAmount',$voidResponse) && !empty($voidResponse['openToCaptureAmount'])){
						$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT,$voidResponse['openToCaptureAmount']['amount']);
					}
					
					if($payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_REFUND) > 0){
						$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_REFUND, "0.00");
					}
					if($payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_DISCOUNT) > 0){
						$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_DISCOUNT, "0.00");
					}
					if($payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_AMOUNT) > 0){
						$payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_AMOUNT, "0.00");
					}
					$result['success'] = true;
				}
				else{
					$this->helper->debug("Transaction Exception : " . json_encode($voidResponse));
					throw new \Magento\Framework\Exception\LocalizedException(__('Clearpay API Error: ' .$voidResponse['message']));
				}
			}
        } 
		else {
			throw new \Magento\Framework\Exception\LocalizedException(__('There are no Clearpay payment linked to this order. Please use refund offline for this order.'));
        }
		return $result;
	}
}
