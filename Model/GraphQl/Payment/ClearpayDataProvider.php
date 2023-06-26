<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\GraphQl\Payment;

class ClearpayDataProvider implements \Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface
{
    public function getData(array $data): array
    {
        if (!isset($data[$data['code']]['clearpay_token'])) {
            return [];
        }

        return $data[$data['code']];
    }
}
