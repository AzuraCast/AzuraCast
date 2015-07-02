<?php
namespace Modules\Frontend\Controllers;

use \PVL\Debug;
use \PVL\Utilities;

class UtilController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function testAction()
    {
        $this->doNotRender();

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        Debug::setEchoMode();

        // -------- START HERE -------- //

        $em = $this->getDI()->get('em');
        $em->createQuery('DELETE FROM Entity\PodcastSource ps')->execute();

        // Pull podcast news.
        $all_podcasts = \Entity\Podcast::getRepository()->findBy(array('is_approved' => 1));

        foreach($all_podcasts as $record)
        {
            $social_types = \Entity\PodcastSource::getSourceSelect();

            Debug::log($record->name);

            foreach($social_types as $social_key => $social_name)
            {
                $social_value = $record->$social_key;

                if (!empty($social_value))
                {
                    Debug::log('Entry '.$social_name.' Found');

                    $source_record = new \Entity\PodcastSource;
                    $source_record->podcast = $record;
                    $source_record->type = $social_key;
                    $source_record->url = $record->$social_key;
                    $em->persist($source_record);
                }
            }

            Debug::divider();

            $em->flush();
        }

        Debug::log('Done importing new sources.');

        \PVL\PodcastManager::run();

        // -------- END HERE -------- //

        Debug::log('Done!');
    }
}