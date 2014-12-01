<?php
use \Entity\Podcast;
use \Entity\PodcastEpisode;

class ShowController extends \DF\Controller\Action
{
    public function indexAction()
    {
        $podcasts_raw = $this->em->createQuery('SELECT p, s, pe FROM Entity\Podcast p LEFT JOIN p.stations s LEFT JOIN p.episodes pe WHERE p.is_approved = 1 ORDER BY p.name ASC')
            ->getArrayResult();

        $podcasts = array();
        foreach($podcasts_raw as $pc)
        {
            $pc['episodes'] = array_slice($pc['episodes'], 0, 3);
            $podcasts[$pc['id']] = $pc;
        }

        $this->view->podcasts = $podcasts;
    }

    public function viewAction()
    {
        $id = (int)$this->_getParam('id');
        $podcast = Podcast::find($id);

        if (!($podcast instanceof Podcast))
            throw new \DF\Exception\DisplayOnly('Podcast not found!');

        $this->view->podcast = $podcast;
        $this->view->episodes = $podcast->episodes;
    }

    public function feedAction()
    {
        $this->doNotRender();

        switch(strtolower($this->_getParam('type', 'default')))
        {
            case 'single':
                $id = (int)$this->_getParam('id');
                $record = Podcast::find($id);

                if (!($record instanceof Podcast))
                    throw new \DF\Exception\DisplayOnly('Show record not found!');

                $feed_title = $record->name;
                $feed_desc = ($record->description) ? $record->description : 'A Ponyville Live! Show.';

                $q = $this->em->createQuery('SELECT pe, p FROM Entity\PodcastEpisode pe JOIN pe.podcast p WHERE p.is_approved = 1 AND p.id = :id ORDER BY pe.timestamp DESC')->setParameter('id', $id);
            break;

            case 'syndicated':
                // TODO

            case 'default':
            default:
                $feed_title = 'Ponyville Live! Shows';
                $feed_desc = 'The partner shows of the Ponyville Live! network, including commentary, interviews, episode reviews, convention coverage, and more.';

                $q = $this->em->createQuery('SELECT pe, p FROM Entity\PodcastEpisode pe JOIN pe.podcast p WHERE p.is_approved = 1 AND pe.timestamp >= :threshold ORDER BY pe.timestamp DESC')
                    ->setParameter('threshold', strtotime('-3 months'));
            break;
        }

        $records = $q->getArrayResult();

        // Initial RSS feed setup.
        $feed = new \Zend_Feed_Writer_Feed;
        $feed->setTitle($feed_title);
        $feed->setLink('http://ponyvillelive.com/');
        $feed->setFeedLink(\DF\Url::current(), 'rss');
        $feed->setDescription($feed_desc);

        $feed->addAuthor(array(
            'name'  => 'Ponyville Live!',
            'email' => 'pr@ponyvillelive.com',
            'uri'   => 'http://ponyvillelive.com',
        ));
        $feed->setDateModified(time());

        foreach((array)$records as $episode)
        {
            try
            {
                $podcast = $episode['podcast'];
                $title = $episode['title'];

                // Check for podcast name preceding episode name.
                if (substr($title, 0, strlen($podcast['name'])) == $podcast['name'])
                    $title = substr($title, strlen($podcast['name']));

                $title = trim($title, " :-\t\n\r\0\x0B");
                $title = $podcast['name'].' - '.$title;

                // Create record.
                $entry = $feed->createEntry();
                $entry->setTitle($title);
                $entry->setLink($episode['web_url']);
                $entry->addAuthor(array(
                    'name'  => $podcast['name'],
                    'uri'   => $podcast['web_url'],
                ));
                $entry->setDateModified($episode['timestamp']);
                $entry->setDateCreated($episode['timestamp']);

                if ($podcast['description'])
                    $entry->setDescription($podcast['description']);

                if ($episode['body'])
                    $entry->setContent($episode['body']);

                $feed->addEntry($entry);
            }
            catch(\Exception $e) {}
        }

        // Export feed.
        $out = $feed->export('rss');

        header("Content-Type: application/rss+xml");
        echo $out;
    }
}