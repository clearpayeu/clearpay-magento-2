<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.Clearpay.com
 */
namespace Clearpay\Clearpay\Model;

class Token
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     * @var \Magento\Checkout\Model\Session
     */
    protected $jsonHelper;
    protected $checkoutSession;

    /**
     * Token constructor.
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param $return
     * @return string
     */
    public function saveAndReturnToken($return)
    {
        // checking if clearpay payment is being use
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();

        if ($payment->getMethod() == \Clearpay\Clearpay\Model\Payovertime::METHOD_CODE) {
            $data = $payment->getAdditionalInformation(\Clearpay\Clearpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);
            $return = $this->jsonHelper->jsonEncode([
                'token' => $data
            ]);
        }

        return $return;
    }
}
