<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2018 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_logger;
    protected $_clearpayConfig;
    protected $_moduleList;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Clearpay\Clearpay\Model\Logger\Logger $logger,
        \Clearpay\Clearpay\Model\Config\Payovertime $clearpayConfig,
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
        $moduleInfo = $this->_moduleList->getOne('Clearpay_Clearpay');
        return $moduleInfo['setup_version'];
    }
}
