<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\Clearpay\Model\Adapter\V2;

use \Clearpay\Clearpay\Model\Adapter\Clearpay\Call;
use \Clearpay\Clearpay\Model\Config\Payovertime as PayovertimeConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Clearpay\Clearpay\Helper\Data as Helper;

/**
 * Class ClearpayOrderPaymentCapture
 * @package Clearpay\Clearpay\Model\Adapter\V2
 */
class ClearpayOrderPaymentCapture
{
    protected $clearpayApiCall;
    protected $clearpayConfig;
    protected $objectManagerInterface;
    protected $storeManagerInterface;
    protected $jsonHelper;
    protected $helper;

    /**
     * ClearpayOrderPaymentCapture constructor.
     * @param Call $clearpayApiCall
     * @param PayovertimeConfig $clearpayConfig
     * @param ObjectManagerInterface $objectManagerInterface
     * @param StoreManagerInterface $storeManagerInterface
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
     * @param $totalAmount
     * @param $merchant_order_id
     * @param array $clearpay_order_id
     * @return mixed|\Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function send($totalAmount,$merchant_order_id,$clearpay_order_id,$override=[])
    {
        $requestData = $this->_buildPaymentCaptureRequest($totalAmount, $merchant_order_id);

        try {
            $response = $this->clearpayApiCall->send(
                $this->clearpayConfig->getApiUrl('v2/payments/'.$clearpay_order_id.'/capture',[],$override),
                $requestData,
                \Magento\Framework\HTTP\ZendClient::POST,
				$override
            );
        } catch (\Exception $e) {
            $response = $this->objectManagerInterface->create('Clearpay\Clearpay\Model\Payovertime');
            $response->setBody($this->jsonHelper->jsonEncode([
                'error' => 1,
                'message' => $e->getMessage()
            ]));
        }

        return $response;
    }
	/**
     * @param $totalAmount
     * @param $merchant_order_id
     * @return array
     */
    protected function _buildPaymentCaptureRequest($totalAmount, $merchant_order_id)
    {
		$params['requestId'] = uniqid();
        $params['merchantReference'] = $merchant_order_id;
        $params['amount'] = $totalAmount;

        return $params;
    }
}
