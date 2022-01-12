<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Order;

class OrderItem implements OrderItemInterface
{
    private float $refundedQty = 0;
    private float $voidedQty = 0;

    public function getClearpayRefundedQty(): float
    {
        return $this->refundedQty;
    }

    public function setClearpayRefundedQty(float $qty): OrderItemInterface
    {
        $this->refundedQty = $qty;
        return $this;
    }

    public function getClearpayVoidedQty(): float
    {
        return $this->voidedQty;
    }

    public function setClearpayVoidedQty(float $qty): OrderItemInterface
    {
        $this->voidedQty = $qty;
        return $this;
    }
}
