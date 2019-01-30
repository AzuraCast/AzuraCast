<?php
namespace App\Exception;

use Monolog\Logger;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class Validation extends \Azura\Exception
{
    protected $logger_level = Logger::INFO;

    /** @var ConstraintViolationListInterface */
    protected $detailed_errors;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'Submission could not be validated.';
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getDetailedErrors(): ConstraintViolationListInterface
    {
        return $this->detailed_errors;
    }

    /**
     * @param ConstraintViolationListInterface $detailed_errors
     */
    public function setDetailedErrors(ConstraintViolationListInterface $detailed_errors): void
    {
        $this->detailed_errors = $detailed_errors;
    }
}
