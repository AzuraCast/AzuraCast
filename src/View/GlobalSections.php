<?php

declare(strict_types=1);

namespace App\View;

use ArrayAccess;
use League\Plates\Template\Template;
use LogicException;

/**
 * A global section container for templates.
 *
 * @implements ArrayAccess<string, string>
 */
final class GlobalSections implements ArrayAccess
{
    private int $sectionMode = Template::SECTION_MODE_REWRITE;
    private array $sections = [];
    private ?string $sectionName = null;

    public function has(string $section): bool
    {
        return !empty($this->sections[$section]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string)$offset);
    }

    public function get(string $section, ?string $default = null): ?string
    {
        return $this->sections[$section] ?? $default;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string)$offset);
    }

    public function unset(string $section): void
    {
        unset($this->sections[$section]);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->unset((string)$offset);
    }

    public function set(
        string $section,
        ?string $value,
        int $mode = Template::SECTION_MODE_REWRITE
    ): void {
        $initialValue = $this->sections[$section] ?? '';

        $this->sections[$section] = match ($mode) {
            Template::SECTION_MODE_PREPEND => $value . $initialValue,
            Template::SECTION_MODE_APPEND => $initialValue . $value,
            default => $value
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string)$offset, (string)$value);
    }

    public function prepend(string $section, ?string $value): void
    {
        $this->set($section, $value, Template::SECTION_MODE_PREPEND);
    }

    public function append(string $section, ?string $value): void
    {
        $this->set($section, $value, Template::SECTION_MODE_APPEND);
    }

    public function start(string $name): void
    {
        if ($this->sectionName) {
            throw new LogicException('You cannot nest sections within other sections.');
        }

        $this->sectionName = $name;

        ob_start();
    }

    public function appendStart(string $name): void
    {
        $this->sectionMode = Template::SECTION_MODE_APPEND;
        $this->start($name);
    }

    public function prependStart(string $name): void
    {
        $this->sectionMode = Template::SECTION_MODE_PREPEND;
        $this->start($name);
    }

    public function end(): void
    {
        if (is_null($this->sectionName)) {
            throw new LogicException(
                'You must start a section before you can stop it.'
            );
        }

        $this->set($this->sectionName, ob_get_clean() ?: null, $this->sectionMode);

        $this->sectionName = null;
        $this->sectionMode = Template::SECTION_MODE_REWRITE;
    }
}
