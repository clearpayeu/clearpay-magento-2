<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

 use Clearpay\Clearpay\Api\Data\CheckoutInterface;

class Checkout implements \Clearpay\Clearpay\Api\Data\CheckoutInterface
{
    private $clearpayToken;
    private $clearpayAuthTokenExpires;
    private $clearpayRedirectCheckoutUrl;

    public function setClearpayToken(string $token): CheckoutInterface
    {
        $this->clearpayToken = $token;
        return $this;
    }

    public function getClearpayToken(): string
    {
        return $this->clearpayToken;
    }

    public function setClearpayAuthTokenExpires(string $authTokenExpires): CheckoutInterface
    {
        $this->clearpayAuthTokenExpires = $authTokenExpires;
        return $this;
    }

    public function getClearpayAuthTokenExpires(): string
    {
        return $this->clearpayAuthTokenExpires;
    }

    public function setClearpayRedirectCheckoutUrl(string $redirectCheckoutUrl): CheckoutInterface
    {
        $this->clearpayRedirectCheckoutUrl = $redirectCheckoutUrl;
        return $this;
    }

    public function getClearpayRedirectCheckoutUrl(): string
    {
        return $this->clearpayRedirectCheckoutUrl;
    }
}
