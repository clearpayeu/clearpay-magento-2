<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.Clearpay.com
 */
namespace Clearpay\Clearpay\Model\Adapter;

class ApiMode
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $environments;

    /**
     * Mode constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $environments
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, $environments = [])
    {
        $this->scopeConfig = $scopeConfig;
        $this->environments = $environments;
    }

    /**
     * Get All API modes from di.xml
     *
     * @return array
     */
    public function getAllApiModes()
    {
        return $this->environments;
    }

    /**
     * Get current API mode based on configuration
     *
     * @return array
     */
    public function getCurrentMode($override = [])
    {
        if (!empty($override["website_id"])) {
            return $this->environments[$this->scopeConfig->getValue('payment/clearpaypayovertime/api_mode', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES, $override["website_id"])];
        }
        return $this->environments[$this->scopeConfig->getValue('payment/clearpaypayovertime/api_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)];
    }
}
