<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_logger;
    protected $_clearpayConfig;
    protected $_moduleList;
    protected $_countryFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Clearpay\Clearpay\Model\Logger\Logger $logger,
        \Clearpay\Clearpay\Model\Config\Payovertime $clearpayConfig,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
		\Magento\Directory\Model\CountryFactory $countryFactory

    ) {
        parent::__construct($context);
        $this->_logger = $logger;
        $this->_clearpayConfig = $clearpayConfig;
        $this->_moduleList = $moduleList;
		$this->_countryFactory = $countryFactory;
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
	
	 public function getCbtCountry()
    {
		$cbtEnabled="Disabled";
        if($this->_clearpayConfig->isCbtEnabled()){
			$cbtEnabled = "Enabled";
			$cbtCountries = $this->_clearpayConfig->getCbtCountry();
			if(!empty($cbtCountries)){
				$cbtCountryCode=explode(",",$cbtCountries);
                $counrtyNames=[];
                foreach($cbtCountryCode AS $countryCode){
					if($country = $this->_countryFactory->create()->loadByCode($countryCode)){
						$counrtyNames[] = $country->getName();
					}
				}
				$cbtEnabled = $cbtEnabled." [ ".implode(" | ",$counrtyNames)." ]";
			}
		}
        return $cbtEnabled;
    }
	
	public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
