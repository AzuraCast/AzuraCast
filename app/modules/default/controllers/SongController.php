<?php
use \Entity\Song;
use \Entity\SongHistory;
use \Entity\SongVote;

class SongController extends \DF\Controller\Action
{
	public function indexAction()
	{

	}

	/**
	 * Voting Functions
	 */

	public function likeAction()
	{
		$this->_vote(1);
	}
	public function dislikeAction()
	{
		$this->_vote(0-1);
	}
	public function clearvoteAction()
	{
		$this->_vote(0);
	}

	protected function _vote($value)
	{
		$this->doNotRender();

		$sh_id = (int)$this->_getParam('sh_id');
		$sh = SongHistory::find($sh_id);

		if ($sh instanceof SongHistory)
		{
			if ($value == 0)
				$vote_result = $sh->clearVote();
			else
				$vote_result = $sh->vote($value);

			if ($vote_result)
				return $this->_returnMessage('success', 'OK');
			else
				return $this->_returnMessage('error', 'Vote could not be applied.');
		}
		else
		{
			return $this->_returnMessage('error', 'Song history record not found.');
		}
	}

	protected function _returnMessage($status, $message = 'OK')
	{
		header("Content-type: application/json");
		$return_message = json_encode(array(
			'status'	=> $status,
			'message'	=> $message,
		));

		echo $return_message;

		return true;
	}

}