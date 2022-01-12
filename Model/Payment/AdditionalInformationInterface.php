<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment;

interface AdditionalInformationInterface
{
    const CLEARPAY_ORDER_ID = 'clearpay_order_id';
    const CLEARPAY_OPEN_TO_CAPTURE_AMOUNT = 'clearpay_open_to_capture_amount';
    const CLEARPAY_PAYMENT_STATE = 'clearpay_payment_state';
    const CLEARPAY_AUTH_EXPIRY_DATE = 'clearpay_auth_expiry_date';
    const CLEARPAY_ROLLOVER_DISCOUNT = 'clearpay_rollover_discount';
    const CLEARPAY_CAPTURED_DISCOUNT = 'clearpay_captured_discount';
}
