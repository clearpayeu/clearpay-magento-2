<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\ClearpayEurope\Model;

/**
 * Class Status
 * @package Clearpay\ClearpayEurope\Model
 */
class Status
{
    /**
     * Constant variable to manage status responds
     */
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_DECLINED = 'DECLINED';
    const STATUS_FAILED = 'FAILED';
}
