<?php
namespace Modules\Api\Controllers;

use \Entity\Convention;
use \Entity\ConventionArchive;

class ConventionController extends BaseController
{
    public function listAction()
    {
        $all_conventions = $this->em->createQuery('SELECT c, ca FROM Entity\Convention c LEFT JOIN c.archives ca ORDER BY c.start_date DESC')
            ->getArrayResult();

        $export_data = array();

        foreach($all_conventions as $row)
        {
            $api_row = Convention::api($row);

            $api_row['archives_count'] = 0;
            if (count($row['archives']) > 0)
            {
                foreach($row['archives'] as $a_row)
                {
                    if (ConventionArchive::typeIsPlayable($a_row['type']))
                        $api_row['archives_count']++;
                }
            }

            $export_data[] = $api_row;
        }

        return $this->returnSuccess($export_data);
    }

    public function indexAction()
    {
        $id = $this->getParam('id');
        $record = Convention::find($id);

        if (!($record instanceof Convention))
            return $this->returnError('Convention not found.');

        $export_data = Convention::api($record);

        if (count($record->archives) > 0)
        {
            $export_data['archives'] = array(
                'videos' => array(),
                'sources' => array(),
            );

            foreach($record->archives as $row)
            {
                if ($row->isPlayable())
                    $export_data['archives']['videos'][] = $row->toArray();
                else
                    $export_data['archives']['sources'][] = $row->toArray();
            }
        }

        return $this->returnSuccess($export_data);
    }
}