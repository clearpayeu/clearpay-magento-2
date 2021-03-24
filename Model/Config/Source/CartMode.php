<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\Config\Source;

/**
 * Class CartMode
 * @package Clearpay\Clearpay\Model\Config\Source
 */
class CartMode implements \Magento\Framework\Option\ArrayInterface
{
    const MAGENTO_CHECKOUT = 1;
    const EXPRESS_CHECKOUT = 2;
    const DISABLED = 0;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::MAGENTO_CHECKOUT, 'label' => __('Yes - Magento Checkout')],
            ['value' => self::EXPRESS_CHECKOUT, 'label' => __('Yes - Express Checkout')],
            ['value' => self::DISABLED, 'label' => __('No')],
		];
    }
}
