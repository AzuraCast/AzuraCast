<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\Station;
use App\Radio\Configuration;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class StationPortCheckerValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Configuration $configuration
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StationPortChecker) {
            throw new UnexpectedTypeException($constraint, StationPortChecker::class);
        }
        if (!$value instanceof Station) {
            throw new UnexpectedTypeException($value, Station::class);
        }

        $frontendConfig = $value->frontend_config;
        $backendConfig = $value->backend_config;

        $portsToCheck = [
            'frontend_config_port' => $frontendConfig->port,
            'backend_config_dj_port' => $backendConfig->dj_port,
            'backend_config_telnet_port' => $backendConfig->telnet_port,
        ];

        $usedPorts = $this->configuration->getUsedPorts($value);

        $message = sprintf(
            __('The port %s is in use by another station (%s).'),
            '{{ port }}',
            '{{ station }}'
        );

        foreach ($portsToCheck as $portPath => $port) {
            if (null === $port) {
                continue;
            }

            $port = (int)$port;

            if (isset($usedPorts[$port])) {
                $this->context->buildViolation($message)
                    ->setParameter('{{ port }}', (string)$port)
                    ->setParameter('{{ station }}', $usedPorts[$port]['name'])
                    ->addViolation();
            }

            if ($portPath === 'backend_config_dj_port' && isset($usedPorts[$port + 1])) {
                $this->context->buildViolation($message)
                    ->setParameter('{{ port }}', sprintf('%s (%s + 1)', $port + 1, $port))
                    ->setParameter('{{ station }}', $usedPorts[$port + 1]['name'])
                    ->addViolation();
            }
        }
    }
}
