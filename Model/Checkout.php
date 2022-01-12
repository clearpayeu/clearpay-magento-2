<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

class Checkout implements \Clearpay\Clearpay\Api\Data\CheckoutInterface
{
    private string $clearpayToken;
    private string $clearpayAuthTokenExpires;
    private string $clearpayRedirectCheckoutUrl;

    public function setClearpayToken(string $token): self
    {
        $this->clearpayToken = $token;
        return $this;
    }

    public function getClearpayToken(): string
    {
        return $this->clearpayToken;
    }

    public function setClearpayAuthTokenExpires(string $authTokenExpires): self
    {
        $this->clearpayAuthTokenExpires = $authTokenExpires;
        return $this;
    }

    public function getClearpayAuthTokenExpires(): string
    {
        return $this->clearpayAuthTokenExpires;
    }

    public function setClearpayRedirectCheckoutUrl(string $redirectCheckoutUrl): self
    {
        $this->clearpayRedirectCheckoutUrl = $redirectCheckoutUrl;
        return $this;
    }

    public function getClearpayRedirectCheckoutUrl(): string
    {
        return $this->clearpayRedirectCheckoutUrl;
    }
}
