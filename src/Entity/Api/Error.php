<?php
namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_Error")
 */
class Error
{
    public function __construct($code = 500, $message = 'General Error', $stack_trace = [])
    {
        $this->code = (int)$code;
        $this->message = (string)$message;
        $this->stack_trace = (array)$stack_trace;
        $this->success = false;
    }

    /**
     * The numeric code of the error.
     *
     * @OA\Property(example=500)
     * @var int
     */
    public $code;

    /**
     * The text description of the error.
     *
     * @OA\Property(example="Error description.")
     * @var string
     */
    public $message;

    /**
     * A stack trace outlining the error, if permissions allow this to be shown.
     *
     * @OA\Property(@OA\Items)
     * @var array
     */
    public $stack_trace;

    /**
     * Used for API calls that expect an \Entity\Api\Status type response.
     *
     * @OA\Property(example=false)
     * @var bool
     */
    public $success;
}
