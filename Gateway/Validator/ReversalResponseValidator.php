<?php
declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Validator;

class ReversalResponseValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    public function validate(array $validationSubject): \Magento\Payment\Gateway\Validator\ResultInterface
    {
        return $this->createResult(true);
    }
}
