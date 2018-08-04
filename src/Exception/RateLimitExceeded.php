<?php
namespace App\Exception;

class RateLimitExceeded extends \Exception
{
    protected $message = 'You have exceeded the rate limit for this application.';
}
