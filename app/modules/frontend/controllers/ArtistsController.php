<?php
namespace Modules\Frontend\Controllers;

use \Entity\Artist;
use \Entity\ArtistType;
use \DF\Utilities;

class ArtistsController extends BaseController
{
    public function indexAction()
    {
        if (!empty($_GET))
            return $this->convertGetToParam();

        $type_names = ArtistType::getTypeNames();
        $this->view->type_names = $type_names;

        $query = NULL;

        if ($this->hasParam('type'))
        {
            $this->view->type = $type = $this->getParam('type');
            $this->view->type_name = $type_names[$type];

            $query = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at WHERE (a.is_approved = 1 AND at.id = :type) ORDER BY a.name ASC')
                ->setParameter('type', $type);
        }
        else if ($this->hasParam('q'))
        {
            $this->view->q = $q = $this->getParam('q');
            $this->view->type_name = 'Search Results';

            $query = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at WHERE (a.is_approved = 1) AND (a.name LIKE :q) ORDER BY a.name ASC')
                ->setParameter('q', '%'.addcslashes($q, "%_").'%');
        }

        if ($query)
        {
            $this->view->pager = new \DF\Paginator\Doctrine($query, $this->getParam('page', 1));
            $this->render('list');
            return;
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getParam('id');

        $record = Artist::find($id);
        if (!($record instanceof Artist))
            throw new \DF\Exception\DisplayOnly('Artist Not Found');

        $this->view->artist = $record;

        // Generate statistics.
        $cache_key = 'artist_'.$record->id.'_stats';
        $stats = \DF\Cache::get($cache_key);

        if (empty($stats))
        {
            $stats = array(
                'plays_per_day' => array(),
                'song_lists' => array(
                    'most_played' => array(
                        'label' => 'Most Played Songs',
                        'songs' => array(),
                    ),
                    'most_liked' => array(
                        'label' => 'Most Liked Songs',
                        'songs' => array(),
                    ),
                    'most_recent' => array(
                        'label' => 'Most Recently Played',
                        'songs' => array(),
                    ),
                ),
            );

            $active_streams = \Entity\StationStream::getMainRadioStreams();

            $songs = $this->em->createQuery('SELECT s, sh
                FROM Entity\Song s
                LEFT JOIN s.history sh
                WHERE s.artist LIKE :artist_q
                AND sh.stream_id IN (:streams)
                ORDER BY s.title, sh.timestamp DESC')
                ->setParameter('artist_q', '%'.$record->name.'%')
                ->setParameter('streams', $active_streams)
                ->getArrayResult();

            $plays_per_day = array();

            foreach($songs as &$song)
            {
                foreach((array)$song['history'] as $i => $history)
                {
                    // Get day of song play, incremenet counter.
                    $day = strtotime(date('Y-m-d', $history['timestamp']).' 00:00:00') * 1000;
                    $plays_per_day[$day] += 1;

                    // Increment votes.
                    $song['score_likes'] += $history['score_likes'];
                    $song['score_dislikes'] += $history['score_dislikes'];
                }
                unset($song['history']);

                // Increment vote totals.
                $song['score_total'] = $song['score_likes'] - $song['score_dislikes'];
                $song['votes'] = $song['score_likes'] + $song['score_dislikes'];
            }

            // Remove current day, as it will always be lower.
            $current_day = strtotime(date('Y-m-d').' 00:00:00')*1000;
            unset($plays_per_day[$current_day]);

            ksort($plays_per_day);
            foreach($plays_per_day as $plays_day => $plays_total)
                $stats['plays_per_day'][] = array($plays_day, $plays_total);

            $stats['song_lists']['most_played']['songs'] = array_slice(Utilities::irsort($songs, 'play_count'), 0, 10);
            $stats['song_lists']['most_liked']['songs'] = array_slice(Utilities::irsort($songs, 'score_total'), 0, 10);
            $stats['song_lists']['most_recent']['songs'] = array_slice(Utilities::irsort($songs, 'last_played'), 0, 10);

            \DF\Cache::save($stats, $cache_key, array(), 300);
        }

        $this->view->stats = $stats;
    }

    public function submitAction()
    {
        return $this->redirectFromHere(array('action' => 'profile'));
    }

    public function profileAction()
    {
        $this->acl->checkPermission('is logged in');
        $user = $this->auth->getLoggedInUser();

        $form_config = $this->module_config['admin']->forms->artist->toArray();
        unset($form_config['groups']['admin']);

        $form = new \DF\Form($form_config);

        if ($user->artist instanceof Artist)
        {
            $record = $user->artist;
            $form->setDefaults($record->toArray(TRUE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            $files = $form->processFiles('artists');

            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            if (!($record instanceof Artist))
            {
                // Check for existing artist with same name.
                $record = Artist::findAbandonedByName($data['name']);

                if ($record instanceof Artist)
                {
                    $record->user = $user;
                }
                else
                {
                    $record = new Artist;
                    $record->is_approved = false;
                    $record->user = $user;
                }
            }

            $record->fromArray($data);
            $record->save();
            
            $this->alert('<b>Your artist profile has been submitted!</b><br>After review, your profile will be listed on the Ponyville Live network artist directory. Thank you for your submission.', 'green');
            $this->redirectFromHere(array('action' => 'index'));
            return;
        }

        if ($user->is_artist)
            $this->view->setVar('title', 'Update Artist Profile');
        else
            $this->view->setVar('title', 'Submit an Artist Profile');
        
        $this->renderForm($form);
    }
}