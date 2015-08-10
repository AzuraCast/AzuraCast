<?php
namespace Modules\Podcasts\Controllers;

use Entity\Podcast;
use Entity\PodcastSource;

class SourcesController extends BaseController
{
    public function indexAction()
    {
        $this->view->sources = $this->podcast->sources;
    }

    public function editAction()
    {
        $form_config = $this->current_module_config->forms->source;
        $form = new \DF\Form($form_config);

        if ($this->hasParam('id'))
        {
            $record = PodcastSource::getRepository()->findOneBy(array(
                'id' => $this->getParam('id'),
                'podcast_id' => $this->podcast->id
            ));
            $form->setDefaults($record->toArray());
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            if (!($record instanceof PodcastSource))
            {
                $record = new PodcastSource();
                $record->podcast = $this->podcast;
            }

            $record->fromArray($data);
            $record->save();

            // Clear cache.
            \DF\Cache::remove('podcasts');

            // Immediately re-process this podcast.
            \PVL\PodcastManager::processPodcast($this->podcast);

            $this->alert('<b>Podcast source updated.</b>', 'green');

            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        $title = (($this->hasParam('id')) ? 'Edit' : 'Add').' Podcast Source';
        $this->renderForm($form, 'edit', $title);
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');

        $record = PodcastSource::getRepository()->findOneBy(array(
            'id' => $this->getParam('id'),
            'podcast_id' => $this->podcast->id
        ));

        if ($record instanceof PodcastSource)
            $record->delete();

        $this->alert('<b>Record deleted.</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }
}
