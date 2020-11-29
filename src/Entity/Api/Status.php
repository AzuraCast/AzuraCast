<?php

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_Status")
 */
class Status
{
    /**
     * @OA\Property(example=true)
     * @var bool
     */
    public bool $success;

    /**
     * @OA\Property(example="Changes saved successfully.")
     * @var string
     */
    public string $message;

    /**
     * @OA\Property(example="<b>Changes saved successfully.</b>")
     * @var string
     */
    public string $formatted_message;

    public function __construct(
        $success = true,
        $message = 'Changes saved successfully.',
        $formatted_message = null
    ) {
        $this->success = (bool)$success;
        $this->message = (string)$message;

        $this->formatted_message = ($formatted_message ?? $message);
    }
}
