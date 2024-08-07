<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

interface PaymentStateInterface
{
    public const AUTH_APPROVED = 'AUTH_APPROVED';
    public const PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';
    public const CAPTURED = 'CAPTURED';
    public const VOIDED = 'VOIDED';
    public const EXPIRED = 'EXPIRED';
}
