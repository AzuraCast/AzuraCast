<?php
namespace Entity;

/**
 * @Entity
 * @Table(name="station_webhooks", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci"})
 */
class StationWebhook
{
    use Traits\TruncateStrings;

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="webhooks")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @Column(name="name", type="string", length=100, nullable=true)
     * @var string|null The nickname of the webhook connector.
     */
    protected $name;

    /**
     * @Column(name="type", type="string", length=100)
     * @var string The type of webhook connector to use.
     */
    protected $type;

    /**
     * @Column(name="is_enabled", type="boolean")
     * @var bool
     */
    protected $is_enabled;

    /**
     * @Column(name="triggers", type="json_array", nullable=true)
     * @var array List of events that should trigger the webhook notification.
     */
    protected $triggers;

    /**
     * @Column(name="config", type="json_array", nullable=true)
     * @var array Detailed webhook configuration (if applicable)
     */
    protected $config;

    public function __construct(Station $station, $type)
    {
        $this->station = $station;
        $this->type = $type;

        $this->is_enabled = true;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     */
    public function setName(?string $name): void
    {
        $this->name = $this->_truncateString($name, 100);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string The localized name of the connector type.
     */
    public function getTypeName(): string
    {
        $connectors = \AzuraCast\Webhook\Dispatcher::getConnectors();
        return $connectors[$this->type]['name'] ?? '';
    }

    /**
     * @return string The localized description of the connector type.
     */
    public function getTypeDescription(): string
    {
        $connectors = \AzuraCast\Webhook\Dispatcher::getConnectors();
        return $connectors[$this->type]['description'] ?? '';
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * @return bool
     */
    public function toggleEnabled(): bool
    {
        $this->is_enabled = !$this->is_enabled;
        return $this->is_enabled;
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * @return array
     */
    public function getTriggers(): array
    {
        return (array)$this->triggers;
    }

    /**
     * Return an array of localized names of the triggers for this hook.
     *
     * @return array
     */
    public function getTriggerNames(): array
    {
        if (empty($this->triggers)) {
            return [__('Default')];
        }

        $trigger_names = [];
        $triggers = \AzuraCast\Webhook\Dispatcher::getTriggers();

        foreach($this->triggers as $trigger) {
            $trigger_names[] = $triggers[$trigger];
        }

        return $trigger_names;
    }

    /**
     * @param array|null $triggers
     */
    public function setTriggers(array $triggers = null): void
    {
        $this->triggers = $triggers;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return (array)$this->config;
    }

    /**
     * @param array|null $config
     */
    public function setConfig(array $config = null): void
    {
        $this->config = $config;
    }
}