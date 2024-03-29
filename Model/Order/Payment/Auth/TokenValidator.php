<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Order\Payment\Auth;

use Clearpay\Clearpay\Model\ResourceModel\Token;

class TokenValidator
{
    private array $results = [];
    private Token $tokensResource;

    public function __construct(Token $tokensResource)
    {
        $this->tokensResource = $tokensResource;
    }

    public function checkIsUsed(string $token): bool
    {
        if (!isset($this->results[$token])) {
            $this->results[$token] = (bool)$this->tokensResource->selectByToken($token);
        }

        return $this->results[$token];
    }
}
