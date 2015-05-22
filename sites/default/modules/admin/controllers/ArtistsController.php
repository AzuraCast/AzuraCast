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
        
        if ($this->hasParam('q'))
        {
            $this->view->q = $q = trim($this->getParam('q'));
            $query = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at WHERE (a.name LIKE :q) ORDER BY a.name ASC')
                ->setParameter('q', '%'.$q.'%');
        }
        else
        {
            $query = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at ORDER BY a.is_approved ASC, a.name ASC');
        }
        
        $this->view->pager = new \DF\Paginator\Doctrine($query, $this->getParam('page', 1), 30);
    }
    
    public function editAction()
    {
        $form_config = $this->current_module_config->forms->artist->toArray();
        unset($form_config['groups']['intro']);

        $form = new \DF\Form($form_config);
        
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

            $files = $form->processFiles('artists');

            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];
            
            $record->fromArray($data);
            $record->save();
            
            $this->alert('Changes saved.', 'green');

            $origin = $this->getParam('origin', 'admin');
            if ($origin == 'profile')
                return $this->redirectToRoute(array('module' => 'default', 'controller' => 'artists', 'action' => 'view', 'id' => $record->id));
            else
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