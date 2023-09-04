<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Url\Lib;

class WidgetCheckoutLibUrlProvider extends LibUrlProvider
{
    private \Clearpay\Clearpay\Model\Config $config;

    public function __construct(
        \Clearpay\Clearpay\Model\Url\UrlBuilder $urlBuilder,
        \Clearpay\Clearpay\Model\Config $config

    ) {
        parent::__construct($urlBuilder);
        $this->config = $config;
     }

    protected function buildUrl(): string
    {
        return $this->urlBuilder->build(
            \Clearpay\Clearpay\Model\Url\UrlBuilder::TYPE_JS_LIB,
            'square-marketplace.js'
        );
    }
}
