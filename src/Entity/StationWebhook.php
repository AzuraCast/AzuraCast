<?php

declare(strict_types=1);

namespace App\Entity;

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
class StationWebhook implements
    Stringable,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const LAST_SENT_TIMESTAMP_KEY = 'last_message_sent';

    #[
        ORM\ManyToOne(inversedBy: 'webhooks'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    protected Station $station;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $station_id;

    #[
        OA\Property(
            description: "The nickname of the webhook connector.",
            example: "Twitter Post"
        ),
        ORM\Column(length: 100, nullable: true)
    ]
    protected ?string $name = null;

    #[
        OA\Property(
            description: "The type of webhook connector to use.",
            example: "twitter"
        ),
        ORM\Column(type: "string", length: 100, enumType: WebhookTypes::class),
        Assert\NotBlank
    ]
    protected WebhookTypes $type;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    protected bool $is_enabled = true;

    #[
        OA\Property(
            description: "List of events that should trigger the webhook notification.",
            type: "array",
            items: new OA\Items()
        ),
        ORM\Column(type: 'json', nullable: true)
    ]
    protected ?array $triggers = null;

    #[
        OA\Property(
            description: "Detailed webhook configuration (if applicable)",
            type: "array",
            items: new OA\Items()
        ),
        ORM\Column(type: 'json', nullable: true)
    ]
    protected ?array $config = null;

    #[
        OA\Property(
            description: "Internal details used by the webhook to preserve state.",
            type: "array",
            items: new OA\Items()
        ),
        ORM\Column(type: 'json', nullable: true),
        Attributes\AuditIgnore
    ]
    protected ?array $metadata = null;

    public function __construct(Station $station, WebhookTypes $type)
    {
        $this->station = $station;
        $this->type = $type;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $this->truncateNullableString($name, 100);
    }

    public function getType(): WebhookTypes
    {
        return $this->type;
    }

    public function getIsEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function setIsEnabled(bool $isEnabled): void
    {
        $this->is_enabled = $isEnabled;
    }

    /**
     * @return string[]
     */
    public function getTriggers(): array
    {
        return (array)$this->triggers;
    }

    public function setTriggers(?array $triggers = null): void
    {
        $this->triggers = $triggers;
    }

    public function hasTriggers(): bool
    {
        return 0 !== count($this->getTriggers());
    }

    public function hasTrigger(WebhookTriggers|string $trigger): bool
    {
        if ($trigger instanceof WebhookTriggers) {
            $trigger = $trigger->value;
        }

        return in_array($trigger, $this->getTriggers(), true);
    }

    /**
     * @return mixed[]
     */
    public function getConfig(): array
    {
        return (array)$this->config;
    }

    public function setConfig(?array $config = null): void
    {
        $this->config = $config;
    }

    /**
     * Set the value of a given metadata key.
     *
     * @param string $key
     * @param mixed|null $value
     */
    public function setMetadataKey(string $key, mixed $value = null): void
    {
        if (null === $value) {
            unset($this->metadata[$key]);
        } else {
            $this->metadata[$key] = $value;
        }
    }

    /**
     * Return the value of a given metadata key, or a default if it is null or doesn't exist.
     *
     * @param string $key
     * @param mixed|null $default
     *
     */
    public function getMetadataKey(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Clear all metadata associated with this webhook.
     */
    public function clearMetadata(): void
    {
        $this->metadata = [];
    }

    /**
     * Check whether this webhook was dispatched in the last $seconds seconds.
     *
     * @param int $seconds
     *
     */
    public function checkRateLimit(int $seconds): bool
    {
        $lastMessageSent = (int)$this->getMetadataKey(self::LAST_SENT_TIMESTAMP_KEY, 0);
        return $lastMessageSent <= (time() - $seconds);
    }

    public function updateLastSentTimestamp(): void
    {
        $this->setMetadataKey(self::LAST_SENT_TIMESTAMP_KEY, time());
    }

    public function __toString(): string
    {
        return $this->getStation() . ' Web Hook: ' . $this->getName();
    }

    public function __clone()
    {
        $this->metadata = null;
    }
}
