<?php
namespace App\Exception;

use Azura\Exception;
use Psr\Log\LogLevel;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

class ValidationException extends Exception
{
    /** @var ConstraintViolationListInterface */
    protected ConstraintViolationListInterface $detailedErrors;

    public function __construct(
        string $message = 'Validation error.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::INFO
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getDetailedErrors(): ConstraintViolationListInterface
    {
        return $this->detailedErrors;
    }

    /**
     * @param ConstraintViolationListInterface $detailedErrors
     */
    public function setDetailedErrors(ConstraintViolationListInterface $detailedErrors): void
    {
        $this->detailedErrors = $detailedErrors;
    }
}
