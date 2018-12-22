<?php
namespace App\Entity\Api;

use App\Entity;
use OpenApi\Annotations\OpenApi as OA;

/**
 * @OA\Schema(type="object")
 */
class Status
{
    public function __construct($success = true, $message = 'Changes saved successfully.')
    {
        $this->success = (bool)$success;
        $this->message = (string)$message;
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
}
