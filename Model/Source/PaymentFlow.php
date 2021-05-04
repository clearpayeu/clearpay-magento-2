<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\ClearpayEurope\Model\Source;

/**
 * Class PaymentFlow
 * @package Clearpay\ClearpayEurope\Model\Source
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
			//['value' => 'deferred', 'label' => __('Deferred Payment Flow')],
		];
    }
}
