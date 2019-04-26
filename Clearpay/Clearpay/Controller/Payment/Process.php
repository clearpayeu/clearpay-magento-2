<?php
/**
 * Magento 2 extensions for Clearpay
 *
 * @author Clearpay
 * @copyright 2016-2018 Clearpay https://www.clearpay.co.uk
 * Updated on 27th March 2018
 * Removed API V0 functionality
 */
namespace Clearpay\Clearpay\Controller\Payment;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Sales\Model\OrderFactory as OrderFactory;
use \Magento\Quote\Model\QuoteFactory as QuoteFactory;
use \Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use \Magento\Payment\Model\Method\AbstractMethod;
use \Clearpay\Clearpay\Model\Adapter\V1\ClearpayOrderTokenV1 as ClearpayOrderTokenV1;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Clearpay\Clearpay\Helper\Data as Helper;
use \Magento\Checkout\Model\Cart as Cart;
use \Magento\Store\Model\StoreResolver as StoreResolver;
use \Magento\Quote\Model\ResourceModel\Quote as QuoteRepository;
use \Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;

/**
 * Class Response
 * @package Clearpay\Clearpay\Controller\Payment
 */
class Process extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_quoteFactory;
    protected $_clearpayConfig;
    protected $_clearpayOrderTokenV1;
    protected $_jsonHelper;
    protected $_helper;
    protected $_cart;
    protected $_storeResolver;
    protected $_quoteRepository;
    protected $_jsonResultFactory;

    /**
     * Response constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        ClearpayConfig $clearpayConfig,
        ClearpayOrderTokenV1 $clearpayOrderTokenV1,
        JsonHelper $jsonHelper,
        Helper $helper,
        Cart $cart,
        StoreResolver $storeResolver,
        QuoteRepository $quoteRepository,
        JsonResultFactory $jsonResultFactory
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_clearpayConfig = $clearpayConfig;
        $this->_clearpayOrderTokenV1 = $clearpayOrderTokenV1;
        $this->_jsonHelper = $jsonHelper;
        $this->_helper = $helper;
        $this->_cart = $cart;
        $this->_storeResolver = $storeResolver;
        $this->_quoteRepository = $quoteRepository;
        $this->_jsonResultFactory = $jsonResultFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->_clearpayConfig->getPaymentAction() == AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
            $result = $this->_processAuthorizeCapture();
        }
        return $result;
    }

    public function _processAuthorizeCapture()
    {
        
        //need to load the correct quote by store
        $data = $this->_checkoutSession->getData();
        
        $quote = $this->_checkoutSession->getQuote();
        $website_id = $this->_clearpayConfig->getStoreObjectFromRequest()->getWebsiteId();

        if ($website_id > 1) {
            $quote = $this->_quoteFactory->create()->loadByIdWithoutStore($data["quote_id_" . $website_id]);
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');

        if ($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
            $customer = $customerRepository->getById($customerId);

            // customer login
            $quote->setCustomer($customer);

            $billingAddress  = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();

            //check if shipping address is missing - e.g. Gift Cards
            if ((empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))) && (empty($billingAddress) || empty($billingAddress->getStreetLine(1)))) {
                $result = $this->_jsonResultFactory->create()->setData(
                    ['success' => false, 'message' => 'Please select an Address']
                );

                return $result;
            } // else if( empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))  || empty($shippingAddress->getFirstname()) ) {
            //     $shippingAddress = $quote->getBillingAddress();
            //     $quote->setShippingAddress($quote->getBillingAddress());
            // }
            elseif (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {
                $billingAddress = $quote->getShippingAddress();
                $quote->setBillingAddress($quote->getShippingAddress());
            }
        } else {
            $post = $this->getRequest()->getPostValue();

            if (!empty($post['email'])) {
                $email = htmlspecialchars($post['email'], ENT_QUOTES);
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
                try {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $quote->setCustomerEmail($email)
                            ->setCustomerIsGuest(true)
                            ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                    }
                } catch (\Exception $e) {
                    $result = $this->_jsonResultFactory->create()->setData(
                        ['error' => 1, 'message' => $e->getMessage()]
                    );
                    return $result;
                }
            }
        }

        $payment = $quote->getPayment();

        $payment->setMethod(\Clearpay\Clearpay\Model\Payovertime::METHOD_CODE);

        $quote->reserveOrderId();


        try {
            $payment = $this->_getAfterPayOrderToken($this->_clearpayOrderTokenV1, $payment, $quote);
        } catch (\Exception $e) {
            $result = $this->_jsonResultFactory->create()->setData(
                ['error' => 1, 'message' => $e->getMessage()]
            );

            return $result;
        }

        $quote->setPayment($payment);
        $this->_quoteRepository->save($quote);

        $this->_checkoutSession->replaceQuote($quote);

        $token = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);

        $result = $this->_jsonResultFactory->create()->setData(
            ['success' => true, 'token' => $token]
        );

        return $result;
    }

    /**
     * @param $payment
     * @return bool
     * @throws LocalizedException
     */
    private function _getAfterPayOrderToken($clearpayOrderToken, $payment, $targetObject)
    {
        if ($targetObject && $targetObject->getReservedOrderId()) {
            $result = $clearpayOrderToken->generate($targetObject, \Clearpay\Clearpay\Model\Payovertime::CLEARPAY_PAYMENT_TYPE_CODE_V1, ['merchantOrderId' => $targetObject->getReservedOrderId() ]);
        } elseif ($targetObject) {
            $result = $clearpayOrderToken->generate($targetObject, \Clearpay\Clearpay\Model\Payovertime::CLEARPAY_PAYMENT_TYPE_CODE_V1);
        }
        
        $result = $this->_jsonHelper->jsonDecode($result->getBody(), true);
        $orderToken = array_key_exists('token', $result) ? $result['token'] : false;

        if ($orderToken) {
            $payment->setAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN, $orderToken);
        } else {
            $this->_helper->debug('No Token response from API');
            throw new \Magento\Framework\Exception\LocalizedException(__('There is an issue processing your order.'));
        }
        return $payment;
    }
}
