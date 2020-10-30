<?php

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_SystemStatus")
 */
class SystemStatus
{
    /**
     * Whether the service is online or not (should always be true)
     *
     * @OA\Property(example=true)
     * @var bool
     */
    public bool $online = true;

    /**
     * The current UNIX timestamp
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public $timestamp;


    /**
     * The current ram useage of the server
     * [Note: not supported for windows hosts]
     * @var array
     */
    public array $ram = array(
        "free" => "0",
        "used" => "0"
    );

    /**
     * The current load useage of the server
     * over 1m, 5m, 15m
     * [Note: sys_getloadavg is not supported for windows]
     * @var array
     */
    public array $loads = [
        "1m" => 0,
        "5m" => 0,
        "15m" => 0,
    ];

    public function __construct()
    {
        $this->timestamp = time();
        $this->ram = $this->systemMemory();
        $this->loads = $this->systemLoads();
    }

    /**
     * uses unix shell free command to get the current
     * memory usage
     * @return mixed[] [free string, used string]
     */
    protected function systemMemory(): array
    {
        // ref: https://stackoverflow.com/questions/1455379/get-server-ram-with-php/1455610#1455610
        $memoryData = explode("\n", file_get_contents("/proc/meminfo"));
        $memoryInfo = array();
        foreach ($memoryData as $line) {
            list($key, $val) = explode(":", $line);
            $val = strtr($val,"kB",""); // replace kb at the end with nothing
            $memoryInfo[$key] = trim($val);
        }
        /*
            $memoryInfo
            ["MemTotal"]=>
            string(10) "2060700"
            ["MemFree"]=>
            string(9) "277344"
            ["Buffers"]=>
            string(8) "92200"
            ["Cached"]=>
            string(9) "650544"
            ["SwapCached"]=>
            string(8) "73592"
            ["Active"]=>
            string(9) "995988"
        */
        $used = $memoryInfo["MemTotal"] - $memoryInfo["MemFree"];
        return [
            "free" => number_format(round($memoryInfo["MemFree"] / 1024, 2), 2),
            "used" => number_format(round($used / 1024, 2), 2),
        ];
    }

    /**
     * uses php sys_getloadavg to get load avg's
     * Not supported by windows
     * @return mixed[] [1m mixed, 5m mixed, 15m mixed]
     */
    protected function systemLoads(): array
    {
        $fetchLoads = sys_getloadavg();
        return [
            "1m" => $fetchLoads[0],
            "5m" => $fetchLoads[1],
            "15m" => $fetchLoads[2],
        ];
    }
}
