<?php
use \Entity\Song;
use \Entity\SongHistory;
use \Entity\SongVote;

class Api_SongController extends \PVL\Controller\Action\Api
{
	public function indexAction()
    {
    	$id = $this->_getParam('id');

    	$record = Song::find($id);

    	if (!($record instanceof Song))
    		return $this->returnError('Song not found.');

    	$return = $record->toArray();

    	// Handle display of external data.
    	foreach($return as $r_key => $r_val)
    	{
    		if (substr($r_key, 0, 8) == 'external')
    			unset($return[$r_key]);
    	}
    	$return['external'] = $record->getExternal();

    	return $this->returnSuccess($return);
    }

    /**
	 * Voting Functions
	 */

	public function likeAction()
	{
		return $this->_vote(1);
	}
	public function dislikeAction()
	{
		return $this->_vote(0-1);
	}
	public function clearvoteAction()
	{
		return $this->_vote(0);
	}

	protected function _vote($value)
	{
		if (!$this->_request->isPost())
			return $this->returnError('Votes must be submitted via HTTP POST.');

		$sh_id = (int)$this->_getParam('sh_id');
		$sh = SongHistory::find($sh_id);

		if ($sh instanceof SongHistory)
		{
			if ($value == 0)
				$vote_result = $sh->clearVote();
			else
				$vote_result = $sh->vote($value);

			if ($vote_result)
				return $this->returnSuccess('OK');
			else
				return $this->returnError('Vote could not be applied.');
		}
		else
		{
			return $this->returnError('Song history record not found.');
		}
	}
}