<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2019 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\Adapter;

use \Clearpay\Clearpay\Model\Adapter\Clearpay\Call;
use \Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Class ClearpayTotalLimit
 * @package Clearpay\Clearpay\Model\Adapter
 */
class ClearpayTotalLimit
{
    /**
     * @var Call
     */
    protected $clearpayApiCall;
    protected $clearpayConfig;
    protected $objectManagerInterface;
    protected $jsonHelper;

    /**
     * ClearpayTotalLimit constructor.
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
     * @return mixed|\Zend_Http_Response
     */
    public function getLimit($override = [])
    {
        /** @var \Clearpay\Clearpay\Model\Config\Payovertime $url */
        $url = $this->clearpayConfig->getApiUrl('v2/configuration'); //V2

        // calling API
        try {
            $response = $this->clearpayApiCall->send($url, null, null, $override);
        } 
        catch (\Exception $e) {

            $state =  $this->objectManagerInterface->get('Magento\Framework\App\State');
            if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
                throw new \Exception($e->getMessage());
            }
            else {
                $response = $this->objectManagerInterface->create('Clearpay\Clearpay\Model\Payovertime');
                $response->setBody($this->jsonHelper->jsonEncode([
                    'error' => 1,
                    'message' => $e->getMessage()
                ]));
            }
        }

        return $response;
    }
}
