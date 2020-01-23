<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\Source;

/**
 * Class PaymentFlow
 * @package Clearpay\Clearpay\Model\Source
 */
class PaymentFlow implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * PaymentFlow constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
			['value' => 'immediate', 'label' => __('Immediate Payment Flow')],
			['value' => 'deferred', 'label' => __('Deferred Payment Flow')],
		];
    }
}
