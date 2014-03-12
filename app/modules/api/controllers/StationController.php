<?php
use \Entity\Station;

class Api_StationController extends \PVL\Controller\Action\Api
{
	public function indexAction()
	{
		if ($this->_hasParam('station'))
		{
			$record = Station::findByShortCode($this->_getParam('station'));
		}
		elseif ($this->_hasParam('id'))
		{
			$id = (int)$this->_getParam('id');
			$record = Station::find($id);
		}

		if (!($record instanceof Station) || $record->deleted_at)
			return $this->returnError('Station not found.');

		return $this->returnSuccess($this->_processStation($record));
	}

	public function listAction()
	{
		$category = $this->_getParam('category', 'all');

		if ($category == 'all')
		{
			$stations_raw = Station::fetchArray();
		}
		else
		{
			$cats = Station::getStationsInCategories();

			if (!isset($cats[$category]))
				return $this->returnError('Category not found.');

			$stations_raw = $cats[$category]['stations'];
		}

		$stations = array();
		foreach($stations_raw as $row)
			$stations[] = $this->_processStation($row);

		return $this->returnSuccess($stations);
	}

	protected function _processStation($row)
	{
		if ($row instanceof Station)
			$row = $row->toArray();

		return array(
			'id'		=> (int)$row['id'],
			'name'		=> $row['name'],
			'shortcode'	=> Station::getStationShortName($row['name']),
			'genre'		=> $row['genre'],
			'category'	=> $row['category'],
			'type'		=> $row['type'],
			'image_url'	=> \DF\Url::content($row['image_url']),
			'web_url'	=> $row['web_url'],
			'stream_url' => $row['stream_url'],
			'twitter_url' => $row['twitter_url'],
			'irc'		=> $row['irc'],
		);

	}
}