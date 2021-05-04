<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
namespace Clearpay\ClearpayEurope\Model\GuestPaymentInformationManagement;

class Plugin
{
    /**
     * @var \Clearpay\ClearpayEurope\Model\Token
     */
    protected $token;

    /**
     * Plugin constructor.
     * @param \Clearpay\ClearpayEurope\Model\Token $token
     */
    public function __construct(
        \Clearpay\ClearpayEurope\Model\Token $token
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
