<?php
use \Entity\Block;

class Admin_BlocksController extends \PVL\Controller\Action\Admin
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer blocks');
    }
    
    public function indexAction()
    {
        $this->view->all_blocks = $this->em->createQuery('SELECT b FROM Entity\Block b ORDER BY b.name ASC')->getArrayResult();
    }
    
    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->block->form);
        
        if ($this->_hasParam('id'))
        {
            $id = (int)$this->_getParam('id');
            $record = Block::find($id);
            $form->setDefaults($record->toArray(TRUE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            
            if (!($record instanceof Block))
                $record = new Block;
            
            $record->fromArray($data);
            $record->save();
            
            $this->alert('Changes saved.');
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        $this->view->form = $form;
    }
    
    public function previewAction()
    {
        $record = Block::find($this->_getParam('id'));
        $this->view->block = $record;
    }
    
    public function deleteAction()
    {
        $record = Block::find($this->_getParam('id'));
        if ($record)
            $record->delete();
            
        $this->alert('Record deleted.');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }
}