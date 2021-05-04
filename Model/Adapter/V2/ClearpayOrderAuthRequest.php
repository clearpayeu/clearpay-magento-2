<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\ClearpayEurope\Model\Adapter\V2;

use \Clearpay\ClearpayEurope\Model\Adapter\Clearpay\Call;
use \Clearpay\ClearpayEurope\Model\Config\Payovertime as PayovertimeConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Clearpay\ClearpayEurope\Helper\Data as Helper;

/**
 * Class ClearpayOrderAuthRequest
 * @package Clearpay\ClearpayEurope\Model\Adapter\V2
 */
class ClearpayOrderAuthRequest
{
    protected $clearpayApiCall;
    protected $clearpayConfig;
    protected $objectManagerInterface;
    protected $storeManagerInterface;
    protected $jsonHelper;
    protected $helper;

    /**
     * ClearpayOrderAuthRequest constructor.
     * @param Call $clearpayApiCall
     * @param PayovertimeConfig $clearpayConfig
     * @param ObjectManagerInterface $objectManagerInterface
     * @param toreManagerInterface $storeManagerInterface
     * @param JsonHelper $jsonHelper
     * @param Helper $clearpayHelper
     */
    public function __construct(
        Call $clearpayApiCall,
        PayovertimeConfig $clearpayConfig,
        ObjectManagerInterface $objectManagerInterface,
        StoreManagerInterface $storeManagerInterface,
        JsonHelper $jsonHelper,
        Helper $clearpayHelper
    ) {
        $this->clearpayApiCall = $clearpayApiCall;
        $this->clearpayConfig = $clearpayConfig;
        $this->objectManagerInterface = $objectManagerInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->jsonHelper = $jsonHelper;
        $this->helper = $clearpayHelper;
    }

    /**
     * @param $token
     * @param $merchant_order_id
     * @return mixed|\Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate($token, $merchant_order_id)
    {
        $requestData = $this->_buildAuthRequest($token, $merchant_order_id);

        try {
            $response = $this->clearpayApiCall->send(
                $this->clearpayConfig->getApiUrl('v1/payments/capture'),
                $requestData,
                \Magento\Framework\HTTP\ZendClient::POST
            );
        } catch (\Exception $e) {
            $response = $this->objectManagerInterface->create('Clearpay\ClearpayEurope\Model\Payovertime');
            $response->setBody($this->jsonHelper->jsonEncode([
                'error' => 1,
                'message' => $e->getMessage()
            ]));
        }

        return $response;
    }
	/**
     * @param $token
     * @param $merchant_order_id
     * @return array
     */
    protected function _buildAuthRequest($token, $merchant_order_id)
    {
        $params['requestId'] = uniqid();
        $params['merchantReference'] = $merchant_order_id;
        $params['token'] = $token;

        return $params;
    }
}
