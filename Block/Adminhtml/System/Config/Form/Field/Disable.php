<?php

declare(strict_types=1);

namespace Clearpay\Clearpay\Block\Adminhtml\System\Config\Form\Field;

class Disable extends \Magento\Config\Block\System\Config\Form\Field
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        /** @phpstan-ignore-next-line */
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        /** @phpstan-ignore-next-line */
        $element->setDisabled('disabled');
        return $element->getElementHtml();
    }
}
