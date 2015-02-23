<?php
namespace Modules\Admin\Controllers;

use \Entity\Podcast;
use \Entity\Podcast as Record;

class PodcastsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer podcasts');
    }
    
    public function indexAction()
    {
        $query = $this->em->createQuery('SELECT p FROM Entity\Podcast p ORDER BY p.name ASC');
        $this->view->pager = new \DF\Paginator\Doctrine($query, $this->getParam('page', 1), 50);
    }
    
    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->podcast);
        
        if ($this->hasParam('id'))
        {
            $id = (int)$this->getParam('id');
            $record = Record::find($id);
            $form->setDefaults($record->toArray(TRUE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            
            if (!($record instanceof Record))
                $record = new Record;

            $files = $form->processFiles('podcasts');

            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];
            
            $record->fromArray($data);
            $record->save();
            
            $this->alert('Changes saved.', 'green');
            return $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
        }

        $this->renderForm($form, 'edit', 'Edit Record');
    }
    
    public function deleteAction()
    {
        $record = Record::find($this->getParam('id'));
        if ($record)
            $record->delete();
            
        $this->alert('Record deleted.', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }
}