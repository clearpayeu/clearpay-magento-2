<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\ClearpayEurope\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_logger;
    protected $_clearpayConfig;
    protected $_moduleList;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Clearpay\ClearpayEurope\Model\Logger\Logger $logger,
        \Clearpay\ClearpayEurope\Model\Config\Payovertime $clearpayConfig,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        parent::__construct($context);
        $this->_logger = $logger;
        $this->_clearpayConfig = $clearpayConfig;
        $this->_moduleList = $moduleList;
    }

    public function debug($message, array $context = [])
    {
        if ($this->_clearpayConfig->isDebugEnabled()) {
            return $this->_logger->debug($message, $context);
        }
    }

    public function getModuleVersion()
    {
        $moduleInfo = $this->_moduleList->getOne('Clearpay_ClearpayEurope');
        return $moduleInfo['setup_version'];
    }
	
	public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
