<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response\MerchantConfiguration;

class SpecificCountriesConfigurationHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private $config;

    public function __construct(
        \Clearpay\Clearpay\Model\Config $config
    ) {
        $this->config = $config;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        $merchantCountry = $this->config->getMerchantCountry(
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        $specificCountries = [];
        if ($merchantCountry != null) {
            $specificCountries[] = $merchantCountry;
        }
        $specificCountries = array_intersect($this->config->getAllowedCountries($websiteId), $specificCountries);
        if (isset($response['CBT']['enabled']) &&
            isset($response['CBT']['countries']) &&
            is_array($response['CBT']['countries']) &&
            count($specificCountries) > 0
        ) {
            $specificCountries = array_unique(array_merge($specificCountries, $response['CBT']['countries']));
        }
        $this->config->setSpecificCountries(implode(",", $specificCountries), $websiteId);
    }
}
