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
 * Class ClearpayOrderTokenCheck
 * @package Clearpay\Clearpay\Model\Adapter\V2
 */
class ClearpayOrderTokenCheck
{
    protected $clearpayApiCall;
    protected $clearpayConfig;
    protected $objectManagerInterface;
    protected $storeManagerInterface;
    protected $jsonHelper;
    protected $helper;

    /**
     * ClearpayOrderTokenCheck constructor.
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
     * @param $token
     * @return mixed|\Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate($token = null)
    {
        try {
            $response = $this->clearpayApiCall->send(
                $this->clearpayConfig->getApiUrl('v2/checkouts/' . $token),
                [],
                \Magento\Framework\HTTP\ZendClient::GET
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
