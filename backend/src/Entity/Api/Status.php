<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Api_Status', type: 'object')]
class Status
{
    #[OA\Property(example: true)]
    public bool $success;

    #[OA\Property(example: 'Changes saved successfully.')]
    public string $message;

    #[OA\Property(example: '<b>Changes saved successfully.</b>')]
    public string $formatted_message;

    public function __construct(
        bool $success = true,
        string $message = 'Changes saved successfully.',
        ?string $formattedMessage = null
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->formatted_message = $formattedMessage ?? $message;
    }

    public static function success(): self
    {
        return new self(true, __('Changes saved successfully.'));
    }

    public static function created(): self
    {
        return new self(true, __('Record created successfully.'));
    }

    public static function updated(): self
    {
        return new self(true, __('Record updated successfully.'));
    }

    public static function deleted(): self
    {
        return new self(true, __('Record deleted successfully.'));
    }
}
