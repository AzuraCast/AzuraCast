<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity;
use App\Radio\Configuration;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class StationPortCheckerValidator extends ConstraintValidator
{
    protected Configuration $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof StationPortChecker) {
            throw new UnexpectedTypeException($constraint, StationPortChecker::class);
        }
        if (!$value instanceof Entity\Station) {
            throw new UnexpectedTypeException($value, Entity\Station::class);
        }

        $frontend_config = $value->getFrontendConfig();
        $backend_config = $value->getBackendConfig();

        $ports_to_check = [
            'frontend_config_port' => $frontend_config->getPort(),
            'backend_config_dj_port' => $backend_config->getDjPort(),
            'backend_config_telnet_port' => $backend_config->getTelnetPort(),
        ];

        $used_ports = $this->configuration->getUsedPorts($value);

        $message = __('The port %s is in use by another station.', '{{ port }}');

        foreach ($ports_to_check as $port_path => $port) {
            if (null === $port) {
                continue;
            }

            $port = (int)$port;
            if (isset($used_ports[$port])) {
                $this->context->buildViolation($message)
                    ->setParameter('{{ port }}', (string)$port)
                    ->addViolation();
            }

            if ($port_path === 'backend_config_dj_port' && isset($used_ports[$port + 1])) {
                $this->context->buildViolation($message)
                    ->setParameter('{{ port }}', sprintf('%s (%s + 1)', $port + 1, $port))
                    ->addViolation();
            }
        }
    }
}
