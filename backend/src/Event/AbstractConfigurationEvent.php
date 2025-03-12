<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractConfigurationEvent extends Event
{
    protected array $configLines = [];

    /**
     * Append one of more lines to the end of the configuration string.
     *
     * @param array $lines
     */
    public function appendLines(array $lines): void
    {
        $this->configLines = array_merge($this->configLines, [''], $lines);
    }

    public function appendBlock(string $lines): void
    {
        $this->appendLines(explode("\n", $lines));
    }

    /**
     * Prepend one or more lines to the front of the configuration string.
     *
     * @param array $lines
     */
    public function prependLines(array $lines): void
    {
        $this->configLines = array_merge($lines, [''], $this->configLines);
    }

    /**
     * Replace the line at the specified index with the specified string.
     *
     * @param int $index
     * @param string $line
     */
    public function replaceLine(int $index, string $line): void
    {
        $this->configLines[$index] = $line;
    }

    /**
     * Compile the configuration lines together and return the result.
     */
    public function buildConfiguration(): string
    {
        return implode("\n", $this->configLines);
    }
}
