<?php

declare(strict_types=1);

namespace Clearpay\Clearpay\ViewModel\Container\Cta;

class Lib extends \Clearpay\Clearpay\ViewModel\Container\Lib
{
    public function getMinTotalValue(): ?string
    {
        return $this->config->getMinOrderTotal();
    }

    public function getMaxTotalValue(): ?string
    {
        return $this->config->getMaxOrderTotal();
    }
}
