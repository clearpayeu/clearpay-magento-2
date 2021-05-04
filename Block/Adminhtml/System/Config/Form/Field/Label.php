<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\ClearpayEurope\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use \Clearpay\ClearpayEurope\Helper\Data as ClearpayHelper;

class Label extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $helper;

    /**
     * Call constructor.
     * @param ClearpayHelper $helper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ClearpayHelper $helper
    ) {
        $this->helper = $helper;
        parent::__construct($context);
    }


    protected function _getElementHtml(AbstractElement $element)
    {
        $version = $this->helper->getModuleVersion();
        return $version;
    }
}
