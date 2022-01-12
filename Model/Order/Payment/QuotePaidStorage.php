<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Order\Payment;

use Magento\Sales\Model\Order\Payment;

class QuotePaidStorage
{
    private array $quotesOrderPayments = [];

    public function setClearpayPaymentForQuote(int $quoteId, Payment $clearpayPayment): self
    {
        $this->quotesOrderPayments[$quoteId] = $clearpayPayment;
        return $this;
    }

    public function getClearpayPaymentIfQuoteIsPaid(int $quoteId): ?Payment
    {
        return $this->quotesOrderPayments[$quoteId] ?? null;
    }
}
