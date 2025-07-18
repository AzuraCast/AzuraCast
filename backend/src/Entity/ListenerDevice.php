<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Embeddable]
final readonly class ListenerDevice implements JsonSerializable
{
    #[ORM\Column(length: 255)]
    public ?string $client;

    #[ORM\Column]
    public bool $is_browser;

    #[ORM\Column]
    public bool $is_mobile;

    #[ORM\Column]
    public bool $is_bot;

    #[ORM\Column(length: 150, nullable: true)]
    public ?string $browser_family;

    #[ORM\Column(length: 150, nullable: true)]
    public ?string $os_family;

    public function __construct(
        ?string $client,
        bool $is_browser,
        bool $is_mobile,
        bool $is_bot,
        ?string $browser_family,
        ?string $os_family
    ) {
        $this->client = $client;
        $this->is_browser = $is_browser;
        $this->is_mobile = $is_mobile;
        $this->is_bot = $is_bot;
        $this->browser_family = $browser_family;
        $this->os_family = $os_family;
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
