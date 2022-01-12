<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Url\Lib;

class ExpressCheckoutLibUrlProvider extends LibUrlProvider
{
    protected function buildUrl(): string
    {
        return $this->urlBuilder->build(
            \Clearpay\Clearpay\Model\Url\UrlBuilder::TYPE_WEB_JS_LIB,
            'afterpay.js?merchant_key=magento2'
        );
    }
}
