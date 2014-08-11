<?php
use \Entity\Convention as Record;
use \Entity\Convention;
use \Entity\ConventionSignup;
use \Entity\ConventionArchive;

class Admin_ConventionsController extends \PVL\Controller\Action\Admin
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer conventions');
    }

    protected function _getConvention()
    {
        if (!$this->hasParam('convention'))
            throw new \DF\Exception\DisplayOnly('No convention specified!');

        $con_id = (int)$this->getParam('convention');
        $con = Convention::find($con_id);

        if ($con instanceof Convention)
        {
            $this->view->convention = $con;
            return $con;
        }
        else
        {
            throw new \DF\Exception\DisplayOnly('Convention ID not found!');
        }
    }
    protected function _flushConventionCache()
    {
        \DF\Cache::remove('homepage_conventions');
    }

    public function indexAction()
    {
        $this->view->coverage = Convention::getCoverageLevels();

        $query = $this->em->createQuery('SELECT c, cs, ca FROM Entity\Convention c LEFT JOIN c.signups cs LEFT JOIN c.archives ca ORDER BY c.start_date DESC');
        $this->view->pager = new \DF\Paginator\Doctrine($query, $this->_getParam('page', 1), 15);
    }

    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->convention);

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

            $files = $form->processFiles('conventions');

            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];

            $record->fromArray($data);
            $record->save();

            $this->_flushConventionCache();

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

        $this->_flushConventionCache();

        $this->alert('Record deleted.', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }

    /**
     * Signup Management
     */

    public function signupsAction()
    {
        $con = $this->_getConvention();

        switch($this->getParam('format'))
        {
            case "csv":
                $export_data = array();
                $export_data[] = array(
                    'Pony/Badge Name',
                    'Legal Name',
                    'Phone Number',
                    'E-mail Address',
                    'PVL Affiliation',
                    'Travel Notes',
                    'Accommodation Notes',
                );

                foreach($con->signups as $row)
                {
                    $export_data[] = array(
                        $row->pony_name,
                        $row->legal_name,
                        $row->phone,
                        $row->email,
                        $row->pvl_affiliation,
                        $row->travel_notes,
                        $row->accommodation_notes,
                    );
                }

                \DF\Export::csv($export_data, TRUE, 'Signups - '.$con->name);
            break;

            case "html":
            default:
                $this->view->signups = $con->signups;

                $this->render();
            break;
        }
    }

    public function editsignupAction()
    {
        $con = $this->_getConvention();
        $form = ConventionSignup::getForm($con);

        $id = (int)$this->getParam('id');
        $record = ConventionSignup::find($id);

        if ($record instanceof ConventionSignup)
            $form->setDefaults($record->toArray(TRUE, TRUE));
        else
            throw new \DF\Exception\DisplayOnly('Cannot create new signup records from this page.');

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            $record->fromArray($data);
            $record->save();

            $this->alert('Changes saved.', 'green');
            $this->redirectFromHere(array('action' => 'signups', 'convention' => $con->id, 'id' => NULL));
            return;
        }

        $this->view->headTitle('Edit Signup Record');
        $this->renderForm($form);
    }

    public function deletesignupAction()
    {
        $con = $this->_getConvention();

        $record = ConventionSignup::find($this->_getParam('id'));
        if ($record instanceof ConventionSignup)
            $record->delete();

        $this->alert('Record deleted.', 'green');
        $this->redirectFromHere(array('action' => 'signups', 'convention' => $con->id, 'id' => NULL, 'csrf' => NULL));
    }

    /**
     * Convention Archive Management
     */

    public function archivesAction()
    {
        $con = $this->_getConvention();

        $folders = ConventionArchive::getFolders();
        $this->view->folders = $folders;

        $types = ConventionArchive::getTypes();

        $archives_raw = $con->archives;
        $archives = array();

        if (count($archives_raw) > 0)
        {
            foreach($archives_raw as $row)
            {
                $row_arr = $row->toArray();
                $row_arr['type_text'] = $types[$row_arr['type']];

                if ($row->playlist_id)
                {
                    $archives[$row->playlist_id]['videos'][] = $row_arr;
                }
                else
                {
                    $row_arr['videos'] = array();
                    $archives[$row->id] = $row_arr;
                }
            }
        }

        $archives_by_folder = array();
        foreach($archives as $id => $row)
        {
            $archives_by_folder[$row['folder']][$id] = $row;
        }

        $this->view->archives = $archives_by_folder;
    }

    public function editarchiveAction()
    {
        $con = $this->_getConvention();

        $form = new \DF\Form($this->current_module_config->forms->conventionarchive);

        if ($this->_hasParam('id'))
        {
            $id = (int)$this->_getParam('id');
            $record = ConventionArchive::find($id);

            $form->setDefaults($record->toArray(TRUE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            if (!($record instanceof ConventionArchive))
            {
                $record = new ConventionArchive;
                $record->convention = $con;
            }

            $record->fromArray($data);
            $record->save();

            $record->process();

            $this->alert('Changes saved.', 'green');
            $this->redirectFromHere(array('action' => 'archives', 'convention' => $con->id, 'id' => NULL));
            return;
        }

        $this->view->headTitle('Edit Convention Archive Item');
        $this->renderForm($form);
    }

    public function deletearchiveAction()
    {
        $con = $this->_getConvention();

        $record = ConventionArchive::find($this->_getParam('id'));
        if ($record instanceof ConventionArchive)
            $record->delete();

        $this->alert('Record deleted.', 'green');
        $this->redirectFromHere(array('action' => 'archives', 'convention' => $con->id, 'id' => NULL, 'csrf' => NULL));
    }
}