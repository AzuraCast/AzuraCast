<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[
    ORM\Embeddable,
    OA\Schema(
        schema: 'Api_ListenerDevice',
        type: 'object'
    )]
final readonly class ListenerDevice implements JsonSerializable
{
    #[
        ORM\Column(length: 255),
        OA\Property(
            description: 'Summary of the listener client.',
            example: 'Firefox 121.0, Windows'
        )
    ]
    public ?string $client;

    #[
        ORM\Column,
        OA\Property(
            description: 'If the listener device is likely a browser.',
            example: true
        )
    ]
    public bool $is_browser;

    #[
        ORM\Column,
        OA\Property(
            description: 'If the listener device is likely a mobile device.',
            example: true
        )
    ]
    public bool $is_mobile;

    #[
        ORM\Column,
        OA\Property(
            description: 'If the listener device is likely a crawler.',
            example: true
        )
    ]
    public bool $is_bot;

    #[
        ORM\Column(length: 150, nullable: true),
        OA\Property(
            description: 'Summary of the listener browser family.',
            example: 'Firefox'
        )
    ]
    public ?string $browser_family;

    #[
        ORM\Column(length: 150, nullable: true),
        OA\Property(
            description: 'Summary of the listener OS family.',
            example: 'Windows'
        )
    ]
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

    public static function fromArray(array $row): self
    {
        return new self(
            client: Types::stringOrNull($row['client'] ?? null),
            is_browser: Types::bool($row['is_browser'] ?? null),
            is_mobile: Types::bool($row['is_mobile'] ?? null),
            is_bot: Types::bool($row['is_bot'] ?? null),
            browser_family: Types::stringOrNull($row['browser_family'] ?? null),
            os_family: Types::stringOrNull($row['os_family'] ?? null)
        );
    }
}
