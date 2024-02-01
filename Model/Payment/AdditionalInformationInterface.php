<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment;

interface AdditionalInformationInterface
{
    public const CLEARPAY_ORDER_ID = 'clearpay_order_id';
    public const CLEARPAY_OPEN_TO_CAPTURE_AMOUNT = 'clearpay_open_to_capture_amount';
    public const CLEARPAY_PAYMENT_STATE = 'clearpay_payment_state';
    public const CLEARPAY_AUTH_EXPIRY_DATE = 'clearpay_auth_expiry_date';
    public const CLEARPAY_ROLLOVER_DISCOUNT = 'clearpay_rollover_discount';
    public const CLEARPAY_CAPTURED_DISCOUNT = 'clearpay_captured_discount';
}
