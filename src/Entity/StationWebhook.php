<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="station_webhooks", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci"})
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object")
 */
class StationWebhook
{
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @OA\Property(example=1)
     *
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="webhooks")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="Twitter Post")
     *
     * @var string|null The nickname of the webhook connector.
     */
    protected $name;

    /**
     * @ORM\Column(name="type", type="string", length=100)
     *
     * @OA\Property(example="twitter")
     *
     * @Assert\NotBlank()
     *
     * @var string The type of webhook connector to use.
     */
    protected $type;

    /**
     * @ORM\Column(name="is_enabled", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $is_enabled = true;

    /**
     * @ORM\Column(name="triggers", type="json", nullable=true)
     *
     * @OA\Property(@OA\Items())
     *
     * @var array List of events that should trigger the webhook notification.
     */
    protected $triggers;

    /**
     * @ORM\Column(name="config", type="json", nullable=true)
     *
     * @OA\Property(@OA\Items())
     *
     * @var array Detailed webhook configuration (if applicable)
     */
    protected $config;

    /**
     * @ORM\Column(name="metadata", type="json", nullable=true)
     *
     * @OA\Property(@OA\Items())
     *
     * @var array Internal details used by the webhook to preserve state.
     */
    protected $metadata;

    public function __construct(Station $station, $type)
    {
        $this->station = $station;
        $this->type = $type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @AuditLog\AuditIdentifier
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $this->truncateString($name, 100);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function toggleEnabled(): bool
    {
        $this->is_enabled = !$this->is_enabled;
        return $this->is_enabled;
    }

    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
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
     * @param mixed $value
     */
    public function setMetadataKey(string $key, $value = null): void
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
     * @return mixed|null
     */
    public function getMetadataKey(string $key, $default = null)
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
}
