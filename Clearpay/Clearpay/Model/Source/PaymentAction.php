<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2019 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\Source;

use \Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 * @package Clearpay\Clearpay\Model\Source
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Possible actions on order place
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorise and Capture'),
            ]
        ];
    }
}
