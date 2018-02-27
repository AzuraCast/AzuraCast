<?php
namespace App\Log;

use App\Http\Response;
use Monolog;

class ResponseWriter extends Monolog\Handler\AbstractProcessingHandler
{
    protected $response;

    public function __construct(Response $response, $level = Monolog\Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->response = $response;
    }

    protected function write(array $record)
    {
        $this->response->write($record['formatted']);
    }
}