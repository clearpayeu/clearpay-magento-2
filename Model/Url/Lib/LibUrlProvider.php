<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Url\Lib;

abstract class LibUrlProvider
{
    protected \Clearpay\Clearpay\Model\Url\UrlBuilder $urlBuilder;
    protected static bool $isLibGotten = false;

    public function __construct(
        \Clearpay\Clearpay\Model\Url\UrlBuilder $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    public function getClearpayLib(): ?string
    {
        if (!self::$isLibGotten) {
            self::$isLibGotten = true;
            return $this->buildUrl();
        }
        return null;
    }

    public function getIsLibGotten(): bool
    {
        return self::$isLibGotten;
    }

    abstract protected function buildUrl(): string;
}
