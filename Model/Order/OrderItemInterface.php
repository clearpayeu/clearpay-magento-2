<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Order;

interface OrderItemInterface
{
    public function getClearpayRefundedQty(): float;

    public function setClearpayRefundedQty(float $qty): self;

    public function getClearpayVoidedQty(): float;

    public function setClearpayVoidedQty(float $qty): self;
}
