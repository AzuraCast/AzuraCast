<?php
namespace Entity;

/**
 * @Entity
 * @Table(name="station_webhooks")
 */
class StationWebhook
{
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
     * @Column(name="name", type="string", length=100)
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

    public function __construct(Station $station)
    {
        $this->station = $station;
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
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
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