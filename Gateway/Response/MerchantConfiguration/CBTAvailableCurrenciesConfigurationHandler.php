<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response\MerchantConfiguration;

use Clearpay\Clearpay\Model\Config;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Framework\Serialize\SerializerInterface;

class CBTAvailableCurrenciesConfigurationHandler implements HandlerInterface
{
    private $config;
    private $serializer;

    public function __construct(
        Config              $config,
        SerializerInterface $serializer
    ) {
        $this->config = $config;
        $this->serializer = $serializer;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        $cbtAvailableCurrencies = '';

        if (isset($response['CBT']['enabled']) &&
            isset($response['CBT']['limits']) &&
            is_array($response['CBT']['limits'])
        ) {
            $cbtAvailableCurrencies = $this->serializer->serialize($response['CBT']['limits']);
        }

        $this->config->setCbtCurrencyLimits($cbtAvailableCurrencies, $websiteId);
    }
}
