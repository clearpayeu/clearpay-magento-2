<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Payment\Capture;

use Clearpay\Clearpay\Api\Data\CheckoutInterface;
use Clearpay\Clearpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface;
use Clearpay\Clearpay\Model\Order\Payment\Auth\TokenSaver;
use Clearpay\Clearpay\Model\Order\Payment\Auth\TokenValidator;
use Clearpay\Clearpay\Model\Payment\AdditionalInformationInterface;
use Clearpay\Clearpay\Model\Payment\PaymentErrorProcessor;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;

class PlaceOrderProcessor
{
    private CartManagementInterface $cartManagement;
    private PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability;
    private PaymentErrorProcessor $paymentErrorProcessor;
    private TokenValidator $tokenValidator;
    private TokenSaver $tokenSaver;
    private OrderRepositoryInterface $orderRepository;
    private Session $checkoutSession;

    public function __construct(
        CartManagementInterface               $cartManagement,
        PaymentDataObjectFactoryInterface     $paymentDataObjectFactory,
        CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability,
        PaymentErrorProcessor                 $paymentErrorProcessor,
        TokenValidator                        $tokenValidator,
        TokenSaver                            $tokenSaver,
        OrderRepositoryInterface              $orderRepository,
        Session                               $checkoutSession
    ) {
        $this->cartManagement = $cartManagement;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
        $this->paymentErrorProcessor = $paymentErrorProcessor;
        $this->tokenValidator = $tokenValidator;
        $this->tokenSaver = $tokenSaver;
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(Quote $quote, CommandInterface $checkoutDataCommand, string $clearpayOrderToken): int
    {
        if ($this->tokenValidator->checkIsUsed($clearpayOrderToken)) {
            return 0;
        }

        $payment = $quote->getPayment();
        try {
            $payment->setAdditionalInformation(CheckoutInterface::CLEARPAY_TOKEN, $clearpayOrderToken);
            $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
            $payment->setAdditionalInformation(CheckoutInterface::CLEARPAY_IS_CBT_CURRENCY, $isCBTCurrencyAvailable);
            $payment->setAdditionalInformation(CheckoutInterface::CLEARPAY_CBT_CURRENCY, $quote->getQuoteCurrencyCode());

            if (!$quote->getCustomerId()) {
                $quote->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
            }

            if (!$payment->getQuote() || !$payment->getQuote()->getId()) {
                $payment->setQuote($quote);
            }

            $checkoutDataCommand->execute(['payment' => $this->paymentDataObjectFactory->create($payment)]);
            $this->checkoutSession->setClearpayRedirect(true);
            $orderId = (int)$this->cartManagement->placeOrder($quote->getId());
        } catch (\Throwable $e) {
            $orderId = $this->paymentErrorProcessor->execute($quote, $e, $payment);
        }

        $order = $this->orderRepository->get($orderId);
        /** @var \Magento\Payment\Model\InfoInterface $orderPayment */
        $orderPayment = $order->getPayment();
        $this->tokenSaver->execute(
            $orderId,
            $clearpayOrderToken,
            $orderPayment->getAdditionalInformation(AdditionalInformationInterface::CLEARPAY_AUTH_EXPIRY_DATE)
        );
        $this->checkoutSession->setClearpayRedirect(false);

        return $orderId;
    }
}
