<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\ErrorMessageMapper;

use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;

class MerchantConfigurationErrorMessageMapper implements ErrorMessageMapperInterface
{
    public function getMessage(string $code)
    {
        switch ($code) {
            case 'unauthorized':
                return __('Clearpay merchant configuration fetching is failed. Wrong credentials.');
            default:
                return __('Clearpay merchant configuration fetching is failed. See logs.');
        }
    }
}
