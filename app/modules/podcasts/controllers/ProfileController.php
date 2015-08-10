<?php
namespace Modules\Podcasts\Controllers;

use \Entity\Podcast;

class ProfileController extends BaseController
{
    protected function _getForm()
    {
        $base_form = $this->module_config['admin']->forms->podcast->toArray();
        unset($base_form['groups']['admin']);

        return new \DF\Form($base_form);
    }

    public function indexAction()
    {
        $form = $this->_getForm();
        $form->populate($this->podcast->toArray());

        $this->view->form = $form;
    }

    public function editAction()
    {
        $form = $this->_getForm();

        $form->setDefaults($this->podcast->toArray(true, true));

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $files = $form->processFiles('podcasts');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            $this->podcast->fromArray($data);
            $this->podcast->save();

            // Clear station cache.
            \DF\Cache::remove('podcasts');

            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        $this->renderForm($form, 'edit', 'Edit Podcast Profile');
    }
}
