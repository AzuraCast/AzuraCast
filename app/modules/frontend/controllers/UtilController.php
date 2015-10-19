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

        $this->em->createQuery('DELETE FROM Entity\Fandom f')->execute();

        // Create new fandom.
        $fandom = new \Entity\Fandom;
        $fandom->name = 'My Little Pony';
        $fandom->abbr = 'Pony';
        $fandom->class = 'pony';
        $fandom->save();

        $fandom_id = $fandom->id;

        // Update all records of all types.
        $this->em->createQuery('UPDATE \Entity\Song s SET s.fandom_id = :fandom_id')
            ->setParameter('fandom_id', $fandom_id)
            ->execute();

        $this->em->createQuery('UPDATE \Entity\Station s SET s.fandom_id = :fandom_id')
            ->setParameter('fandom_id', $fandom_id)
            ->execute();

        $this->em->createQuery('UPDATE \Entity\Podcast p SET p.fandom_id = :fandom_id')
            ->setParameter('fandom_id', $fandom_id)
            ->execute();

        $this->em->createQuery('UPDATE \Entity\Convention c SET c.fandom_id = :fandom_id')
            ->setParameter('fandom_id', $fandom_id)
            ->execute();

        $this->em->createQuery('UPDATE \Entity\NetworkNews nn SET nn.fandom_id = :fandom_id')
            ->setParameter('fandom_id', $fandom_id)
            ->execute();

        // Test the "fetch default" function.
        $default_fandom = \Entity\Fandom::fetchDefault();
        Debug::log('Default Fandom: '.$default_fandom->name);

        // -------- END HERE -------- //

        Debug::log('Done!');
    }
}