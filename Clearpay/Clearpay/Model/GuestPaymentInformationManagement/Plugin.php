<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2018 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\GuestPaymentInformationManagement;

class Plugin
{
    /**
     * @var \Clearpay\Clearpay\Model\Token
     */
    protected $token;

    /**
     * Plugin constructor.
     * @param \Clearpay\Clearpay\Model\Token $token
     */
    public function __construct(
        \Clearpay\Clearpay\Model\Token $token
    ) {
        $this->token = $token;
    }

    /**
     * @param \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
     * @param $returnValue
     * @return string
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\GuestPaymentInformationManagement $subject,
        $returnValue
    ) {
        return $this->token->saveAndReturnToken($returnValue);
    }
}
