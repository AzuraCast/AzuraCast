<?php
use \Entity\Song;

class Api_SongController extends \PVL\Controller\Action\Api
{
	public function indexAction()
    {
    	$id = $this->_getParam('id');

    	$record = Song::find($id);

    	if (!($record instanceof Song))
    		return $this->returnError('Song not found.');

    	$return = $record->toArray();
    	return $this->returnSuccess($return);
    }
}