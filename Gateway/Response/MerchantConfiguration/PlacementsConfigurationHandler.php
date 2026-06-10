<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Response\MerchantConfiguration;

class PlacementsConfigurationHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private \Clearpay\Clearpay\Model\Config $config;
    private \Clearpay\Clearpay\Model\Placement\Service\GetPlacementData $getPlacementData;

    public function __construct(
        \Clearpay\Clearpay\Model\Config $config,
        \Clearpay\Clearpay\Model\Placement\Service\GetPlacementData $getPlacementData
    ) {
        $this->config = $config;
        $this->getPlacementData = $getPlacementData;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        $mpid = $response['publicId'] ?? '';
        $placemntsData = $this->getPlacementData->execute($mpid);
        foreach ($placemntsData as $placement) {
            switch ($placement['pageType']) {
                case 'product':
                    $this->config->setPlacementIdPdp($placement['placementId'], $websiteId);
                    break;
                case 'cart':
                    $this->config->setPlacementIdCart($placement['placementId'], $websiteId);
                    break;
            }
        }
    }
}

