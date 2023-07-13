<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Plugin\QuoteGraphQl\Cart;

use Clearpay\Clearpay\Api\Data\CheckoutInterface;
use Clearpay\Clearpay\Gateway\Config\Config;
use Clearpay\Clearpay\Model\Payment\Capture\PlaceOrderProcessor;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\PlaceOrder as PlaceOrderModel;

class PlaceOrderPlugin
{
    private PlaceOrderProcessor $placeOrderProcessor;
    private CommandInterface $validateCheckoutDataCommand;

    public function __construct(
        PlaceOrderProcessor   $placeOrderProcessor,
        CommandInterface      $validateCheckoutDataCommand
    ) {
        $this->placeOrderProcessor = $placeOrderProcessor;
        $this->validateCheckoutDataCommand = $validateCheckoutDataCommand;
    }

    public function aroundExecute(
        PlaceOrderModel $subject,
        callable        $proceed,
        Quote           $cart,
        string          $maskedCartId,
        int             $userId
    ): int {
        $payment = $cart->getPayment();
        if ($payment->getMethod() === Config::CODE) {
            $clearpayOrderToken = $payment->getAdditionalInformation(CheckoutInterface::CLEARPAY_TOKEN);

            return $this->placeOrderProcessor->execute($cart, $this->validateCheckoutDataCommand, $clearpayOrderToken);
        }

        return $proceed($cart, $maskedCartId, $userId);
    }
}
