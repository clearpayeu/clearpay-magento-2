<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Api\Data;

/**
 * Interface CheckoutInterface
 * @api
 */
interface CheckoutInterface
{
    /**#@+
     * Checkout result keys
     */
    const CLEARPAY_TOKEN = 'clearpay_token';
    const CLEARPAY_AUTH_TOKEN_EXPIRES = 'clearpay_expires';
    const CLEARPAY_REDIRECT_CHECKOUT_URL = 'clearpay_redirectCheckoutUrl';
    /**#@-*/

    /**
     * @param string $token
     * @return static
     */
    public function setClearpayToken(string $token): self;

    /**
     * @return string
     */
    public function getClearpayToken(): string;

    /**
     * @param string $authTokenExpires
     * @return static
     */
    public function setClearpayAuthTokenExpires(string $authTokenExpires): self;

    /**
     * @return string
     */
    public function getClearpayAuthTokenExpires(): string;

    /**
     * @param string $redirectCheckoutUrl
     * @return static
     */
    public function setClearpayRedirectCheckoutUrl(string $redirectCheckoutUrl): self;

    /**
     * @return string
     */
    public function getClearpayRedirectCheckoutUrl(): string;
}
