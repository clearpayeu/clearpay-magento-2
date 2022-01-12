<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteFactory as QuoteFactory;
use Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use Magento\Payment\Model\Method\AbstractMethod;
use Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderTokenV2 as ClearpayOrderTokenV2;
use Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderTokenCheck as TokenCheck;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Clearpay\Clearpay\Helper\Data as Helper;
use Magento\Quote\Model\ResourceModel\Quote as QuoteRepository;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Quote\Model\QuoteValidator as QuoteValidator;
use Zend\Form\Annotation\AbstractArrayAnnotation;
use Clearpay\Clearpay\Model\Adapter\ClearpayExpressPayment as ExpressPayment;

/**
 * Class Response
 *
 * @package Clearpay\Clearpay\Controller\Payment
 */
class Express extends \Magento\Framework\App\Action\Action
{

    protected $_objectManager;

    protected $_checkoutSession;

    protected $_quoteFactory;

    protected $_clearpayConfig;

    protected $_clearpayOrderTokenV2;

    protected $_tokenCheck;

    protected $_jsonHelper;

    protected $_helper;

    protected $_quoteRepository;

    protected $_jsonResultFactory;

    protected $_quoteValidator;

    protected $_directCapture;

    protected $_authRequest;

    protected $_clearpayApiPayment;

    protected $_quoteManagement;

    protected $_expressPayment;

    protected $_timezone;

    protected $shippingListProvider;

    /**
     * Response constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        CheckoutSession $checkoutSession,  
        QuoteFactory $quoteFactory, 
        ClearpayConfig $clearpayConfig, 
        ClearpayOrderTokenV2 $clearpayOrderTokenV2, 
        TokenCheck $tokenCheck, 
        JsonHelper $jsonHelper, 
        Helper $helper, 
        QuoteRepository $quoteRepository, 
        JsonResultFactory $jsonResultFactory, 
        QuoteValidator $quoteValidator, 
        \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderDirectCapture $directCapture,
        \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderAuthRequest $authRequest,
        \Clearpay\Clearpay\Model\Adapter\ClearpayPayment $clearpayApiPayment,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        ExpressPayment $expressPayment,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Clearpay\Clearpay\Model\ExpressPayment\ShippingListProvider $shippingListProvider
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_objectManager = $objectManager;
        $this->_quoteFactory = $quoteFactory;
        $this->_clearpayConfig = $clearpayConfig;
        $this->_clearpayOrderTokenV2 = $clearpayOrderTokenV2;
        $this->_tokenCheck = $tokenCheck;
        $this->_jsonHelper = $jsonHelper;
        $this->_helper = $helper;
        $this->_quoteRepository = $quoteRepository;
        $this->_jsonResultFactory = $jsonResultFactory;
        $this->_quoteValidator = $quoteValidator;
        $this->_directCapture = $directCapture;
        $this->_authRequest = $authRequest;
        $this->_clearpayApiPayment = $clearpayApiPayment;
        $this->_quoteManagement = $quoteManagement;
        $this->_expressPayment=$expressPayment;
        $this->_timezone = $timezone;
        $this->shippingListProvider = $shippingListProvider;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->_jsonResultFactory->create()->setData([
            'error' => 1,
            'message' => "Invalid Request."
        ]);
        if ($this->_clearpayConfig->getPaymentAction() == AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
            $action = strtolower($this->getRequest()->getParam('action'));
            switch ($action) {
                case "start":
                    $result = $this->_start();
                    break;
                case "change":
                    $result = $this->_change();
                    break;
                case "confirm":
                    $result = $this->_confirm();
                    break;
            }
        }
        return $result;
    }

    /**
     * Initialize the Express Checkout
     */
    public function _start()
    {
        $this->_helper->debug("================= In Initialiazation=========");
        // need to load the correct quote by store
        $data = $this->_checkoutSession->getData();

        $quote = $this->_checkoutSession->getQuote();
        $websiteId = $this->_clearpayConfig->getStoreObjectFromRequest()->getWebsiteId();

        if ($websiteId > 1) {
            $quote = $this->_quoteFactory->create()->loadByIdWithoutStore($data["quote_id_" . $websiteId]);
        }

        $customerSession = $this->_objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $this->_objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');

        if ($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
            $customer = $customerRepository->getById($customerId);

            // customer login
            $quote->setCustomer($customer);
        } else {
            $quote->setCustomerIsGuest(true)->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
        }

        $payment = $quote->getPayment();

        $payment->setMethod(\Clearpay\Clearpay\Model\Payovertime::METHOD_CODE);

        $quote->reserveOrderId();

        try {
            $payment = $this->_expressPayment->getClearPayExpressOrderToken($this->_clearpayOrderTokenV2, $payment, $quote);
        } catch (\Exception $e) {
            $result = $this->_jsonResultFactory->create()->setData([
                'error' => 1,
                'message' => $e->getMessage()
            ]);

            return $result;
        }

        $quote->setPayment($payment);

        $this->_quoteRepository->save($quote);
        $this->_checkoutSession->replaceQuote($quote);

        $token = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);

        $result = $this->_jsonResultFactory->create()->setData([
            'success' => true,
            'token' => $token
        ]);

        return $result;
    }

    /**
     * Shipping Address Change callback
     */
    public function _change()
    {
        $this->_helper->debug("================= In Shipping Change=========");
        $data = $this->_checkoutSession->getData();
        $customerData = $this->getRequest()->getPostValue();
        $quote = $this->_checkoutSession->getQuote();
        $websiteId = $this->_clearpayConfig->getStoreObjectFromRequest()->getWebsiteId();

        if ($websiteId > 1) {
            $quote = $this->_objectManager->get('Magento\Checkout\Model\Session')
                ->getQuote()
                ->load($data["quote_id_" . $websiteId]);
        }

        $shippingAddress = $quote->getShippingAddress();

        if (! empty($customerData) && ! $quote->isVirtual()) {
            // Set first name & lastname in shipping address
            $fullName = explode(' ', $customerData["name"]);
            $lastName = array_pop($fullName);
            if (count($fullName) == 0) {
                // if $customerData["name"] contains only one word
                $firstName = $lastName;
            } else {

                $firstName = implode(' ', $fullName);
            }

            $shippingAddress->setFirstName($firstName);
            $shippingAddress->setLastName($lastName);
            $shippingAddress->setStreet(array(
                $customerData['address1'],
                $customerData['address2']
            ));
            $shippingAddress->setCountryId($customerData['countryCode']);
            $shippingAddress->setCity($customerData['suburb']);
            $shippingAddress->setPostcode($customerData['postcode']);
            $shippingAddress->setRegionId($this->_expressPayment->getRegionId($customerData['state'], $customerData['countryCode']));
            $shippingAddress->setRegion($customerData['state']);
            $shippingAddress->setTelephone($customerData['phoneNumber']);
            $shippingAddress->setCollectShippingRates(true);

            $this->_quoteRepository->save($quote);
            $this->_checkoutSession->replaceQuote($quote);
        }

        $shippingList = $this->shippingListProvider->provide($quote);

        $this->_helper->debug("Shipping Estimation Rates", $shippingList);
        if (! empty($shippingList)) {
            $result = $result = $this->_jsonResultFactory->create()->setData([
                'success' => true,
                'shippingOptions' => $shippingList
            ]);
        } elseif ($quote->isVirtual()) {
            $result = $this->_jsonResultFactory->create()->setData([
                'error' => true,
                'message' => "Shipping option is not required for virtual product."
            ]);
        } else {

            $result = $this->_jsonResultFactory->create()->setData([
                'error' => true,
                'message' => "Shipping is unavailable for this address, or all options exceed Clearpay order limit."
            ]);
        }
        return $result;
    }


    /**
     * Place the order
     */
    public function _confirm()
    {
        $this->_helper->debug("================= In Confirm============");
        try {
            $responseData = $this->getRequest()->getParams();
            $this->_helper->debug("EC Response: ", $responseData);
            switch ($responseData['status']) {
                case \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_CANCELLED:
                    $this->messageManager->addError(__('You have cancelled your Clearpay payment. Please select an alternative payment method.'));
                    break;
                case \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_SUCCESS:

                    $quote = $this->_checkoutSession->getQuote();

                    $payment = $quote->getPayment();

                    $token = $responseData["orderToken"];
                    $merchant_order_id = $quote->getReservedOrderId();

                    $orderResponse = $this->_tokenCheck->generate($token);
                    $orderData = $this->_jsonHelper->jsonDecode($orderResponse->getBody());

                    /**
                     * Validation to check between session and post request
                     */
                    if (! $orderData || !empty($orderData['errorCode'])) {
                        // Check the order token being use
                        throw new \Magento\Framework\Exception\LocalizedException(__('There are issues when processing your payment. Invalid Token'));
                    } elseif ($this->_expressPayment->isCartUpdated($quote, $orderData['items'])) {
                        // Check cart Items
                        throw new \Magento\Framework\Exception\LocalizedException(__('There are issues when processing your payment. Invalid Cart Items'));
                    }
                    $this->_expressPayment->setOrderData($orderData);
                    $quote = $this->_checkoutSession->getQuote();
                    $this->_quoteValidator->validateBeforeSubmit($quote);

                    $baseOrderTotal = $quote->getBaseGrandTotal();
                    $orderAmount = array(
                        'amount' => $this->_expressPayment->formatAmount($baseOrderTotal),
                        'currency' => $quote->getBaseCurrencyCode()
                    );
                    // Process payment
                    if (! $this->_helper->getConfig('payment/clearpaypayovertime/payment_flow') || $this->_helper->getConfig('payment/clearpaypayovertime/payment_flow') == "immediate" || $quote->getIsVirtual()) {

                        $this->_helper->debug("Starting Payment Capture request.");

                        $paymentResponse = $this->_directCapture->generate($token, $merchant_order_id, $orderAmount);
                    } else {

                        $this->_helper->debug("Starting Auth request.");
                        $paymentResponse = $this->_authRequest->generate($token, $merchant_order_id, $orderAmount);
                    }

                    $paymentResponse = $this->_jsonHelper->jsonDecode($paymentResponse->getBody());

                    if (empty($paymentResponse['status'])) {
                        $paymentResponse['status'] = \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_DECLINED;
                        $this->_helper->debug("_confirm: Transaction Exception (Empty Response): " . json_encode($paymentResponse));
                    }

                    switch ($paymentResponse['status']) {
                        case \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_APPROVED:
                            $this->_tokenCheck->setIsTokenChecked(true);

                            $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID, $paymentResponse['id']);

                            $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS, $paymentResponse['paymentState']);

                            if ($paymentResponse['paymentState'] == \Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_AUTH_APPROVED && array_key_exists('events', $paymentResponse)) {
                                try {
                                    $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::AUTH_EXPIRY, $this->_timezone->date($paymentResponse['events'][0]['expires'])
                                        ->format('Y-m-d H:i T'));
                                } catch (\Exception $e) {
                                    $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::AUTH_EXPIRY, $this->_timezone->date($paymentResponse['events'][0]['expires'], null, false)
                                        ->format('Y-m-d H:i T'));
                                    $this->_helper->debug($e->getMessage());
                                }
                            }

                            $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT, array_key_exists('openToCaptureAmount', $paymentResponse) && ! empty($paymentResponse['openToCaptureAmount']) ? $paymentResponse['openToCaptureAmount']['amount'] : "0.00");

                            $this->_checkoutSession->setLastQuoteId($quote->getId())
                                ->setLastSuccessQuoteId($quote->getId())
                                ->clearHelperData();

                            // Store Customer email address in temporary variable
                            $customerEmailAddress = $quote->getCustomerEmail();

                            // Create Order From Quote

                            $quote->collectTotals();

                            // Restore Customer email address if it becomes null/blank
                            if (empty($quote->getCustomerEmail())) {
                                $quote->setCustomerEmail($customerEmailAddress);
                            }

                            // Catch the deadlock exception while creating the order and retry 3 times

                            $tries = 0;
                            do {
                                $retry = false;

                                try {
                                    // Create order in Magento
                                    $this->_helper->debug("Trying Order Creation. Try number:" . $tries);
                                    $order = $this->_quoteManagement->submit($quote);
                                } catch (\Exception $e) {

                                    if (preg_match('/SQLSTATE\[40001\]: Serialization failure: 1213 Deadlock found/', $e->getMessage()) && $tries < 2) {
                                        $this->_helper->debug("Waiting for a second before retrying the Order Creation");
                                        $retry = true;
                                        sleep(1);
                                    } else {
                                        // Reverse or void the order
                                        $orderId = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID);
                                        $paymentStatus = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS);

                                        if ($paymentStatus == \Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_AUTH_APPROVED) {
                                            $voidResponse = $this->_clearpayApiPayment->voidOrder($orderId);
                                            $voidResponse = $this->_jsonHelper->jsonDecode($voidResponse->getBody());

                                            if (! array_key_exists("errorCode", $voidResponse)) {
                                                $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS, $voidResponse['paymentState']);

                                                if (array_key_exists('openToCaptureAmount', $voidResponse) && ! empty($voidResponse['openToCaptureAmount'])) {
                                                    $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT, $voidResponse['openToCaptureAmount']['amount']);
                                                }

                                                $this->_helper->debug('Order Exception : There was a problem with order creation. Clearpay Order ' . $orderId . ' Voided.' . $e->getMessage());
                                                throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem placing your order. Your Clearpay order ' . $orderId . ' is refunded.'));
                                            } else {
                                                $this->_helper->debug("_confirm:Transaction Exception : " . json_encode($voidResponse));
                                                throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem placing your order.'));
                                            }
                                        } else {
                                           // $orderTotal = $quote->getGrandTotal();
                                            $refundResponse = $this->_clearpayApiPayment->refund($this->_expressPayment->formatAmount($baseOrderTotal), $orderId, $quote->getBaseCurrencyCode());

                                            $refundResponse = $this->_jsonHelper->jsonDecode($refundResponse->getBody());

                                            if (! empty($refundResponse['refundId'])) {
                                                $this->_helper->debug('Order Exception : There was a problem with order creation. Clearpay Order ' . $orderId . ' refunded.' . $e->getMessage());
                                                throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem placing your order. Your Clearpay order ' . $orderId . ' is refunded.'));
                                            } else {
                                                $this->_helper->debug("_confirm:Transaction Exception : " . json_encode($refundResponse));
                                                throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem placing your order.'));
                                            }
                                        }
                                    }
                                }
                                $tries ++;
                            } while ($tries < 3 && $retry);

                            if ($order) {

                                $payment = $order->getPayment();

                                if ($payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::PAYMENT_STATUS) == \Clearpay\Clearpay\Model\Response::PAYMENT_STATUS_AUTH_APPROVED) {
                                    $totalDiscount = $this->_expressPayment->calculateTotalDiscount($order);
                                    if ($totalDiscount > 0) {
                                        $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ROLLOVER_DISCOUNT, $this->_expressPayment->calculateTotalDiscount($order));
                                    }
                                    $this->_expressPayment->captureVirtual($order, $payment);
                                }

                                $this->_checkoutSession->setLastOrderId($order->getId())
                                    ->setLastRealOrderId($order->getIncrementId())
                                    ->setLastOrderStatus($order->getStatus());

                                $this->_expressPayment->createTransaction($order, $paymentResponse, $payment);

                                $this->_helper->debug("Clearpay Transaction Completed");
                                $result = $this->_jsonResultFactory->create()->setData([
                                'success' => true
                                ]);
                            } else {
                                $result = $this->_jsonResultFactory->create()->setData([
                                'success' => true
                                ]);
                                $this->_helper->debug("Order Exception : There was a problem with order creation.");
                            }
                            break;
                        case \Clearpay\Clearpay\Model\Response::RESPONSE_STATUS_DECLINED:
                            $result = $this->_jsonResultFactory->create()->setData([
                            'error' => true,
                            'message' =>'Clearpay payment declined. Please select an alternative payment method.'
                            ]);
                            $this->messageManager->addError(__('Clearpay payment declined. Please select an alternative payment method.'));
                            break;
                        default:
                            $result = $this->_jsonResultFactory->create()->setData([
                            'error' => true,
                            'message' =>$paymentResponse
                            ]);
                            $this->messageManager->addError($paymentResponse);
                            break;
                    }
                    break;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $result = $this->_jsonResultFactory->create()->setData([
            'error' => true,
            'message' =>$e->getMessage()
            ]);
            $this->_helper->debug("_confirm : Transaction Exception: " . $e->getMessage());
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $result = $this->_jsonResultFactory->create()->setData([
            'error' => true,
            'message' =>"There was a problem in placing your order."
            ]);
            $this->_helper->debug("_confirm : Transaction Exception: " . $e->getMessage());
            $this->messageManager->addError("There was a problem in placing your order.");
        }
        return $result;
    }

}
