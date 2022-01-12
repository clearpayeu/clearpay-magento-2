<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

class RedirectPath implements \Clearpay\Clearpay\Api\Data\RedirectPathInterface
{
    private $confirmPath;
    private $cancelPath;

    public function setConfirmPath(string $path): \Clearpay\Clearpay\Api\Data\RedirectPathInterface
    {
        $this->confirmPath = $path;
        return $this;
    }

    public function getConfirmPath(): string
    {
        return $this->confirmPath;
    }

    public function setCancelPath(string $path): \Clearpay\Clearpay\Api\Data\RedirectPathInterface
    {
        $this->cancelPath = $path;
        return $this;
    }

    public function getCancelPath(): string
    {
        return $this->cancelPath;
    }
}
