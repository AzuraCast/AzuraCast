<?php

namespace Entity\Api;

use Entity;

/**
 * @SWG\Definition(type="object")
 */
class StationRequest
{
    /**
     * Requestable ID unique identifier
     *
     * @SWG\Property(example=1)
     * @var int
     */
    public $request_id;

    /**
     * URL to directly submit request
     *
     * @SWG\Property(example="/api/station/1/request/1")
     * @var int
     */
    public $request_url;

    /**
     * Song
     *
     * @SWG\Property
     * @var Song
     */
    public $song;
}