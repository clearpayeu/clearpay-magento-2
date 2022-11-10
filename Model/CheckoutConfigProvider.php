<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

class CheckoutConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    private \Magento\Framework\Locale\Resolver $localeResolver;

    private \Magento\Checkout\Model\Session $checkoutSession;

    private \Clearpay\Clearpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability;

    public function __construct(
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Clearpay\Clearpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability
    ) {
        $this->localeResolver = $localeResolver;
        $this->checkoutSession = $checkoutSession;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
    }

    public function getConfig(): array
    {
        $quote = $this->checkoutSession->getQuote();

        return [
            'payment' => [
                'clearpay' => [
                    'locale' => $this->localeResolver->getLocale(),
                    'isCBTCurrency' => $this->checkCBTCurrencyAvailability->checkByQuote($quote)
                ]
            ]
        ];
    }
}
