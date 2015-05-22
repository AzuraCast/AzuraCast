<?php
namespace Modules\Admin\Controllers;

use \Entity\Station;
use \Entity\Convention;
use \Entity\ShortUrl;

class UrlsController extends BaseController
{
    public function indexAction()
    {
        $urls = $this->em->createQuery('SELECT su, s FROM Entity\ShortUrl su LEFT JOIN su.station s ORDER BY su.station_id, su.short_url ASC')
            ->getArrayResult();

        $global_custom_urls = array();
        $station_custom_urls = array();

        foreach($urls as $url)
        {
            if ($url['station'])
                $station_custom_urls[] = $url;
            else
                $global_custom_urls[] = $url;
        }

        $this->view->station_custom_urls = $station_custom_urls;
        $this->view->global_custom_urls = $global_custom_urls;

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

        if ($this->hasParam('id'))
        {
            $record = ShortUrl::getRepository()->find($this->getParam('id'));
            $form->setDefaults($record->toArray());
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            if (!($record instanceof ShortUrl))
                $record = new ShortUrl;

            $record->fromArray($data);

            if (!$record->checkUrl())
                throw new \DF\Exception\DisplayOnly('This URL is already taken! Please go back and try another.');

            $record->save();

            $this->alert('Record updated.', 'green');
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        $this->renderForm($form, 'edit', ($this->hasParam('id')) ? 'Edit Short URL' : 'Add Short URL');
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');
        $record = ShortUrl::getRepository()->find($id);

        if ($record instanceof ShortUrl)
            $record->delete();

        $this->alert('<b>Record deleted.</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }
}