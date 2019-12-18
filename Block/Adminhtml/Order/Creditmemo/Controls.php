<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2019 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Block\Adminhtml\Order\Creditmemo;

class Controls extends \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Creditmemo\Controls
{
    public function canRefundToCustomerBalance()
    {
		$creditMemo = $this->_coreRegistry->registry('current_creditmemo');
		$payment = $creditMemo->getOrder()->getPayment();
		if($payment->getMethod() == \Clearpay\Clearpay\Model\Payovertime::METHOD_CODE ){
			return false;
		}
        return true;
    }
}
