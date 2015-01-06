<?php
namespace Modules\Admin\Controllers;

use \Entity\Rotator;
use \Entity\Rotator as Record;

class RotatorsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer rotators');
    }
    
    public function indexAction()
    {
        $records = $this->em->createQuery('SELECT r FROM Entity\Rotator r ORDER BY r.id ASC')
            ->getArrayResult();
        $this->view->records = $records;
    }
    
    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->rotator);
        
        if ($this->_hasParam('id'))
        {
            $id = (int)$this->_getParam('id');
            $record = Record::find($id);

            $form->setDefaults($record->toArray(FALSE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            if (!($record instanceof Record))
                $record = new Record;

            $files = $form->processFiles('rotators');

            foreach($files as $file_field => $file_paths)
            {
                if (!empty($file_paths))
                    $data[$file_field] = $file_paths[1];
            }

            if ($data['image_url'])
                \DF\Image::resizeImage($data['image_url'], $data['image_url'], 336, 280);
            
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