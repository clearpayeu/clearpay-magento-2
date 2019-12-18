<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2019 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Block\Adminhtml\Order\Creditmemo\Create;

class Items extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items
{
    protected function _prepareLayout()
    {
		parent::_prepareLayout();
		$payment = $this->getCreditmemo()->getOrder()->getPayment();
		if($payment->getMethod() == \Clearpay\Clearpay\Model\Payovertime::METHOD_CODE ){
			$this->unsetChild(
                'submit_offline'
            );
		}
    }
}
