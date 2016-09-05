<?php
namespace Modules\Admin\Controllers;

use Entity\ApiKey as Record;

class ApiController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer api keys');
    }

    public function indexAction()
    {
        $this->view->records = Record::fetchArray();
    }

    public function editAction()
    {
        $form = new \App\Form($this->current_module_config->forms->api_key);

        if ($this->hasParam('id'))
        {
            $id = $this->getParam('id');
            $record = Record::find($id);
            $form->setDefaults($record->toArray(TRUE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            if (!($record instanceof Record))
                $record = new Record;

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
        if ($record instanceof Record)
            $record->delete();

        $this->alert('Record deleted.', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }
}