<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.Clearpay.com
 */
namespace Clearpay\Clearpay\Model\Source;

class PaymentDisplay implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Option to set redirect or lightbox
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'redirect',
                'label' => 'Redirect',
            ],
            [
                'value' => 'lightbox',
                'label' => 'Lightbox',
            ]

        ];
    }
}
