<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\ErrorMessageMapper;

use Clearpay\Clearpay\Gateway\Validator\CaptureResponseValidator;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;

class CaptureErrorMessageMapper implements ErrorMessageMapperInterface
{
    public function getMessage(string $code)
    {
        switch ($code) {
            case CaptureResponseValidator::STATUS_DECLINED:
                return __('Clearpay payment declined. Please select an alternative payment method.');
            default:
                return null;
        }
    }
}
