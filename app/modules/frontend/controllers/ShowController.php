<?php
namespace Modules\Frontend\Controllers;

use \Entity\Podcast;
use \Entity\PodcastEpisode;

class ShowController extends BaseController
{
    public function indexAction()
    {
        $podcasts_raw = $this->em->createQuery('SELECT p, s, pe FROM Entity\Podcast p LEFT JOIN p.stations s LEFT JOIN p.episodes pe WHERE p.is_approved = 1 ORDER BY p.name ASC')
            ->getArrayResult();

        $podcasts = array();
        foreach($podcasts_raw as $pc)
        {
            $pc['episodes'] = array_slice($pc['episodes'], 0, 3);

            if (isset($pc['episodes'][0]))
                $pc['latest_episode'] = $pc['episodes'][0]['timestamp'];

            $podcasts[$pc['id']] = $pc;
        }

        if ($this->getParam('sort') == 'latest')
            $podcasts = \DF\Utilities::irsort($podcasts, 'latest_episode');

        $this->view->sort = $this->getParam('sort', 'alpha');
        $this->view->podcasts = $podcasts;
    }

    public function viewAction()
    {
        $id = (int)$this->getParam('id');
        $podcast = Podcast::find($id);

        if (!($podcast instanceof Podcast))
            throw new \DF\Exception\DisplayOnly('Podcast not found!');

        $this->view->podcast = $podcast;
        $this->view->social_types = Podcast::getSocialTypes();

        // Stringify list of stations that play this podcast.
        $airs_on = '';
        if (count($podcast->stations) > 0)
        {
            $airs_on_array = array();
            foreach($podcast->stations as $station)
                $airs_on_array[] = '<a href="'.$station->web_url.'" target="_blank">'.$station->name.'</a>';

            $airs_on = 'Airs on '.\DF\Utilities::joinCompound($airs_on_array);
        }

        $this->view->podcast_airs_on = $airs_on;

        // Paginate episodes.
        $query = $this->em->createQuery('SELECT pe FROM Entity\PodcastEpisode pe WHERE pe.podcast_id = :podcast AND pe.is_active = 1 ORDER BY pe.timestamp DESC')
            ->setParameter('podcast', $id);
        $this->view->pager = new \DF\Paginator\Doctrine($query, $this->getParam('page', 1), 25);
    }

    public function episodeAction()
    {
        $podcast_id = (int)$this->getParam('id');
        $episode_id = (int)$this->getParam('episode');

        $record = PodcastEpisode::getRepository()->findOneBy(array('id' => $episode_id, 'podcast_id' => $podcast_id));

        if (!($record instanceof PodcastEpisode))
            throw new \DF\Exception\DisplayOnly('Podcast episode not found!');

        $record->play_count = $record->play_count + 1;
        $record->save();

        // Insert into Influx
        if (isset($_SERVER['CF-Connecting-IP']))
            $remote_ip = $_SERVER['CF-Connecting-IP'];
        else
            $remote_ip = $_SERVER['REMOTE_ADDR'];

        $influx = $this->di->get('influx');
        $influx->setDatabase('pvlive_analytics');

        $influx->insert('podcast.'.$podcast_id.'.'.$episode_id, [
            'value'         => 1,
            'ip'            => $remote_ip,
            'client'        => $this->getParam('origin', 'organic'),
            'useragent'     => $_SERVER['HTTP_USER_AGENT'],
            'referrer'      => $_SERVER['HTTP_REFERER'],
        ]);

        $redirect_url = $record->web_url;
        return $this->redirect($redirect_url);
    }

    public function feedAction()
    {
        $this->doNotRender();

        if ($this->hasParam('id'))
        {
            $id = (int)$this->getParam('id');
            $record = Podcast::find($id);

            if (!($record instanceof Podcast))
                throw new \DF\Exception\DisplayOnly('Show record not found!');

            $feed_title = $record->name;
            $feed_desc = ($record->description) ? $record->description : 'A Ponyville Live! Show.';

            $cache_name = 'podcasts_' . $id . '_feed';
            $q = $this->em->createQuery('SELECT pe, p FROM Entity\PodcastEpisode pe JOIN pe.podcast p WHERE p.is_approved = 1 AND p.id = :id ORDER BY pe.timestamp DESC')->setParameter('id', $id);
        }
        else
        {
            $feed_title = 'Ponyville Live! Shows';
            $feed_desc = 'The partner shows of the Ponyville Live! network, including commentary, interviews, episode reviews, convention coverage, and more.';

            $cache_name = 'podcasts_all_feed';
            $q = $this->em->createQuery('SELECT pe, p FROM Entity\PodcastEpisode pe JOIN pe.podcast p WHERE p.is_approved = 1 AND pe.timestamp >= :threshold ORDER BY pe.timestamp DESC')
                ->setParameter('threshold', strtotime('-3 months'));
        }

        $rss = \DF\Cache::get($cache_name);

        if (!$rss)
        {
            $records = $q->getArrayResult();

            // Initial RSS feed setup.
            $feed = new \Zend\Feed\Writer\Feed();
            $feed->setTitle($feed_title);
            $feed->setLink('http://ponyvillelive.com/');

            $feed->setDescription($feed_desc);

            $feed->addAuthor(array(
                'name' => 'Ponyville Live!',
                'email' => 'pr@ponyvillelive.com',
                'uri' => 'http://ponyvillelive.com',
            ));
            $feed->setDateModified(time());

            foreach ((array)$records as $episode)
            {
                try
                {
                    $podcast = $episode['podcast'];
                    $title = $episode['title'];

                    // Check for podcast name preceding episode name.
                    if (substr($title, 0, strlen($podcast['name'])) == $podcast['name'])
                        $title = substr($title, strlen($podcast['name']));

                    $title = trim($title, " :-\t\n\r\0\x0B");
                    $title = $podcast['name'] . ' - ' . $title;

                    // Create record.
                    $entry = $feed->createEntry();
                    $entry->setTitle($title);
                    $entry->setLink($episode['web_url']);
                    $entry->addAuthor(array(
                        'name' => $podcast['name'],
                        'uri' => $podcast['web_url'],
                    ));
                    $entry->setDateModified($episode['timestamp']);
                    $entry->setDateCreated($episode['timestamp']);

                    if ($podcast['description'])
                        $entry->setDescription($podcast['description']);

                    if ($episode['body'])
                        $entry->setContent($episode['body']);

                    $feed->addEntry($entry);
                }
                catch (\Exception $e){ }
            }

            // Export feed.
            $rss = $feed->export('rss');

            \DF\Cache::set($rss, $cache_name, array(), 60 * 15);
        }

        header("Content-Type: application/rss+xml");
        echo $rss;
    }
}