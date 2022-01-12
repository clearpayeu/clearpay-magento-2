<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\CheckoutManagement;

use Clearpay\Clearpay\Api\Data\CheckoutInterface;
use Clearpay\Clearpay\Api\Data\RedirectPathInterface;

class CheckoutManagement implements \Clearpay\Clearpay\Api\CheckoutManagementInterface
{
    private $checkoutCommand;
    private $expressCheckoutCommand;
    private $cartRepository;
    private $maskedQuoteIdToQuoteId;
    private $checkoutFactory;
    private $expressCheckoutValidator;
    private $checkoutValidator;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $checkoutCommand,
        \Magento\Payment\Gateway\CommandInterface $expressCheckoutCommand,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        \Clearpay\Clearpay\Api\Data\CheckoutInterfaceFactory $checkoutFactory,
        ?\Clearpay\Clearpay\Model\Spi\CheckoutValidatorInterface $checkoutValidator = null,
        ?\Clearpay\Clearpay\Model\Spi\CheckoutValidatorInterface $expressCheckoutValidator = null
    ) {
        $this->checkoutCommand = $checkoutCommand;
        $this->expressCheckoutCommand = $expressCheckoutCommand;
        $this->cartRepository = $cartRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->checkoutFactory = $checkoutFactory;
        $this->checkoutValidator = $checkoutValidator;
        $this->expressCheckoutValidator = $expressCheckoutValidator;
    }

    public function create(string $cartId, RedirectPathInterface $redirectPath): CheckoutInterface
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->getActiveQuoteByCartOrQuoteId($cartId);

        $this->cartRepository->save($quote->reserveOrderId());
        if ($this->checkoutValidator !== null) {
            $this->checkoutValidator->validate($quote);
        }
        $this->checkoutCommand->execute(['quote' => $quote, 'redirect_path' => $redirectPath]);

        return $this->createCheckout($quote->getPayment());
    }

    public function createExpress(string $cartId, string $popupOriginUrl): CheckoutInterface
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->getActiveQuoteByCartOrQuoteId($cartId);

        $this->cartRepository->save($quote->reserveOrderId());
        if ($this->expressCheckoutValidator !== null) {
            $this->expressCheckoutValidator->validate($quote);
        }
        $this->expressCheckoutCommand->execute(['quote' => $quote, 'popup_origin_url' => $popupOriginUrl]);

        return $this->createCheckout($quote->getPayment());
    }

    private function createCheckout(\Magento\Payment\Model\InfoInterface $payment): CheckoutInterface
    {
        return $this->checkoutFactory->create()
            ->setClearpayToken(
                $payment->getAdditionalInformation(CheckoutInterface::CLEARPAY_TOKEN)
            )->setClearpayAuthTokenExpires(
                $payment->getAdditionalInformation(CheckoutInterface::CLEARPAY_AUTH_TOKEN_EXPIRES)
            )->setClearpayRedirectCheckoutUrl(
                $payment->getAdditionalInformation(CheckoutInterface::CLEARPAY_REDIRECT_CHECKOUT_URL)
            );
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getActiveQuoteByCartOrQuoteId(string $cartId): \Magento\Quote\Api\Data\CartInterface
    {
        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $quoteId = (int)$cartId;
        }
        return $this->cartRepository->getActive($quoteId);
    }
}
