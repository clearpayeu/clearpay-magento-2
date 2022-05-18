<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Url\Lib;

class WidgetCheckoutLibUrlProvider extends LibUrlProvider
{
    private $config;
    private $localeResolver;

    public function __construct(
        \Clearpay\Clearpay\Model\Url\UrlBuilder $urlBuilder,
        \Clearpay\Clearpay\Model\Config $config,
        \Magento\Framework\Locale\Resolver $localeResolver
    ) {
        parent::__construct($urlBuilder);
        $this->config = $config;
        $this->localeResolver = $localeResolver;
    }

    protected function buildUrl(): string
    {
        $locale = $this->localeResolver->getLocale();
        if ($locale == 'en_GB') {
            return $this->urlBuilder->build(
                \Clearpay\Clearpay\Model\Url\UrlBuilder::TYPE_WEB_JS_LIB,
                'afterpay.js'
            );
        }
        return $this->urlBuilder->build(
            \Clearpay\Clearpay\Model\Url\UrlBuilder::TYPE_JS_LIB,
            'afterpay-1.x.js'
        );
    }
}
