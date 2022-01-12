<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Setup\Patch\Data;

class AdaptPayments implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private const METHOD_CODE = 'clearpaypayovertime';

    private \Magento\Sales\Setup\SalesSetup $salesSetup;

    public function __construct(
        \Magento\Sales\Setup\SalesSetup $salesSetup
    ) {
        $this->salesSetup = $salesSetup;
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
        $this->salesSetup->getConnection()
            ->update(
                $this->salesSetup->getTable('sales_order_payment'),
                [
                    'method' => \Clearpay\Clearpay\Gateway\Config\Config::CODE,
                    'additional_information' => new \Zend_Db_Expr(
                        'replace(additional_information, "clearpay_payment_status", "clearpay_payment_state")'
                    )
                ],
                ['method = ?' => self::METHOD_CODE]
            );

        return $this;
    }
}
