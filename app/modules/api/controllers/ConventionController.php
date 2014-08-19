<?php
use \Entity\Convention;
use \Entity\ConventionArchive;

class Api_ConventionController extends \PVL\Controller\Action\Api
{
    public function listAction()
    {
        $all_conventions = Convention::fetchArray();
        $export_data = array();

        foreach($all_conventions as $row)
            $export_data[$row['id']] = Convention::api($row);

        $this->returnSuccess($export_data);
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
                    $export_data['archives']['videos'][$row->id] = $row->toArray();
                else
                    $export_data['archives']['sources'][$row->id] = $row->toArray();
            }
        }

        return $this->returnSuccess($export_data);
    }
}