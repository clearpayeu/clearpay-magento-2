<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Setup\Patch\Data;

use Magento\Framework\Exception\AlreadyExistsException;
use Clearpay\Clearpay\Model\Payment\PaymentErrorProcessor;

class ClearpayPendingExceptionReviewOrderStatus implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private \Magento\Sales\Model\Order\StatusFactory $statusFactory;

    private \Magento\Sales\Model\ResourceModel\Order\Status $statusResource;

    public function __construct(
        \Magento\Sales\Model\Order\StatusFactory        $statusFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status $statusResource
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResource = $statusResource;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function apply(): self
    {
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => PaymentErrorProcessor::ORDER_STATUS_CODE,
            'label' => 'Pending Exception Review (Clearpay)',
        ]);

        try {
            $this->statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return $this;
        }

        $status->assignState('processing', false, false);
        return $this;
    }
}
