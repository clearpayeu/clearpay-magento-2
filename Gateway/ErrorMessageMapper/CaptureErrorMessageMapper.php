<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\ErrorMessageMapper;

use Clearpay\Clearpay\Gateway\Validator\CaptureResponseValidator;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;

class CaptureErrorMessageMapper implements ErrorMessageMapperInterface
{
    public const STATUS_DECLINED_ERROR_MESSAGE = 'Clearpay payment declined. Please select an alternative payment method.';// @codingStandardsIgnoreLine

    public function getMessage(string $code)
    {
        $message = (string)self::STATUS_DECLINED_ERROR_MESSAGE;
        switch ($code) {
            case CaptureResponseValidator::STATUS_DECLINED:
                return __($message);
            default:
                return null;
        }
    }
}
