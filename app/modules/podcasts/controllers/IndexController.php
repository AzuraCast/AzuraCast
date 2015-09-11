<?php
namespace Modules\Podcasts\Controllers;

use Entity\Podcast;
use Entity\PodcastEpisode;

class IndexController extends BaseController
{
    public function indexAction()
    {
        $this->view->social_types = Podcast::getSocialTypes();

        // Paginate episodes.
        $query = $this->em->createQuery('SELECT pe FROM Entity\PodcastEpisode pe WHERE pe.podcast_id = :podcast ORDER BY pe.timestamp DESC')
            ->setParameter('podcast', $this->podcast->id);
        $this->view->pager = new \DF\Paginator\Doctrine($query, $this->getParam('page', 1), 25);

        // Generate traffic metrics.
        $influx = $this->di->get('influx');
        $raw_analytics = $influx->setDatabase('pvlive_analytics')
            ->query('SELECT * FROM /^1(d|h).podcast.'.$this->podcast->id.'.*/ WHERE time > now() - 180d', 'm');

        $analytic_totals = array();
        foreach($raw_analytics as $row_schema => $row_entries)
        {
            $schema_parts = explode('.', $row_schema);
            $duration = $schema_parts[0];

            foreach($row_entries as $row_entry)
            {
                $time = $row_entry['time'];

                if (!isset($analytic_totals[$duration][$time]))
                    $analytic_totals[$duration][$time] = 0;

                $analytic_totals[$duration][$time] += $row_entry['count'];
            }
        }

        @ksort($analytic_totals['1d']);
        @ksort($analytic_totals['1h']);

        $chart_data = array(
            '1d' => '[]',
            '1h' => '[]',
        );
        foreach($analytic_totals as $total_duration => $total_entries)
        {
            $new_chart_data = array();
            foreach($total_entries as $entry_timestamp => $entry_value)
                $new_chart_data[] = array($entry_timestamp, $entry_value);

            $chart_data[$total_duration] = json_encode($new_chart_data);
        }

        $this->view->chart_data = $chart_data;
    }

    public function toggleAction()
    {
        $episode_id = (int)$this->getParam('id');

        $ep = PodcastEpisode::find($episode_id);

        if ($ep->podcast === $this->podcast)
        {
            $ep->is_active = !($ep->is_active);
            $ep->save();
        }

        return $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }

    public function addadminAction()
    {
        $this->doNotRender();

        $email = $this->getParam('email');
        $user = \Entity\User::getOrCreate($email);

        $user->podcasts->add($this->podcast);
        $user->save();

        \DF\Messenger::send(array(
            'to' => $user->email,
            'subject' => 'Access Granted to Podcast Center',
            'template' => 'newperms',
            'vars' => array(
                'areas' => array('Podcast Center: '.$this->podcast->name),
            ),
        ));

        return $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'email' => NULL));
    }

    public function removeadminAction()
    {
        $this->doNotRender();

        $id = (int)$this->getParam('id');

        $user = \Entity\User::find($id);
        $user->podcasts->removeElement($this->podcast);
        $user->save();

        return $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }
}