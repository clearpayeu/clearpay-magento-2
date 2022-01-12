<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

class RedirectPath implements \Clearpay\Clearpay\Api\Data\RedirectPathInterface
{
    private string $confirmPath;
    private string $cancelPath;

    public function setConfirmPath(string $path): self
    {
        $this->confirmPath = $path;
        return $this;
    }

    public function getConfirmPath(): string
    {
        return $this->confirmPath;
    }

    public function setCancelPath(string $path): self
    {
        $this->cancelPath = $path;
        return $this;
    }

    public function getCancelPath(): string
    {
        return $this->cancelPath;
    }
}
