<?php
namespace AzuraCast;

class RateLimit
{
    /** @var \Redis */
    protected $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $group_name
     * @param int $timeout
     * @param int $interval
     * @return bool
     * @throws Exception\RateLimitExceeded
     */
    public function checkRateLimit($group_name = 'default', $timeout = 5, $interval = 2)
    {
        if (APP_TESTING_MODE || APP_IS_COMMAND_LINE) {
            return true;
        }

        $ip = $this->_getIp();
        $cache_name = 'rate_limit:'.$group_name.':'.str_replace(':', '.', $ip);

        $result = $this->redis->get($cache_name);

        if ($result !== false) {
            if ($result + 1 > $interval) {
                throw new Exception\RateLimitExceeded();
            } else {
                $this->redis->incr($cache_name);
            }
        } else {
            $this->redis->setex($cache_name, $timeout, 1);
            return true;
        }
    }

    protected function _getIp()
    {
        return $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_FORWARDED']
            ?? $_SERVER['HTTP_FORWARDED_FOR']
            ?? $_SERVER['HTTP_FORWARDED']
            ?? $_SERVER['REMOTE_ADDR']
            ?? null;
    }
}