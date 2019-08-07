<?php
namespace App\Event\Radio;

use App\Entity\Station;
use Symfony\Contracts\EventDispatcher\Event;

class WriteLiquidsoapConfiguration extends Event
{
    /** @var array */
    protected $config_lines;

    /** @var Station */
    protected $station;

    public function __construct(Station $station)
    {
        $this->station = $station;
        $this->config_lines = [];
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * Append one of more lines to the end of the configuration string.
     *
     * @param array $lines
     */
    public function appendLines(array $lines): void
    {
        $this->config_lines = array_merge($this->config_lines, [''], $lines);
    }

    /**
     * Prepend one or more lines to the front of the configuration string.
     *
     * @param array $lines
     */
    public function prependLines(array $lines): void
    {
        $this->config_lines = array_merge($lines, [''], $this->config_lines);
    }

    /**
     * Compile the configuration lines together and return the result.
     *
     * @return string
     */
    public function buildConfiguration(): string
    {
        return implode("\n", $this->config_lines);
    }
}
