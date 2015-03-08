<?php
namespace Modules\Frontend\Controllers;

use \Entity\Song;

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

        \PVL\Debug::setEchoMode();

        $em = $this->di->get('em');
        $tables = array('SongExternalBronyTunes', 'SongExternalEqBeats', 'SongExternalPonyFm');

        foreach($tables as $table)
        {
            $i = 0;
            $hashes_seen = array();

            $q = $em->createQuery('select se FROM Entity\\'.$table.' se ORDER BY se.id ASC');

            $iterableResult = $q->iterate();
            foreach ($iterableResult as $row)
            {
                $ext_row = $row[0];
                $hash = $ext_row->hash;

                if (isset($hashes_seen[$hash]))
                {
                    $em->remove($ext_row);
                }
                else
                {
                    $hashes_seen[$hash] = $hash;
                }

                ++$i;

                if (($i % 100) === 0)
                {
                    $em->flush(); // Executes all updates.
                    $em->clear(); // Detaches all objects from Doctrine!
                }
            }
        }

        $em->flush(); // Executes all updates.
        $em->clear(); // Detaches all objects from Doctrine!

        echo 'Duplicates removed.';
        exit;

        // Sync the BronyTunes library.
        \PVL\Service\BronyTunes::load(true);

        // Sync the Pony.fm library.
        \PVL\Service\PonyFm::load(true);

        // Sync the EqBeats library.
        \PVL\Service\EqBeats::load(true);

        \PVL\Debug::log('Donezo!');
    }
}