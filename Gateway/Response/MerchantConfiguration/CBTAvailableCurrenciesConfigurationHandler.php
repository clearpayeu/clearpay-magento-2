<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response\MerchantConfiguration;

class CBTAvailableCurrenciesConfigurationHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private \Clearpay\Clearpay\Model\Config  $config;

    public function __construct(
        \Clearpay\Clearpay\Model\Config $config
    ) {
        $this->config = $config;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        $cbtAvailableCurrencies = [];
        if (isset($response['CBT']['enabled']) &&
            isset($response['CBT']['limits']) &&
            is_array($response['CBT']['limits'])
        ) {
            foreach ($response['CBT']['limits'] as $limit) {
                if (isset($limit['maximumAmount']['currency']) && isset($limit['maximumAmount']['amount'])) {
                    $cbtAvailableCurrencies[] = $limit['maximumAmount']['currency'] . ':' . $limit['maximumAmount']['amount'];
                }
            }
        }
        $this->config->setCbtCurrencyLimits(implode(",", $cbtAvailableCurrencies), $websiteId);
    }
}
