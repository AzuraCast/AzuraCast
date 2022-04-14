<?php

declare(strict_types=1);

namespace App\Entity;

use App\Service\DeviceDetector\DeviceResult;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'listener_user_agents'),
]
class ListenerUserAgent
{
    use Traits\TruncateStrings;

    #[
        ORM\Column(length: 255, nullable: false),
        ORM\Id
    ]
    protected string $user_agent;

    #[ORM\Column(length: 255)]
    protected ?string $client = null;

    #[ORM\Column]
    protected bool $is_browser = false;

    #[ORM\Column]
    protected bool $is_mobile = false;

    #[ORM\Column]
    protected bool $is_bot = false;

    #[ORM\Column(length: 150)]
    protected ?string $browser_family = null;

    #[ORM\Column(length: 150)]
    protected ?string $os_family = null;

    #[ORM\OneToMany(mappedBy: 'userAgentDetails', targetEntity: Listener::class)]
    protected Collection $listeners;

    public function __construct(
        string $userAgent
    ) {
        $this->user_agent = $this->truncateString($userAgent);
        $this->listeners = new ArrayCollection();
    }

    public function getUserAgent(): string
    {
        return $this->user_agent;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(?string $client): void
    {
        $this->client = $this->truncateNullableString($client);
    }

    public function isBrowser(): bool
    {
        return $this->is_browser;
    }

    public function setIsBrowser(bool $is_browser): void
    {
        $this->is_browser = $is_browser;
    }

    public function isMobile(): bool
    {
        return $this->is_mobile;
    }

    public function setIsMobile(bool $is_mobile): void
    {
        $this->is_mobile = $is_mobile;
    }

    public function isBot(): bool
    {
        return $this->is_bot;
    }

    public function setIsBot(bool $is_bot): void
    {
        $this->is_bot = $is_bot;
    }

    public function getBrowserFamily(): ?string
    {
        return $this->browser_family;
    }

    public function setBrowserFamily(?string $browser_family): void
    {
        $this->browser_family = $this->truncateNullableString($browser_family, 150);
    }

    public function getOsFamily(): ?string
    {
        return $this->os_family;
    }

    public function setOsFamily(?string $os_family): void
    {
        $this->os_family = $this->truncateNullableString($os_family, 150);
    }

    /**
     * @return Collection<Listener>
     */
    public function getListeners(): Collection
    {
        return $this->listeners;
    }

    public static function fromDeviceResult(DeviceResult $row): self
    {
        $record = new self($row->userAgent);
        $record->setClient($row->client);
        $record->setIsBrowser($row->isBrowser);
        $record->setIsMobile($row->isMobile);
        $record->setIsBot($row->isBot);
        $record->setBrowserFamily($row->browserFamily);
        $record->setOsFamily($row->osFamily);

        return $record;
    }
}
