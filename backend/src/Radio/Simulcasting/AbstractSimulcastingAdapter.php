<?php

declare(strict_types=1);

namespace App\Radio\Simulcasting;

use App\Entity\Simulcasting;

abstract class AbstractSimulcastingAdapter
{
    protected Simulcasting $simulcasting;

    public function __construct(Simulcasting $simulcasting)
    {
        $this->simulcasting = $simulcasting;
    }

    abstract public function getStreamKey(): string;

    abstract public function getStatus(): string;

    abstract public function run(): bool;

    abstract public function stop(): bool;

    abstract public function getAdapterName(): string;

    abstract public function getAdapterDescription(): string;

    abstract public function getConfiguration(): array;

    abstract public function getLiquidsoapOutput(Simulcasting $simulcasting, \App\Entity\Station $station): string;

    public function getSimulcasting(): Simulcasting
    {
        return $this->simulcasting;
    }

    public function validateConfiguration(): array
    {
        $errors = [];
        
        if (empty($this->getStreamKey())) {
            $errors[] = 'Stream key is required';
        }

        return $errors;
    }

    public function isConfigurable(): bool
    {
        return empty($this->validateConfiguration());
    }
}
