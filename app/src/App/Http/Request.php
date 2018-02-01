<?php
namespace App\Http;

class Request extends \Slim\Http\Request
{
    /**
     * Detect if a parameter exists in the request.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string $key The parameter key.
     * @return bool Whether the key exists.
     */
    public function hasParam($key): bool
    {
        return ($this->getParam($key, null) !== null);
    }
}