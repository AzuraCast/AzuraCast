<?php
namespace Modules\Api\Controllers;

class IndexController extends BaseController
{
    /**
     * Public index for API.
     */
    public function indexAction()
    {
        return $this->returnSuccess('The PVL! API is online and functioning. For more information, visit http://docs.ponyvillelive.apiary.io/');
    }

    /**
     * Heartbeat function, returns the current UNIX timestamp.
     */
    public function statusAction()
    {
        return $this->returnSuccess(array(
            'online' => 'true',
            'timestamp' => time(),
        ));
    }

    /**
     * Returns the time in local and GMT.
     */
    public function timeAction()
    {
        $tz_info = \PVL\Timezone::getInfo();

        return $this->returnSuccess(array(
            'timestamp'             => time(),

            'gmt_datetime'          => $tz_info['now_utc']->format('Y-m-d g:i:s'),
            'gmt_date'              => $tz_info['now_utc']->format('F j, Y'),
            'gmt_time'              => $tz_info['now_utc']->format('g:ia'),
            'gmt_timezone'          => 'GMT',
            'gmt_timezone_abbr'     => 'GMT',

            'local_datetime'        => $tz_info['now']->format('Y-m-d g:i:s'),
            'local_date'            => $tz_info['now']->format('F j, Y'),
            'local_time'            => $tz_info['now']->format('g:ia'),
            'local_timezone'        => $tz_info['code'],
            'local_timezone_abbr'   => $tz_info['abbr'],
        ));
    }
}