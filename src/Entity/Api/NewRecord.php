<?php
namespace App\Entity\Api;

class NewRecord extends Status
{
    /**
     * @OA\Property(
     *     @OA\Items(
     *         type="string",
     *         example="http://localhost/api/record/1"
     *     )
     * )
     * @var array
     */
    public $links = [];
}
