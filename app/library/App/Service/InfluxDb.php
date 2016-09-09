<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ParseException;
use GuzzleHttp\Message\ResponseInterface;

class InfluxDb
{
    const STATUS_CODE_OK = 200;
    const STATUS_CODE_UNAUTHORIZED = 401;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_BAD_REQUEST = 400;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param array $options
     */
    public function __construct($options)
    {
        $defaults = array(
            'host'      => 'localhost',
            'port'      => 8086,
            'username'  => 'root',
            'password'  => 'root',
            'protocol'  => 'http',
        );

        $this->options = array_merge($defaults, (array)$options);
        $this->client = new Client();
    }

    /**
     * @param $db_name
     */
    public function setDatabase($db_name)
    {
        $this->options['database'] = $db_name;
        return $this;
    }

    /**
     * Insert point into series
     * @param string $name
     * @param array $value
     * @param bool|string $timePrecision
     * @return mixed
     */
    public function insert($series_name, array $values, $timePrecision = false)
    {
        $data =[];

        $data['name'] = $series_name;
        $data['columns'] = array_keys($values);
        $data['points'][] = array_values($values);

        return $this->rawSend([$data], $timePrecision);
    }

    /**
     * Make a query into database
     * @param string $query
     * @param bool|string $timePrecision
     * @return array
     */
    public function query($query, $timePrecision = false)
    {
        $return = $this->rawQuery($query, $timePrecision);

        $response = [];
        foreach ($return as $metric) {
            $columns = $metric["columns"];
            $response[$metric["name"]] = [];

            foreach ($metric["points"] as $point) {
                $response[$metric["name"]][] = array_combine($columns, $point);
            }
        }
        return $response;
    }

    /**
     * @param $message
     * @param bool $timePrecision
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    public function rawSend($message, $timePrecision = false)
    {
        $response = $this->client->post(
            $this->_getSeriesEndpoint(),
            $this->_getRequest($message, [], $timePrecision)
        );

        return $this->_parseResponse($response);
    }

    /**
     * @param $query
     * @param bool $timePrecision
     * @return mixed
     */
    public function rawQuery($query, $timePrecision = false)
    {
        $response = $this->client->get(
            $this->_getSeriesEndpoint(),
            $this->_getRequest([], ["q" => $query], $timePrecision)
        );

        return $this->_parseResponse($response);
    }

    /**
     * Get the URL for getting/posting updates to a series.
     *
     * @return string
     */
    protected function _getSeriesEndpoint()
    {
        return sprintf(
            "%s://%s:%d/db/%s/series",
            $this->options['protocol'],
            $this->options['host'],
            $this->options['port'],
            $this->options['database']
        );
    }

    /**
     * @param array $body
     * @param array $query
     * @param bool $timePrecision
     * @return array
     */
    protected function _getRequest(array $body = [], array $query = [], $timePrecision = false)
    {
        $request = [
            "auth" => [$this->options['username'], $this->options['password']],
            "exceptions" => false
        ];

        if (count($body)) {
            $request['body'] = json_encode($body);
        }
        if (count($query)) {
            $request['query'] = $query;
        }
        if ($timePrecision) {
            $request["query"]["time_precision"] = $timePrecision;
        }
        return $request;
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     * @throws \Exception
     */
    protected function _parseResponse(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400 && $statusCode < 500) {
            $message = (string)$response->getBody();
            if (!$message) {
                $message = $response->getReasonPhrase();
            }
            switch ($statusCode) {
                case self::STATUS_CODE_UNAUTHORIZED:
                case self::STATUS_CODE_FORBIDDEN:
                    throw new \Exception($message, $statusCode);
                case self::STATUS_CODE_BAD_REQUEST:
                    if (strpos($message, "Couldn't find series:") !== false) {
                        throw new \Exception($message, $statusCode);
                    }
            }
            throw new \Exception($message, $statusCode);
        } else if ($statusCode == self::STATUS_CODE_OK) {
            try {
                if (!empty((string)$response->getBody()))
                    return $response->json();
                else
                    return array();
            } catch (ParseException $ex) {
                throw new \Exception(
                    sprintf("%s; Response is '%s'", $ex->getMessage(), (string)$response->getBody()),
                    $ex->getCode(), $ex
                );
            }
        } else if ($statusCode > 200 && $statusCode < 300) {
            return true;
        }
        throw new \Exception((string)$response->getBody(), $statusCode);
    }
}