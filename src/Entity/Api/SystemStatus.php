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
        $this->ram = $this->System_Memory();
        $this->loads = $this->System_Loads();
    }

    protected function OSNotWindows() : bool
    {
        // https://www.php.net/manual/en/function.php-uname.php
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
        {
            return false;
        }
        return true;
    }
    protected function System_Memory() : array
    {
        // if this works out we m
        $free_memory = 0;
        $used_memory = 0;
        if ($this->OSNotWindows() == true)
        {
            // https://stackoverflow.com/questions/4705759/how-to-get-cpu-usage-and-ram-usage-without-exec
            $free_memory = explode("\n", trim(shell_exec('free')));
            $total_memory = preg_split("/[\s]+/", $exec_free[1]);
            $used_memory = $total_memory - $free_memory;
            $free_memory = number_format(round($free_memory/1024, 2), 2);
            $used_memory = number_format(round($used_memory/1024, 2), 2);
        }
        return array(
            "free"=>$free_memory,
            "used"=>$used_memory
        );
    }
    protected function System_Loads() : array
    {
        $fetch_loads = array(0,0,0);
        if ($this->OSNotWindows() == true)
        {
            $fetch_loads = sys_getloadavg();
        }
        return array(
            "1m"=>$fetch_loads[0],
            "5m"=>$fetch_loads[1],
            "15m"=>$fetch_loads[2]
        );
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
     */
    public array $ram = array(
        "free"=>0,
        "used"=>0
    );

    /**
     * The current load useage of the server
     * over 1m, 5m, 15m
     * [Note: sys_getloadavg is not supported for windows]
     */
    public array $loads = array(
        "1m"=>0,
        "5m"=>0,
        "15m"=>0
    );



}
