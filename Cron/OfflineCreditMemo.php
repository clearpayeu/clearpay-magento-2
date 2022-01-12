<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Cron;

class OfflineCreditMemo
{
    private $statusChanger;

    public function __construct(
        \Clearpay\Clearpay\Model\Order\CreditMemo\StatusChanger $statusChanger
    ) {
        $this->statusChanger = $statusChanger;
    }

    public function execute(): void
    {
        $this->statusChanger->execute();
    }
}
