<?php
namespace Modules\Admin\Controllers;

use \Entity\Artist;
use \Entity\Artist as Record;

class ArtistsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer artists');
    }
    
    public function indexAction()
    {
        if ($_GET)
            $this->redirectFromHere($_GET);
        
        if ($this->_hasParam('q'))
        {
            $this->view->q = $q = trim($this->_getParam('q'));
            $query = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at WHERE (a.name LIKE :q) ORDER BY a.name ASC')
                ->setParameter('q', '%'.$q.'%');
        }
        else
        {
            $query = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at ORDER BY a.is_approved ASC, a.name ASC');
        }
        
        $this->view->pager = new \DF\Paginator\Doctrine($query, $this->_getParam('page', 1), 30);
    }
    
    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->artist);
        
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

            $files = $form->processFiles('artists');

            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];
            
            $record->fromArray($data);
            $record->save();
            
            $this->alert('Changes saved.', 'green');

            $origin = $this->_getParam('origin', 'admin');
            if ($origin == 'profile')
                $this->redirectToRoute(array('module' => 'default', 'controller' => 'artists', 'action' => 'view', 'id' => $record->id));
            else
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