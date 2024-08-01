<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Embeddable]
class ListenerDevice implements JsonSerializable
{
    #[ORM\Column(length: 255)]
    protected ?string $client = null;

    #[ORM\Column]
    protected bool $is_browser = false;

    #[ORM\Column]
    protected bool $is_mobile = false;

    #[ORM\Column]
    protected bool $is_bot = false;

    #[ORM\Column(length: 150, nullable: true)]
    protected ?string $browser_family = null;

    #[ORM\Column(length: 150, nullable: true)]
    protected ?string $os_family = null;

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function isBrowser(): bool
    {
        return $this->is_browser;
    }

    public function isMobile(): bool
    {
        return $this->is_mobile;
    }

    public function isBot(): bool
    {
        return $this->is_bot;
    }

    public function getBrowserFamily(): ?string
    {
        return $this->browser_family;
    }

    public function getOsFamily(): ?string
    {
        return $this->os_family;
    }

    public function jsonSerialize(): array
    {
        return [
            'client' => $this->client,
            'is_browser' => $this->is_browser,
            'is_mobile' => $this->is_mobile,
            'is_bot' => $this->is_bot,
            'browser_family' => $this->browser_family,
            'os_family' => $this->os_family,
        ];
    }
}
