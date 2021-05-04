<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\ClearpayEurope\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\Resolver;

/**
 * Class ConfigProvider
 * @package Clearpay\ClearpayEurope\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
	const TERMS_CONDITION_LINK = "https://www.clearpay.co.uk/terms";
    /**
     * @var Config\Payovertime
     */
    protected $clearpayConfig;

    /**
     * @var string[]
     */
    protected $terms_links = array(
        'es' => "https://www.clearpay.com/es/terms/",
        'fr' => "https://www.clearpay.com/fr/terms/",
        'it' => "https://www.clearpay.com/it/terms/",
        );

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * ConfigProvider constructor.
     * @param Config\Payovertime $config
     * @param Resolver $resolver
     */
    public function __construct(\Clearpay\ClearpayEurope\Model\Config\Payovertime $config, Resolver $resolver)
    {
        $this->clearpayConfig = $config;
        $this->resolver = $resolver;
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

        $locale = strtolower(strstr($this->resolver->getLocale(), '_', true));
		$termsConditionUrl = self::TERMS_CONDITION_LINK;
		if(in_array($locale,['es','fr','it'])){
			$termsConditionUrl = $this->terms_links[$locale];
		}
		
        /**
         * adding config array
         */
        $config = array_merge_recursive($config, [
            'payment' => [
                'clearpayeu' => [
                    'clearpayJs'        => $this->clearpayConfig->getWebUrl('clearpay.js'),
                    'clearpayReturnUrl' => 'clearpayeurope/payment/response',
                    'paymentAction'     => $this->clearpayConfig->getPaymentAction(),
                    'termsConditionUrl' => $termsConditionUrl,
                    'termsAndConditions' => __('Terms & Conditions'),
                    'termsConditionText' => __('You will be redirected to the Clearpay website when you proceed to checkout.'),
                    'currencyCode'     => $this->clearpayConfig->getCurrencyCode(),
                    'locale'            => $locale,
                    'clearpayFirstInstalmentText' => __('First instalment'),
                    'clearpaySecondInstalmentText' => __('2 weeks later'),
                    'clearpayThirdInstalmentText' => __('4 weeks later'),
                    'clearpayFourthInstalmentText' => __('6 weeks later'),
                    'clearpayCheckoutText' => __('Four interest-free payments totalling'),
                    'buttonText' => __('Continue to Clearpay')
                ],
            ],
        ]);

        return $config;
    }
}
