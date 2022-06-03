<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Setup\Patch\Data;

class ClearpayEUAdaptCapturedDiscounts extends AdaptCapturedDiscounts
{
    public static function getDependencies()
    {
        return [
            \Clearpay\Clearpay\Setup\Patch\Data\ClearpayEUAdaptPayments::class
        ];
    }
}
