<?php
use \Entity\Station;
use \Entity\Convention;
use \Entity\ShortUrl;

class Stations_UrlsController extends \PVL\Controller\Action\Station
{
    public function indexAction()
    {
        $urls = $this->em->createQuery('SELECT su FROM Entity\ShortUrl su WHERE su.station_id = :station_id ORDER BY su.timestamp ASC')
            ->setParameter('station_id', $this->station->id)
            ->execute();

        $this->view->urls = $urls;

        // Auto-Generated Station URLs.
        $station_details = Station::getShortNameLookup();
        $station_categories = Station::getCategories();
        $station_urls = array();

        foreach($station_details as $short_name => $station)
        {
            $station['url'] = ShortUrl::getFullUrl($short_name);
            $station['icon'] = $station_categories[$station['category']]['icon'];

            $station_urls[$short_name] = $station;
        }

        $this->view->station_urls = $station_urls;

        // Auto-Generated Convention Archive URLs
        $convention_details = Convention::getShortNameLookup();
        $convention_urls = array();

        foreach($convention_details as $short_name => $convention)
        {
            $convention['url'] = ShortUrl::getFullUrl($short_name);
            $convention_urls[$short_name] = $convention;
        }

        $this->view->convention_urls = $convention_urls;
    }

    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->url);
        
        if ($this->_hasParam('id'))
        {
            $record = ShortUrl::getRepository()->findOneBy(array(
                'id' => $this->_getParam('id'), 
                'station_id' => $this->station->id
            ));
            $form->setDefaults($record->toArray());
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();
            
            if (!($record instanceof ShortUrl))
                $record = new ShortUrl;

            $record->station = $this->station;
            $record->fromArray($data);

            if (!$record->checkUrl())
                throw new \DF\Exception\DisplayOnly('This URL is already taken! Please go back and try another.');

            $record->save();
            
            $this->alert('Record updated.', 'green');
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        if ($this->_hasParam('id'))
            $this->view->setVar('title', 'Edit Short URL');
        else
            $this->view->setVar('title', 'Add Short URL');

        $this->renderForm($form);
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');
        $record = ShortUrl::getRepository()->findOneBy(array(
            'id' => $id,
            'station_id' => $this->station->id
        ));

        if ($record instanceof ShortUrl)
            $record->delete();

        $this->alert('<b>Record deleted.</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }
}