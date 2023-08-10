<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response\MerchantConfiguration;

class MpidConfigurationHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
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
        $mpid =$response['publicId']??"null";
        $this->config->setPublicId($mpid, $websiteId);
    }
}
