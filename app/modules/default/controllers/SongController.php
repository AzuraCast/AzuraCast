<?php
use \Entity\Song;
use \Entity\SongHistory;
use \Entity\SongVote;

class SongController extends \DF\Controller\Action
{
    public function indexAction()
    {
        $id = (int)$this->getParam('id');
        $record = Song::find($id);

        if (!($record instanceof Song))
            throw new \DF\Exception\DisplayOnly('Song not found!');

        $song_info = array();
        $song_info['record'] = $record;

        // Get external provider information.
        $song_info['external'] = $record->getExternal();

        // Temporary replacement for locally cached song art.
        if ($song_info['external']['bronytunes']['image_url'])
            $song_info['image_url'] = $song_info['external']['bronytunes']['image_url'];
        elseif ($song_info['external']['eqbeats']['image_url'])
            $song_info['image_url'] = $song_info['external']['eqbeats']['image_url'];
        elseif ($song_info['external']['ponyfm']['image_url'])
            $song_info['image_url'] = $song_info['external']['ponyfm']['image_url'];
        else
            $song_info['image_url'] = \DF\Url::content('images/song_generic.png');

        // Get most recent playback information.
        

        // Get requestable locations.

        $this->view->song = $song_info;
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
            'status'    => $status,
            'message'   => $message,
        ));

        echo $return_message;

        return true;
    }

}