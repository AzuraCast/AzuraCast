<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use App\Exception\Supervisor\AlreadyRunningException;
use App\Exception\Supervisor\BadNameException;
use App\Exception\Supervisor\NotRunningException;
use Supervisor\Exception\Fault\AlreadyStartedException;
use Supervisor\Exception\Fault\BadNameException as SupervisorBadNameException;
use Supervisor\Exception\Fault\NotRunningException as SupervisorNotRunningException;
use Supervisor\Exception\SupervisorException as SupervisorLibException;

class SupervisorException extends Exception
{
    public static function fromSupervisorLibException(
        SupervisorLibException $e,
        string $processName
    ): self {
        if ($e instanceof SupervisorBadNameException) {
            $headline = sprintf(
                __('%s is not recognized as a service.'),
                $processName
            );
            $body = __('It may not be registered with Supervisor yet. Restarting broadcasting may help.');

            $eNew = new BadNameException(
                $headline . '; ' . $body,
                $e->getCode(),
                $e
            );
        } elseif ($e instanceof AlreadyStartedException) {
            $headline = sprintf(
                __('%s cannot start'),
                $processName
            );
            $body = __('It is already running.');

            $eNew = new AlreadyRunningException(
                $headline . '; ' . $body,
                $e->getCode(),
                $e
            );
        } elseif ($e instanceof SupervisorNotRunningException) {
            $headline = sprintf(
                __('%s cannot stop'),
                $processName
            );
            $body = __('It is not running.');

            $eNew = new NotRunningException(
                $headline . '; ' . $body,
                $e->getCode(),
                $e
            );
        } else {
            $classParts = explode('\\', $e::class);
            $className = array_pop($classParts);

            $headline = sprintf(
                __('%s encountered an error: %s'),
                $processName,
                $className
            );
            $body = __('Check the log for details.');

            $eNew = new self(
                $headline,
                $e->getCode(),
                $e
            );
        }

        $eNew->setFormattedMessage('<b>' . $headline . '</b><br>' . $body);

        return $eNew;
    }
}
