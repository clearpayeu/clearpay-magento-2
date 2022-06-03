<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Setup\Patch\Data;

class ClearpayEUAdaptPayments implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    protected const METHOD_CODE = 'clearpayeupayovertime';

    protected $salesSetup;

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
                        'replace(
                            replace(
                                additional_information,
                                "clearpay_payment_status",
                                "clearpay_payment_state"
                                ),
                            "APPROVED",
                            "CAPTURED"
                        )'
                    )
                ],
                ['method = ?' => static::METHOD_CODE]
            );

        return $this;
    }
}
