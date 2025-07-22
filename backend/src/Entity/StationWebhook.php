<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Types;
use App\Webhook\Enums\WebhookTriggers;
use App\Webhook\Enums\WebhookTypes;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_webhooks', options: ['charset' => 'utf8mb4', 'collate' => 'utf8mb4_unicode_ci']),
    Attributes\Auditable
]
final class StationWebhook implements
    Stringable,
    Interfaces\StationAwareInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const string LAST_SENT_TIMESTAMP_KEY = 'last_message_sent';

    #[
        ORM\ManyToOne(inversedBy: 'webhooks'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    public Station $station;

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[
        OA\Property(
            description: "The nickname of the webhook connector.",
            example: "Twitter Post"
        ),
        ORM\Column(length: 100, nullable: true)
    ]
    public ?string $name = null {
        set => $this->truncateNullableString($value, 100);
    }

    #[
        OA\Property(
            description: "The type of webhook connector to use.",
            example: "twitter"
        ),
        ORM\Column(type: "string", length: 100, enumType: WebhookTypes::class),
        Assert\NotBlank
    ]
    public readonly WebhookTypes $type;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    public bool $is_enabled = true;

    #[
        OA\Property(
            description: "List of events that should trigger the webhook notification.",
            type: "array",
            items: new OA\Items()
        ),
        ORM\Column(type: 'json', nullable: true)
    ]
    public ?array $triggers = null;

    #[
        OA\Property(
            description: "Detailed webhook configuration (if applicable)",
            type: "array",
            items: new OA\Items()
        ),
        ORM\Column(type: 'json', nullable: true)
    ]
    public ?array $config = null;

    #[
        OA\Property(
            description: "Internal details used by the webhook to preserve state.",
            type: "array",
            items: new OA\Items()
        ),
        ORM\Column(type: 'json', nullable: true),
        Attributes\AuditIgnore
    ]
    public ?array $metadata = null;

    public function __construct(Station $station, WebhookTypes $type)
    {
        $this->station = $station;
        $this->type = $type;
    }

    public function hasTrigger(WebhookTriggers|string $trigger): bool
    {
        if ($trigger instanceof WebhookTriggers) {
            $trigger = $trigger->value;
        }

        return in_array($trigger, $this->triggers ?? [], true);
    }

    public function setMetadataKey(string $key, mixed $value = null): void
    {
        if (null === $value) {
            unset($this->metadata[$key]);
        } else {
            $this->metadata[$key] = $value;
        }
    }

    public function getMetadataKey(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check whether this webhook was dispatched in the last $seconds seconds.
     */
    public function checkRateLimit(int $seconds): bool
    {
        $lastMessageSent = Types::int($this->getMetadataKey(self::LAST_SENT_TIMESTAMP_KEY));
        return $lastMessageSent <= (time() - $seconds);
    }

    public function updateLastSentTimestamp(): void
    {
        $this->setMetadataKey(self::LAST_SENT_TIMESTAMP_KEY, time());
    }

    public function __toString(): string
    {
        return $this->station . ' Web Hook: ' . $this->name;
    }

    public function __clone()
    {
        $this->metadata = null;
    }
}
