<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Setup\Patch\Data;

use Clearpay\Clearpay\Model\Payment\PaymentErrorProcessor;
use Magento\Framework\Exception\AlreadyExistsException;

class ClearpayPendingExceptionReviewOrderStatus implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private $statusFactory;
    private $statusResource;

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
            'label'  => 'Pending Exception Review (Clearpay)',
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
