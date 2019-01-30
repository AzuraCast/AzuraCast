<?php
namespace App\Exception;

class MediaProcessing extends \Azura\Exception
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'The media provided could not be processed.';
        }

        parent::__construct($message, $code, $previous);
    }
}
