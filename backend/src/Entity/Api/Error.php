<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Exception;
use OpenApi\Attributes as OA;
use ReflectionClass;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Throwable;

#[OA\Schema(
    schema: 'Api_Error',
    required: ['*'],
    type: 'object'
)]
final readonly class Error
{
    #[OA\Property(
        description: 'The numeric code of the error.',
        example: 500
    )]
    public int $code;

    #[OA\Property(
        description: 'The programmatic class of error.',
        example: 'NotLoggedInException'
    )]
    public string $type;

    #[OA\Property(
        description: 'The text description of the error.',
        example: 'Error description.',
    )]
    public string $message;

    #[OA\Property(
        description: 'The HTML-formatted text description of the error.',
        example: '<b>Error description.</b><br>Detailed error text.'
    )]
    public ?string $formatted_message;

    #[OA\Property(
        description: 'Stack traces and other supplemental data.',
        items: new OA\Items()
    )]
    public array $extra_data;

    #[OA\Property(
        description: 'Used for API calls that expect an \Entity\Api\Status type response.',
        example: false
    )]
    public bool $success;

    public function __construct(
        int $code = 500,
        string $message = 'General Error',
        ?string $formattedMessage = null,
        array $extraData = [],
        string $type = 'Error'
    ) {
        $this->code = $code;
        $this->message = $message;
        $this->formatted_message = ($formattedMessage ?? $message);
        $this->extra_data = $extraData;
        $this->type = $type;
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

        $className = new ReflectionClass($e)->getShortName();
        if ($e instanceof Exception) {
            $messageFormatted = $e->getFormattedMessage();
            $extraData = $e->getExtraData();
        } else {
            $messageFormatted = $e->getMessage();
            $extraData = [];
        }

        $extraData['class'] = $className;
        $extraData['file'] = $e->getFile();
        $extraData['line'] = $e->getLine();

        if ($includeTrace) {
            if (!($e instanceof FlattenException)) {
                $e = FlattenException::createFromThrowable($e);
            }

            $extraData['trace'] = $e->getTrace();
        }

        return new self($code, $e->getMessage(), $messageFormatted, $extraData, $className);
    }
}
