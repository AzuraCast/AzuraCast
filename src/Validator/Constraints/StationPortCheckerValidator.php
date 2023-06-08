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

        $frontendConfig = $value->getFrontendConfig();
        $backendConfig = $value->getBackendConfig();

        $portsToCheck = [
            'frontend_config_port' => $frontendConfig->getPort(),
            'backend_config_dj_port' => $backendConfig->getDjPort(),
            'backend_config_telnet_port' => $backendConfig->getTelnetPort(),
        ];

        $usedPorts = $this->configuration->getUsedPorts($value);

        $message = sprintf(
            __('The port %s is in use by another station.'),
            '{{ port }}'
        );

        foreach ($portsToCheck as $portPath => $port) {
            if (null === $port) {
                continue;
            }

            $port = (int)$port;
            if (isset($usedPorts[$port])) {
                $this->context->buildViolation($message)
                    ->setParameter('{{ port }}', (string)$port)
                    ->addViolation();
            }

            if ($portPath === 'backend_config_dj_port' && isset($usedPorts[$port + 1])) {
                $this->context->buildViolation($message)
                    ->setParameter('{{ port }}', sprintf('%s (%s + 1)', $port + 1, $port))
                    ->addViolation();
            }
        }
    }
}
