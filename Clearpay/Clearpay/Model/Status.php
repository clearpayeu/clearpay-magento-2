<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2018 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model;

/**
 * Class Status
 * @package Clearpay\Clearpay\Model
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
