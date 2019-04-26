<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2018 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\Adapter;

use \Clearpay\Clearpay\Model\Adapter\Clearpay\Call;
use \Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;

class ClearpayPayment
{
    /**
     * constant variable
     */
    const API_RESPONSE_APPROVED = 'APPROVED';

    /**
     * @var Call
     */
    protected $clearpayApiCall;
    protected $clearpayConfig;
    protected $objectManagerInterface;
    protected $jsonHelper;

    /**
     * ClearpayPayment constructor.
     * @param Call $clearpayApiCall
     * @param ClearpayConfig $clearpayConfig
     * @param ObjectManagerInterface $objectManagerInterface
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        Call $clearpayApiCall,
        ClearpayConfig $clearpayConfig,
        ObjectManagerInterface $objectManagerInterface,
        JsonHelper $jsonHelper
    ) {
        $this->clearpayApiCall = $clearpayApiCall;
        $this->clearpayConfig = $clearpayConfig;
        $this->objectManagerInterface = $objectManagerInterface;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @param $clearpayOrderId
     * @return mixed|\Zend_Http_Response
     */
    public function getPayment($clearpayOrderId, $override = [])
    {
        return $this->_getPayment($clearpayOrderId, false, $override);
    }

    /**
     * @param $token
     * @return mixed|\Zend_Http_Response
     */
    public function getPaymentByToken($token, $override = [])
    {
        return $this->_getPayment($token, true, $override);
    }

    /**
     * @param $input
     * @param bool $useToken
     * @return mixed|\Zend_Http_Response
     */
    protected function _getPayment($input, $useToken = false, $override = [])
    {
        // set url for ID
        $url = $this->clearpayConfig->getApiUrl('merchants/orders/' . $input, [], $override);

        // if request using token create url for it
        if ($useToken) {
            $url = $this->clearpayConfig->getApiUrl('merchants/orders/', ['token' => $input], $override);
        }

        try {
            $response = $this->clearpayApiCall->send($url, null, null, $override);
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
     * @param $amount
     * @param $orderId
     * @param string $currency
     * @return mixed|\Zend_Http_Response
     */
    public function refund($amount, $orderId, $currency = 'GBP', $override = [])
    {
        // create url to request refunds
        $url = $this->clearpayConfig->getApiUrl('v1/payments/' . $orderId . '/refund', [], $override);

        // generate body to be sent to refunds
        $body = [
            'amount'    => [
                'amount'    => abs(round($amount, 2)), // Clearpay API V1 requires a positive amount
                'currency'  => $currency,
            ],
            'merchantRefundId'  => null
        ];


        // refunding now
        try {
            $response = $this->clearpayApiCall->send(
                $url,
                $body,
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
}
