<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.Clearpay.com
 */
namespace Clearpay\Clearpay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 * @package Clearpay\Clearpay\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    const TERMS_CONDITION_LINK = "https://www.clearpay.co.uk/terms";
    /**
     * @var Config\Payovertime
     */
    protected $clearpayConfig;
    /**
     * @var Payovertime
     */
    protected $clearpayPayovertime;
    private $localeResolver;
    /**
     * ConfigProvider constructor.
     * @param Config\Payovertime $config
     */
    public function __construct(\Clearpay\Clearpay\Model\Config\Payovertime $config,\Clearpay\Clearpay\Model\Payovertime $clearpayPayovertime,\Magento\Framework\Locale\Resolver $localeResolver)
    {
        $this->clearpayConfig = $config;
        $this->clearpayPayovertime = $clearpayPayovertime;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Get config set on JS global variable window.checkoutConfig
     *
     * @return array
     */
    public function getConfig()
    {
        // set default array
        $config = [];

        /**
         * adding config array
         */
        $config = array_merge_recursive($config, [
            'payment' => [
                'clearpay' => [
                    'clearpayJs'        => $this->clearpayConfig->getWebUrl(),
                    'clearpayReturnUrl' => 'clearpay/payment/response',
                    'paymentAction'     => $this->clearpayConfig->getPaymentAction(),
                    'termsConditionUrl' => $this->clearpayConfig->getTermsAndConditionLink(),
                    'countryCode' => $this->clearpayConfig->getCurrentCountryCode(),
                    'currencyCode'     => $this->clearpayConfig->getCurrencyCode(),
                    'baseCurrencyCode'     => $this->clearpayPayovertime->getStoreCurrencyCode(),
                    'storeLocale' =>$this->localeResolver->getLocale()
                ],
            ],
        ]);

        return $config;
    }
}
