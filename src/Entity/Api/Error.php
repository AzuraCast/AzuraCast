<?php

namespace App\Entity\Api;

use App\Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_Error")
 */
class Error
{
    /**
     * The numeric code of the error.
     *
     * @OA\Property(example=500)
     * @var int
     */
    public int $code;

    /**
     * The text description of the error.
     *
     * @OA\Property(example="Error description.")
     * @var string
     */
    public string $message;

    /**
     * The HTML-formatted text description of the error.
     *
     * @OA\Property(example="<b>Error description.</b><br>Detailed error text.")
     * @var string
     */
    public ?string $formatted_message;

    /**
     * Stack traces and other supplemental data.
     *
     * @OA\Property(@OA\Items)
     * @var array
     */
    public array $extra_data;

    /**
     * Used for API calls that expect an \Entity\Api\Status type response.
     *
     * @OA\Property(example=false)
     * @var bool
     */
    public bool $success;

    public function __construct(
        $code = 500,
        $message = 'General Error',
        $formatted_message = null,
        $extra_data = []
    ) {
        $this->code = (int)$code;
        $this->message = (string)$message;
        $this->formatted_message = ($formatted_message ?? $message);
        $this->extra_data = (array)$extra_data;
        $this->success = false;
    }

    public static function fromException(\Throwable $e, bool $includeTrace = false): self
    {
        $code = (int)$e->getCode();
        if (0 === $code) {
            $code = 500;
        }

        $errorHeader = get_class($e) . ' at ' . $e->getFile() . ' L' . $e->getLine();
        $message = $errorHeader . ': ' . $e->getMessage();

        if ($e instanceof Exception) {
            $messageFormatted = '<b>' . $errorHeader . ':</b> ' . $e->getFormattedMessage();
            $extraData = $e->getExtraData();
        } else {
            $messageFormatted = '<b>' . $errorHeader . ':</b> ' . $e->getMessage();
            $extraData = [];
        }

        if ($includeTrace) {
            $extraData['trace'] = $e->getTrace();
        }

        return new self($code, $message, $messageFormatted, $extraData);
    }
}
