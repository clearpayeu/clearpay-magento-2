<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.Clearpay.com
 */
namespace Clearpay\Clearpay\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Disable extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setDisabled('disabled');
        return $element->getElementHtml();
    }
}
