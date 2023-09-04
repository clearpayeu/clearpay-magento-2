<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Url\Lib;

class CtaLibUrlProvider extends LibUrlProvider
{
    protected function buildUrl(): string
    {
        return $this->urlBuilder->build(
            \Clearpay\Clearpay\Model\Url\UrlBuilder::TYPE_JS_LIB,
            'square-marketplace.js'
        );
    }
}
