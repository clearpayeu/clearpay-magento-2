<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

class CheckoutConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    private $localeResolver;

    public function __construct(
        \Magento\Framework\Locale\Resolver $localeResolver
    ) {
        $this->localeResolver = $localeResolver;
    }

    public function getConfig(): array
    {
        return [
            'payment' => [
                'clearpay' => [
                    'locale' => $this->localeResolver->getLocale()
                ]
            ]
        ];
    }
}
