<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Exception;
use OpenApi\Annotations as OA;
use Throwable;

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
        int $code = 500,
        string $message = 'General Error',
        ?string $formatted_message = null,
        array $extra_data = []
    ) {
        $this->code = $code;
        $this->message = $message;
        $this->formatted_message = ($formatted_message ?? $message);
        $this->extra_data = $extra_data;
        $this->success = false;
    }

    public static function notFound(): self
    {
        return new self(404, __('Record not found'));
    }

    public static function fromFileError(int $fileError): self
    {
        $errorMessage = match ($fileError) {
            UPLOAD_ERR_INI_SIZE => __('The uploaded file exceeds the upload_max_filesize directive in php.ini.'),
            UPLOAD_ERR_FORM_SIZE => __('The uploaded file exceeds the MAX_FILE_SIZE directive from the HTML form.'),
            UPLOAD_ERR_PARTIAL => __('The uploaded file was only partially uploaded.'),
            UPLOAD_ERR_NO_FILE => __('No file was uploaded.'),
            UPLOAD_ERR_NO_TMP_DIR => __('No temporary directory is available.'),
            UPLOAD_ERR_CANT_WRITE => __('Could not write to filesystem.'),
            UPLOAD_ERR_EXTENSION => __('Upload halted by a PHP extension.'),
            default => __('Unspecified error.'),
        };

        return new self(500, $errorMessage);
    }

    public static function fromException(Throwable $e, bool $includeTrace = false): self
    {
        $code = $e->getCode();
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
