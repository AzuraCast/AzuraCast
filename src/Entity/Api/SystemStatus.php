<?php

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_SystemStatus")
 */
class SystemStatus
{
    public function __construct()
    {
        $this->timestamp = time();
        $this->ram = $this->systemMemory();
        $this->loads = $this->systemLoads();
    }

    /**
     * Checks if the value PHP_OS
     * signals that we are not running in windows
     */
    protected function osNotWindows(): bool
    {
        // ref: https://www.php.net/manual/en/function.php-uname.php
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
        {
            return false;
        }
        return true;
    }

    /**
     * uses unix shell free command to get the current
     * memory usage
     * @return mixed[] [free string, used string]
     */
    protected function systemMemory(): array
    {
        $freeMemory = "0";
        $usedMemory = "0";
        if ($this->osNotWindows() == true)
        {
            // ref: https://stackoverflow.com/questions/4705759/how-to-get-cpu-usage-and-ram-usage-without-exec
            $memoryExec = explode("\n", trim(shell_exec('free')));
            $memoryProcessed = preg_split("/[\s]+/", $memoryExec[1]);
            /*
                $memory_processed
                [0]=>row_title [1]=>mem_total [2]=>mem_used
                [3]=>mem_free [4]=>mem_shared [5]=>mem_buffers [6]=>mem_cached
            */
            $freeMemory = number_format(round($memoryProcessed[3] / 1024, 2), 2);
            $usedMemory = number_format(round($memoryProcessed[2] / 1024, 2), 2);
        }
        return [
            "free" => $freeMemory,
            "used" => $usedMemory
        ];
    }

    /**
     * uses php sys_getloadavg to get load avg's
     * Not supported by windows
     * @return mixed[] [1m mixed, 5m mixed, 15m mixed]
     */
    protected function systemLoads(): array
    {
        $fetchLoads = [0,0,0];
        if ($this->osNotWindows() == true)
        {
            $fetchLoads = sys_getloadavg();
        }
        return [
            "1m" => $fetchLoads[0],
            "5m" => $fetchLoads[1],
            "15m" => $fetchLoads[2]
        ];
    }
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
    public array $ram = [
        "free" => "0",
        "used" => "0"
    ];

    /**
     * The current load useage of the server
     * over 1m, 5m, 15m
     * [Note: sys_getloadavg is not supported for windows]
     * @var array
     */
    public array $loads = [
        "1m" => 0,
        "5m" => 0,
        "15m" => 0
    ];
}
