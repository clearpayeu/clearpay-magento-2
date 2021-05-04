<?php
/**
 * Magento 2 extensions for Clearpay
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\ClearpayEurope\Controller\Payment;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Sales\Model\OrderFactory as OrderFactory;
use \Magento\Quote\Model\QuoteFactory as QuoteFactory;
use \Clearpay\ClearpayEurope\Model\Config\Payovertime as ClearpayConfig;
use \Magento\Payment\Model\Method\AbstractMethod;
use \Clearpay\ClearpayEurope\Model\Adapter\V2\ClearpayOrderTokenV2 as ClearpayOrderTokenV2;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Clearpay\ClearpayEurope\Helper\Data as Helper;
use \Magento\Checkout\Model\Cart as Cart;
use \Magento\Store\Model\StoreResolver as StoreResolver;
use \Magento\Quote\Model\ResourceModel\Quote as QuoteRepository;
use \Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use \Magento\Quote\Model\QuoteValidator as QuoteValidator;

/**
 * Class Response
 * @package Clearpay\ClearpayEurope\Controller\Payment
 */
class Process extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_quoteFactory;
    protected $_clearpayConfig;
    protected $_clearpayOrderTokenV2;
    protected $_jsonHelper;
    protected $_helper;
    protected $_cart;
    protected $_storeResolver;
    protected $_quoteRepository;
    protected $_jsonResultFactory;
    protected $_quoteValidator;

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
        ClearpayOrderTokenV2 $clearpayOrderTokenV2,
        JsonHelper $jsonHelper,
        Helper $helper,
        Cart $cart,
        StoreResolver $storeResolver,
        QuoteRepository $quoteRepository,
        JsonResultFactory $jsonResultFactory,
        QuoteValidator $quoteValidator
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_clearpayConfig = $clearpayConfig;
        $this->_clearpayOrderTokenV2 = $clearpayOrderTokenV2;
        $this->_jsonHelper = $jsonHelper;
        $this->_helper = $helper;
        $this->_cart = $cart;
        $this->_storeResolver = $storeResolver;
        $this->_quoteRepository = $quoteRepository;
        $this->_jsonResultFactory = $jsonResultFactory;
        $this->_quoteValidator = $quoteValidator;
        
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
		
		$billingAddress  = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
		
		if (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {
			if(!empty($shippingAddress) && !empty($shippingAddress->getStreetLine(1)))
			{
				$shippingAddressData = $shippingAddress->getData();
				$billingAddress->setPrefix($shippingAddressData['prefix']);
				$billingAddress->setFirstName($shippingAddressData['firstname']);
				$billingAddress->setMiddleName($shippingAddressData['middlename']);
				$billingAddress->setLastName($shippingAddressData['lastname']);
				$billingAddress->setSuffix($shippingAddressData['suffix']);
				$billingAddress->setCompany($shippingAddressData['company']);
				$billingAddress->setStreet($shippingAddressData['street']);
				$billingAddress->setCity($shippingAddressData['city']);
				$billingAddress->setRegion($shippingAddressData['region']);
				$billingAddress->setRegionId($shippingAddressData['region_id']);
				$billingAddress->setPostcode($shippingAddressData['postcode']);
				$billingAddress->setCountryId($shippingAddressData['country_id']);
				$billingAddress->setTelephone($shippingAddressData['telephone']);
				$billingAddress->setFax($shippingAddressData['fax']);
				$this->_helper->debug("No billing address found. Adding the shipping address as billing address");
			}
			else{
				if($customerSession->isLoggedIn()){
					try{
					   $billingID =  $customerSession->getCustomer()->getDefaultBilling();
					   $this->_helper->debug("No billing address found. Adding the Customer's default billing address.");
					   $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingID);
					   $billingAddress->addData($address->getData());
			
					}catch(\Exception $e){
						$this->_helper->debug($e->getMessage());
						$result = $this->_jsonResultFactory->create()->setData(
						  ['success' => false, 'message' => 'Please select an Address']
						);

					  return $result;
					}
				}
				else{
				  $result = $this->_jsonResultFactory->create()->setData(
					['success' => false, 'message' => 'Please select an Address']
				  );

				  return $result;
				}
			}
		}
		
		if((empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))) && !$quote->isVirtual()){
			$billingAddress  = $quote->getBillingAddress();
			if(!empty($billingAddress) && !empty($billingAddress->getStreetLine(1)))
			{
				$billingAddressData = $billingAddress->getData();
				$shippingAddress->setPrefix($billingAddressData['prefix']);
				$shippingAddress->setFirstName($billingAddressData['firstname']);
				$shippingAddress->setMiddleName($billingAddressData['middlename']);
				$shippingAddress->setLastName($billingAddressData['lastname']);
				$shippingAddress->setSuffix($billingAddressData['suffix']);
				$shippingAddress->setCompany($billingAddressData['company']);
				$shippingAddress->setStreet($billingAddressData['street']);
				$shippingAddress->setCity($billingAddressData['city']);
				$shippingAddress->setRegion($billingAddressData['region']);
				$shippingAddress->setRegionId($billingAddressData['region_id']);
				$shippingAddress->setPostcode($billingAddressData['postcode']);
				$shippingAddress->setCountryId($billingAddressData['country_id']);
				$shippingAddress->setTelephone($billingAddressData['telephone']);
				$shippingAddress->setFax($billingAddressData['fax']);
				$this->_helper->debug("No shipping address found. Adding the billing address as shipping address");
			}
			else{
				$result = $this->_jsonResultFactory->create()->setData(
					['success' => false, 'message' => 'Please select an Address']
				  );

				return $result;
			}
		}
		
        $payment = $quote->getPayment();

        $payment->setMethod(\Clearpay\ClearpayEurope\Model\Payovertime::METHOD_CODE);

        $quote->reserveOrderId();


        try {
            $payment = $this->_getClearPayOrderToken($this->_clearpayOrderTokenV2, $payment, $quote);
        } catch (\Exception $e) {
            $result = $this->_jsonResultFactory->create()->setData(
                ['error' => 1, 'message' => $e->getMessage()]
            );

            return $result;
        }

        $quote->setPayment($payment);
        
		try{
			$this->_quoteValidator->validateBeforeSubmit($quote);
		}
		catch(\Magento\Framework\Exception\LocalizedException $e){
			 $result = $this->_jsonResultFactory->create()->setData(
				['success' => false, 'message' => $e->getMessage()]
			  );
			return $result;
		}
		$this->_quoteRepository->save($quote);
        $this->_checkoutSession->replaceQuote($quote);

        $token = $payment->getAdditionalInformation(\Clearpay\ClearpayEurope\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);

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
    private function _getClearPayOrderToken($clearpayOrderToken, $payment, $targetObject)
    {
        if ($targetObject && $targetObject->getReservedOrderId()) {
            $result = $clearpayOrderToken->generate($targetObject, ['merchantOrderId' => $targetObject->getReservedOrderId() ]);
        } elseif ($targetObject) {
            $result = $clearpayOrderToken->generate($targetObject);
        }
        
        $result = $this->_jsonHelper->jsonDecode($result->getBody(), true);
        $orderToken = array_key_exists('token', $result) ? $result['token'] : false;

        if ($orderToken) {
            $payment->setAdditionalInformation(\Clearpay\ClearpayEurope\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN, $orderToken);
        } else {
            $this->_helper->debug('No Token response from API');
            throw new \Magento\Framework\Exception\LocalizedException(__('There is an issue processing your order.'));
        }
        return $payment;
    }
}
