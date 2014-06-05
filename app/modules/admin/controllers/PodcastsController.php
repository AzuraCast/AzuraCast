<?php
use \Entity\Podcast;
use \Entity\Podcast as Record;

class Admin_PodcastsController extends \DF\Controller\Action
{
    public function permissions()
    {
        return \DF\Acl::getInstance()->isAllowed('administer podcasts');
    }
    
    public function indexAction()
    {
        $query = $this->em->createQuery('SELECT p FROM Entity\Podcast p ORDER BY p.name ASC');
        $this->view->pager = new \DF\Paginator\Doctrine($query, $this->_getParam('page', 1), 50);
    }
    
    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->podcast);
        
        if ($this->_hasParam('id'))
        {
            $id = (int)$this->_getParam('id');
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
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        $this->view->headTitle('Edit Record');
        $this->renderForm($form);
    }
    
    public function deleteAction()
    {
        $record = Record::find($this->_getParam('id'));
        if ($record)
            $record->delete();
            
        $this->alert('Record deleted.', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }
}