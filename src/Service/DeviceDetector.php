<?php

declare(strict_types=1);

namespace App\Service;

class DeviceDetector
{
    /**
     * @var array<string, \DeviceDetector\DeviceDetector>
     */
    protected array $parsedUserAgents = [];

    protected \DeviceDetector\DeviceDetector $dd;

    public function __construct()
    {
        $this->dd = new \DeviceDetector\DeviceDetector();
    }

    public function parse(string $userAgent): \DeviceDetector\DeviceDetector {
        $userAgentHash = md5($userAgent);
        if (isset($this->parsedUserAgents[$userAgentHash])) {
            return $this->parsedUserAgents[$userAgentHash];
        }

        $this->dd->setUserAgent($userAgent);
        $this->dd->parse();

        $this->parsedUserAgents[$userAgentHash] = $this->dd;

        return $this->dd;
    }
}
