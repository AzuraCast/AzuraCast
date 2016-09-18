<?php
namespace Modules\Api\Controllers;

use Entity\Settings;

class NowplayingController extends BaseController
{
    public function indexAction()
    {
        $this->setCacheLifetime(15);

        // Pull from cache, or load from flatfile otherwise.
        $cache = $this->di->get('cache');
        
        $np = $cache->get('api_nowplaying_data', function() {
            return Settings::getSetting('nowplaying');
        });

        // Sanity check for now playing data.
        if (empty($np))
            return $this->returnError('Now Playing data has not loaded into the cache. Wait for file reload.');

        if ($this->hasParam('id') || $this->hasParam('station'))
        {
            if ($this->hasParam('id'))
            {
                $id = (int)$this->getParam('id');
                foreach($np as $key => $np_row)
                {
                    if ($np_row['station']['id'] == $id)
                        return $this->returnSuccess($np_row);
                }

                return $this->returnError('Station not found.');
            }
        }
        else
        {
            return $this->returnSuccess($np);
        }
    }
}