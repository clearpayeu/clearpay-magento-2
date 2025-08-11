<?php
declare(strict_types=1);

namespace Clearpay\Clearpay\Plugin\Quote;

use Clearpay\Clearpay\Gateway\Config\Config;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;

class CheckoutManagement
{
    private CartRepositoryInterface $quoteRepository;
    private Session $checkoutSession;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Session                 $checkoutSession
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
    }

    public function beforePlaceOrder(CartManagementInterface $subject, $cartId, ?PaymentInterface $paymentMethod = null)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        $payment = $quote->getPayment();
        if (($payment->getMethod() === Config::CODE) && !$this->checkoutSession->getClearpayRedirect()) {
            throw new LocalizedException(__('You cannot use the chosen payment method.'));
        }

        return [$cartId, $paymentMethod];
    }
}
