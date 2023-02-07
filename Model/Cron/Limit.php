<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.Clearpay.com
 */
namespace Clearpay\Clearpay\Model\Cron;

use Clearpay\Clearpay\Model\Adapter\ClearpayTotalLimit as ClearpayTotalLimit;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Clearpay\Clearpay\Helper\Data as ClearpayHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\App\Config\Storage\WriterInterface as WriterInterface;
use Magento\Config\Model\ResourceModel\Config as RequestConfig;
use Psr\Log\LoggerInterface as logger;

class Limit
{
    /**
     * @var ClearpayTotalLimit
     */
    protected $_clearpayTotalLimit;
    protected $_storeManager;
    protected $_helper;
    protected $_jsonHelper;
    protected $_resourceConfig;
    protected $_writerInterface;
    private $_logger;

    /**
     * Limit constructor.
     * @param ClearpayTotalLimit $clearpayTotalLimit
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     */
    public function __construct(
        ClearpayTotalLimit $clearpayTotalLimit,
        StoreManagerInterface $storeManager,
        ClearpayHelper $helper,
        JsonHelper $jsonHelper,
        WriterInterface $writerInterface,
        RequestConfig $resourceConfig,
        logger $logger
    ) {
        $this->_clearpayTotalLimit = $clearpayTotalLimit;
        $this->_storeManager = $storeManager;
        $this->_jsonHelper = $jsonHelper;
        $this->_resourceConfig = $resourceConfig;
        $this->_helper = $helper;
        $this->_writerInterface = $writerInterface;
        $this->_logger=$logger;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        //run the default update first
        $this->_updateDefault();

        $websites = $this->_getWebsites();
        //$this->_helper->debug("CRON Websites:" . json_encode($websites));

        if ($websites && count($websites) > 1) {
            foreach ($websites as $key => $website) {
                $this->_updateWebsite($website);
            }
        }
    }

    /**
     * @return array
     */
    private function _getWebsites()
    {
        $websites = $this->_storeManager->getWebsites();
        return $websites;
    }

    /**
     * @return bool
     */
    private function _updateDefault()
    {

        // $this->_helper->debug("Update Default");
        $response = $this->_clearpayTotalLimit->getLimit();
        $response = $this->_jsonHelper->jsonDecode($response->getBody());

        $this->_helper->debug("CRON :" . array_key_exists('errorCode', $response));
        

        if (array_key_exists('errorCode', $response)) {
            //Log the error
            $this->_logger->warning('Clearpay Update Limits Failed. Please check Merchant ID and Key. Default Config');
            return false;
        } else {
            // default min and max if not provided
            $minTotal = "0";
            $maxTotal = "0";

            // understand the response from the API
			$minTotal = array_key_exists('minimumAmount',$response) && isset($response['minimumAmount']['amount']) ? $response['minimumAmount']['amount'] : "0";
			$maxTotal = array_key_exists('maximumAmount',$response) && isset($response['maximumAmount']['amount']) ? $response['maximumAmount']['amount'] : "0";

            //Change the minimum amd maximum to Not applicable if both limits are 0.
            if ($minTotal == "0" && $maxTotal=="0") {
                $minTotal="N/A";
                $maxTotal="N/A";
            }

            $this->_resourceConfig->saveConfig(
                'payment/' . \Clearpay\Clearpay\Model\Payovertime::METHOD_CODE . '/' . \Clearpay\Clearpay\Model\Config\Payovertime::MIN_TOTAL_LIMIT,
                $minTotal,
                'default',
                0
            );

            $this->_resourceConfig->saveConfig(
                'payment/' . \Clearpay\Clearpay\Model\Payovertime::METHOD_CODE . '/' . \Clearpay\Clearpay\Model\Config\Payovertime::MAX_TOTAL_LIMIT,
                $maxTotal,
                'default',
                0
            );

            return true;
        }
    }

    /**
     * @return bool
     */
    private function _updateWebsite($website)
    {
        
        $website_id = $website["website_id"];

        $response = $this->_clearpayTotalLimit->getLimit([ "website_id" => $website_id ]);
        $response = $this->_jsonHelper->jsonDecode($response->getBody());

        if (array_key_exists('errorCode', $response)) {
            //Log the error
            $this->_logger->warning('Clearpay Update Limits Failed. Please check Merchant ID and Key.' . $website["name"]);
            return false;
        } else {
            // default min and max if not provided
            $minTotal = "0";
            $maxTotal = "0";

            // understand the response from the API
            $minTotal = isset($response['minimumAmount']['amount']) ? $response['minimumAmount']['amount'] : "0";
            $maxTotal = isset($response['maximumAmount']['amount']) ? $response['maximumAmount']['amount'] : "0";

            //Change the minimum amd maximum to Not applicable if both limits are 0.
            if ($minTotal == "0" && $maxTotal=="0") {
                $minTotal="N/A";
                $maxTotal="N/A";
            }

            $result = $this->_writerInterface->save(
                'payment/' . \Clearpay\Clearpay\Model\Payovertime::METHOD_CODE . '/' . \Clearpay\Clearpay\Model\Config\Payovertime::MIN_TOTAL_LIMIT,
                $minTotal,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                $website_id
            );

            $this->_writerInterface->save(
                'payment/' . \Clearpay\Clearpay\Model\Payovertime::METHOD_CODE . '/' . \Clearpay\Clearpay\Model\Config\Payovertime::MAX_TOTAL_LIMIT,
                $maxTotal,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                $website_id
            );

            return true;
        }
    }
}
