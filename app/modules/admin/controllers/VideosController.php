<?php
namespace Modules\Admin\Controllers;

use \Entity\VideoChannel;
use \Entity\VideoChannel as Record;

class VideosController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer stations');
    }
    
    public function indexAction()
    {
        $records = $this->em->createQuery('SELECT v FROM Entity\Video v ORDER BY v.weight ASC, v.id ASC')
            ->getArrayResult();

        $this->view->stations = $records;
    }
    
    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->video);
        
        if ($this->hasParam('id'))
        {
            $id = (int)$this->getParam('id');
            $record = Record::find($id);
            $form->setDefaults($record->toArray(FALSE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            if (!($record instanceof Record))
                $record = new Record;

            $files = $form->processFiles('videos');

            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];
            
            $record->fromArray($data);
            $record->save();

            // Clear station cache.
            \DF\Cache::remove('video_channels');

            $this->alert('Changes saved.', 'green');
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        $this->view->setVar('title', 'Edit Record');
        $this->renderForm($form);
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