<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use PVL\CentovaCast;
use PVL\Utilities;

class CentovaController extends BaseController
{
    public function tracksAction()
    {
        $this->doNotRender();

        $centova_tracks = CentovaCast::fetchTracks($this->station);

        if (empty($centova_tracks))
            throw new \DF\Exception\DisplayOnly('Track list could not be loaded from CentovaCast.');

        \DF\Export::csv($centova_tracks, false);
    }
}