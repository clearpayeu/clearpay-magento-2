<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Order\CreditMemo;

class StatusChanger
{
    private OrdersRetriever $ordersRetriever;
    private CreditMemoProcessor $creditMemoProcessor;
    private \Psr\Log\LoggerInterface $logger;

    public function __construct(
        OrdersRetriever $ordersRetriever,
        CreditMemoProcessor $creditMemoProcessor,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->ordersRetriever = $ordersRetriever;
        $this->creditMemoProcessor = $creditMemoProcessor;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $orders = $this->ordersRetriever->getClearpayOrders();
        foreach ($orders as $order) {
            try {
                $this->creditMemoProcessor->processOrder($order);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
