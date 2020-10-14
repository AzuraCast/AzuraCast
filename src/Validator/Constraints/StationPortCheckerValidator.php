<?php

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

    public function validate($station, Constraint $constraint): void
    {
        if (!$constraint instanceof StationPortChecker) {
            throw new UnexpectedTypeException($constraint, StationPortChecker::class);
        }
        if (!$station instanceof Entity\Station) {
            throw new UnexpectedTypeException($station, Entity\Station::class);
        }

        $frontend_config = $station->getFrontendConfig();
        $backend_config = $station->getBackendConfig();

        $ports_to_check = [
            'frontend_config_port' => $frontend_config->getPort(),
            'backend_config_dj_port' => $backend_config->getDjPort(),
            'backend_config_telnet_port' => $backend_config->getTelnetPort(),
        ];

        $used_ports = $this->configuration->getUsedPorts($station);

        foreach ($ports_to_check as $port_path => $value) {
            if (null === $value) {
                continue;
            }

            $port = (int)$value;
            if (isset($used_ports[$port])) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ port }}', (string)$port)
                    ->addViolation();
            }

            if ($port_path === 'backend_config_dj_port' && isset($used_ports[$port + 1])) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ port }}', sprintf('%s (%s + 1)', $port + 1, $port))
                    ->addViolation();
            }
        }
    }
}
