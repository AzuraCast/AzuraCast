<?php

declare(strict_types=1);

namespace App\Utilities;

use Symfony\Component\Console\Output\OutputInterface;

final class Spinner
{
    public const array DEFAULT_FRAMES = [
        '🖥️🎶－🎵－📻',
        '🖥️－🎶－🎵📻',
        '🖥️🎵－🎶－📻',
        '🖥️－🎵－🎶📻',
    ];

    private readonly int $length;

    private int $current = 0;

    public function __construct(
        private readonly ?OutputInterface $output = null,
        private readonly array $frames = self::DEFAULT_FRAMES
    ) {
        $this->length = count($this->frames);
    }

    private function write(string $ln): void
    {
        if (null !== $this->output) {
            $this->output->write($ln);
        } else {
            echo $ln;
        }
    }

    public function tick(string $message): void
    {
        $next = $this->next();

        $this->write(chr(27) . '[0G');
        $this->write(sprintf('%s %s', $this->frames[$next], $message));
    }

    private function next(): int
    {
        $prev = $this->current;
        $this->current = $prev + 1;

        if ($this->current >= $this->length) {
            $this->current = 0;
        }

        return $prev;
    }
}
