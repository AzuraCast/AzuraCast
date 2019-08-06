<?php
namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_Status")
 */
class Status
{
    public function __construct(
        $success = true,
        $message = 'Changes saved successfully.',
        $formatted_message = null)
    {
        $this->success = (bool)$success;
        $this->message = (string)$message;

        $this->formatted_message = (string)($formatted_message ?? $message);
    }

    /**
     * @OA\Property(example=true)
     * @var bool
     */
    public $success;

    /**
     * @OA\Property(example="Changes saved successfully.")
     * @var string
     */
    public $message;

    /**
     * @OA\Property(example="<b>Changes saved successfully.</b>")
     * @var string
     */
    public $formatted_message;
}
