<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model;

use \Magento\Payment\Model\InfoInterface;
use \Magento\Framework\Exception\LocalizedException as LocalizedException;
use \Clearpay\Clearpay\Helper\Data as Helper;
use \Magento\Quote\Model\ResourceModel\Quote\Payment as PaymentQuoteRepository;

class Payovertime extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Constant variable
     */
    const METHOD_CODE = 'clearpaypayovertime';

    const ADDITIONAL_INFORMATION_KEY_TOKEN = 'clearpay_token';
    const ADDITIONAL_INFORMATION_KEY_ORDERID = 'clearpay_order_id';
    const ADDITIONAL_INFORMATION_KEY_TOKENGENERATED = 'clearpay_token_generated';
    const PAYMENT_STATUS = 'clearpay_payment_status';
    const ROLLOVER_DISCOUNT = 'clearpay_rollover_discount';
    const ROLLOVER_AMOUNT = 'clearpay_rollover_amount';
    const OPEN_TOCAPTURE_AMOUNT = 'clearpay_open_to_capture_amount';
    const ROLLOVER_REFUND = 'clearpay_rollover_refund_amount';
	const AUTH_EXPIRY = 'clearpay_auth_expiry_date';


    const CLEARPAY_PAYMENT_TYPE_CODE = 'PBI';

    const MINUTE_DELAYED_ORDER = 75;

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    protected $_isGateway = true;
    protected $_isInitializeNeeded = false;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal = false;
    protected $_canFetchTransactionInfo = true;

    protected $_infoBlockType = 'Clearpay\Clearpay\Block\Info';

    /**
     * For dependency injection
     */
    protected $supportedContryCurrencyCodes = array('GB'=>'GBP','AU'=>'AUD','NZ'=>'NZD','CA'=>'CAD');
    protected $clearpayPaymentTypeCode = self::CLEARPAY_PAYMENT_TYPE_CODE;

    protected $logger;

    protected $checkoutSession;
    protected $exception;

    protected $clearpayOrderTokenV2;

    protected $clearpayPayment;
    protected $clearpayResponse;
    protected $helper;
    protected $date;
    protected $timezone;

    protected $transactionRepository;
    protected $transactionBuilder;
    protected $jsonHelper;
    protected $messageManager;
    protected $paymentQuoteRepository;

    /**
     * Payovertime constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Exception\LocalizedExceptionFactory $exception
     * @param Adapter\ClearpayOrderTokenV2 $clearpayOrderTokenV2
     * @param Adapter\ClearpayPayment $clearpayPayment
     * @param Response $clearpayResponse
     * @param Helper $clearpayHelper
     * @param PaymentQuoteRepository $paymentQuoteRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Exception\LocalizedExceptionFactory $exception,
        \Clearpay\Clearpay\Model\Adapter\V2\ClearpayOrderTokenV2 $clearpayOrderTokenV2,
        \Clearpay\Clearpay\Model\Adapter\ClearpayPayment $clearpayPayment,
        \Clearpay\Clearpay\Model\Response $clearpayResponse,
        Helper $clearpayHelper,
        PaymentQuoteRepository $paymentQuoteRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
    
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->checkoutSession = $checkoutSession;
        $this->exception = $exception;
        $this->clearpayPayment = $clearpayPayment;
        $this->clearpayResponse = $clearpayResponse;
        $this->helper = $clearpayHelper;
        $this->date = $date;
        $this->timezone = $timezone;

        $this->transactionRepository = $transactionRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->jsonHelper = $jsonHelper;
        $this->messageManager = $messageManager;

        $this->clearpayOrderTokenV2 = $clearpayOrderTokenV2;
        $this->_paymentQuoteRepository = $paymentQuoteRepository;
    }

    /**
     * @return bool
     */
    public function isInitializeNeeded()
    {
        return $this->_isInitializeNeeded;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        return $this;
    }

    /**
     * @param $payment
     * @return bool
     * @throws LocalizedException
     */
    protected function _getClearPayOrderToken($clearpayOrderToken, $payment, $targetObject)
    {
        $result = $clearpayOrderToken->generate($targetObject, $this->clearpayPaymentTypeCode);
        $result_ori = $result;

        $result = $this->jsonHelper->jsonDecode($result->getBody(), true);
        $orderToken = array_key_exists('token', $result) ? $result['token'] : false;
        
        if (!array_key_exists('token', $result)) {
            $orderToken = array_key_exists('orderToken', $result) ? $result['orderToken'] : false;
        }

        if ($orderToken) {
            $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_TOKEN, $orderToken);
            $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_TOKENGENERATED, true);
        } else {
            $this->helper->debug('No Token response from API');
            throw new \Magento\Framework\Exception\LocalizedException(__('There is an issue processing your order.'));
        }
        return $payment;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @throws LocalizedException
     *
     * @return null
     */
    public function capture(InfoInterface $payment, $amount)
    {
        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $token_generated = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKENGENERATED);
        $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_TOKENGENERATED, false);
        $this->_paymentQuoteRepository->save($payment);
        return $this;
    }


    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return bool|mixed|\Zend_Http_Response
     * @throws LocalizedException
     */
    public function order(InfoInterface $payment, $amount)
    {
        // get order ID
        $orderId = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDERID);

        // if order ID is not exist
        if (!$orderId) {
            $response = $this->clearpayPayment->getPaymentByToken(
                $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_TOKEN),
                ["website_id" => $payment->getOrder()->getStore()->getWebsiteId()]
            );
        } else {
            $response = $this->clearpayPayment->getPayment(
                $orderId,
                ["website_id" => $payment->getOrder()->getStore()->getWebsiteId()]
            );
        }

        $response = $this->jsonHelper->jsonDecode($response->getBody());
        $apiAmount = $response['orderDetail']['orderAmount']['amount'];

        // check the amount is the same - deprecated due to possible rounding differences on Magento 2
        // if ((int)$apiAmount != (int)$amount) {
        //     throw new \Magento\Framework\Exception\LocalizedException(__('Detected fraud.'));
        // }

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // debug mode
        $this->helper->debug('Start \Clearpay\Clearpay\Model\Payovertime::refund()');
		
		$result = $this->clearpayResponse->calculateRefund($payment, $amount);
		
		if(!array_key_exists('success',$result)){
			throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem with your refund. Please check the logs.'));
		}
		$this->helper->debug('Finished \Clearpay\Clearpay\Model\Payovertime::refund()');
		return $this;
		
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->getConfigData('merchant_id') || !$this->getConfigData('merchant_key') || ($this->getConfigData('payment_flow') == "deferred" && $quote->getGrandTotal() < 1)) {
            return false;
        }
		else{
			$excluded_categories=$this->getConfigData('exclude_category');
			if($excluded_categories!=""){
				
				$quote = $this->checkoutSession->getQuote();
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
    }

    /**
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        $canUseForCurrency= false;
        $storeCurrencyCode=$this->getStoreCurrencyCode();
        if (in_array($currencyCode, $this->supportedContryCurrencyCodes) ) {
            
            if ($currencyCode==$storeCurrencyCode ) {
                $canUseForCurrency=parent::canUseForCurrency($currencyCode);
				}else if(!empty($this->getConfigData('enable_cbt')) && !empty($this->getConfigData('cbt_country'))){
                //Currency Check for Cross Border trade
                $specifiedCountires=explode(",",$this->getConfigData('cbt_country'));
                foreach($specifiedCountires AS $country){
                    if(isset($this->supportedContryCurrencyCodes[$country]) && ($currencyCode==$this->supportedContryCurrencyCodes[$country])){
                        $canUseForCurrency=parent::canUseForCurrency($currencyCode);
                        break;
                    }
                }
                
            }
           
        }
        return $canUseForCurrency;
    }

    /**
     * @param InfoInterface $payment
     * @param string $transactionId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        // Debug mode
        $this->helper->debug('Start \Clearpay\Clearpay\Model\Payovertime::fetchTransactionInfo()');

        $order = $payment->getOrder();

        // adding current magento scope datetime with 75 mins calculations
        $requestDate = $this->date->gmtDate(null, $this->timezone->scopeTimeStamp() - (self::MINUTE_DELAYED_ORDER * 60));
        $orderScopeDateArray = get_object_vars($this->timezone->date($order->getCreatedAt()));
        $orderScopeDate = $this->date->gmtDate(null, $orderScopeDateArray['date']);

        // check if order still in 75 mins mark
        if ($orderScopeDate < $requestDate) {
            // set token and get payment data from API
            $token = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_TOKEN);
            $response = $this->clearpayPayment->getPaymentByToken(
                $token,
                ["website_id" => $order->getStore()->getWebsiteId()]
            );
            $response = $this->jsonHelper->jsonDecode($response->getBody());

            // check if result found
            if (isset($response['totalResults']) && $response['totalResults'] > 0) {
                $result = $response['results'][0];
                switch ($result['status']) {
                    case \Clearpay\Clearpay\Model\Status::STATUS_APPROVED:
                        // Approved payment will update the order and create invoice
                        $this->clearpayResponse->updatePayment($payment->getOrder(), $result['id']);
                        $this->clearpayResponse->createInvoiceAndUpdateOrder($payment->getOrder(), $result['id']);
                        $payment->setIsTransactionApproved(true);
                        break;

                    case \Clearpay\Clearpay\Model\Status::STATUS_DECLINED;
                        // set payment denied and will canceled the order
                        $payment->addTransactionCommentsToOrder(false, __('Payment declined by Clearpay'));
                        $payment->setIsTransactionDenied(true);
                        break;

                    case \Clearpay\Clearpay\Model\Status::STATUS_FAILED;
                        // set payment denied and will canceled the order
                        $payment->addTransactionCommentsToOrder(false, __('Payment Failed'));
                        $payment->setIsTransactionDenied(true);
                        break;
                }
            } else {
                // if order is just an abandoned order
                $payment->addTransactionCommentsToOrder(false, __('Customer abandoned the payment process'));
                $payment->setIsTransactionDenied(true);
            }
        } else {
            $this->helper->debug('The requested order still in 75 minutes from current date time.');
        }

        // Debug mode
        $this->helper->debug('Finished \Clearpay\Clearpay\Model\Payovertime::fetchTransactionInfo()');

        // return to the parent
        return parent::fetchTransactionInfo($payment, $transactionId);
    }
    /**
     * Calculated the merchant's store currency code
     *
     * @return $text
     */
    public function getStoreCurrencyCode()
    {
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore();
        return $store->getBaseCurrencyCode();
    }
}
