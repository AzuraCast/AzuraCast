<?php
namespace Modules\Frontend\Controllers;

use \Entity\Convention as Record;
use \Entity\Convention;
use \Entity\ConventionSignup;
use \Entity\ConventionArchive;

class ConventionController extends BaseController
{
    /**
     * @param bool $required
     * @return \Entity\Convention
     * @throws DF\Exception\DisplayOnly
     */
    protected function _getConvention($required = false)
    {
        $convention = null;
        if ($this->hasParam('id'))
        {
            $id = (int)$this->getParam('id');
            $convention = Convention::find($id);
        }

        if ($convention instanceof Convention)
            return $convention;
        elseif ($required)
            throw new \DF\Exception\DisplayOnly('Convention not specified!');
        else
            return null;
    }

    // Public convention archives view.
    public function indexAction()
    {
        if ($this->hasParam('id'))
            $this->redirectFromHere(array('action' => 'archive'));

        // Pull conventions.
        $conventions = Convention::getAllConventions();
        $this->view->conventions_upcoming = $conventions['upcoming'];
        $this->view->conventions_archived = $conventions['archived'];

        $this->render();
    }

    public function archiveAction()
    {
        $convention = $this->_getConvention();

        if (!$convention)
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));

        $this->view->convention = $convention;

        $videos = array();
        $sources = array();
        $folders = ConventionArchive::getFolders();

        foreach($folders as $folder_key => $folder_name)
            $videos[$folder_name] = array('key' => $folder_key, 'name' => $folder_name, 'videos' => array());

        foreach($convention->archives as $row)
        {
            if ($row->isPlayable())
            {
                $folder_name = $folders[$row->folder];
                $videos[$folder_name]['videos'][] = $row;
            }
            else
            {
                $sources[] = $row;
            }
        }

        foreach($videos as $folder_name => $row)
        {
            if (empty($row['videos']))
                unset($videos[$folder_name]);
        }

        $this->view->videos = $videos;
        $this->view->sources = $sources;

        // Pull conventions.
        $conventions = Convention::getAllConventions();
        $this->view->conventions_archived = $conventions['archived'];
    }

    public function signupAction()
    {
        $this->acl->checkPermission('is logged in');

        $user = $this->auth->getLoggedInUser();

        // Get conventions that are available for signup.
        $upcoming_cons = array();
        $previous_cons = array();

        $cons_raw = $this->em->createQuery('SELECT c FROM Entity\Convention c WHERE (c.signup_enabled = 1 AND c.end_date >= :now) ORDER BY c.start_date ASC')
            ->setParameter('now', gmdate('Y-m-d'))
            ->getArrayResult();

        foreach($cons_raw as $row)
        {
            $row['range'] = Convention::getDateRange($row['start_date'], $row['end_date']);
            $upcoming_cons[$row['id']] = $row;
        }

        // Get conventions that the user has signed up for.
        $cons_signed_up = $this->em->createQuery('SELECT cs, c FROM Entity\ConventionSignup cs JOIN cs.convention c WHERE (cs.user_id = :user_id) ORDER BY c.start_date DESC')
            ->setParameter('user_id', $user->id)
            ->getArrayResult();

        foreach($cons_signed_up as $row)
        {
            $con_id = $row['convention_id'];

            if (isset($upcoming_cons[$con_id]))
            {
                $upcoming_cons[$con_id]['signup'] = $row;
            }
            else
            {
                $con = $row['convention'];
                $con['range'] = Convention::getDateRange($con['start_date'], $con['end_date']);

                $previous_cons[$con_id] = $con;
                $previous_cons[$con_id]['signup'] = $row;
            }
        }

        $this->view->upcoming_cons = $upcoming_cons;
        $this->view->previous_cons = $previous_cons;
    }

    public function editsignupAction()
    {
        $this->acl->checkPermission('is logged in');

        $con = $this->_getConvention(TRUE);
        $user = $this->auth->getLoggedInUser();

        if (!($con instanceof Convention))
            throw new \DF\Exception\DisplayOnly('Convention not found!');

        if (!$con->canSignup())
            throw new \DF\Exception\DisplayOnly('You cannot add or update a signup record for this convention.');

        // Retrieve auto-customized form for this convention.
        $form = ConventionSignup::getForm($con);

        $record = ConventionSignup::getRepository()->findOneBy(array('convention_id' => $con->id, 'user_id' => $user->id));
        if ($record instanceof ConventionSignup)
            $form->setDefaults($record->toArray());
        else
            $form->setDefaults($user->toArray());

        if ($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            if (!($record instanceof ConventionSignup))
            {
                $record = new ConventionSignup;
                $record->convention = $con;
                $record->user = $user;
            }

            $record->fromArray($data);
            $record->save();

            // Save some data to the user profile.
            $user->fromArray(array(
                'legal_name'    => $data['legal_name'],
                'pony_name'     => $data['pony_name'],
                'phone'         => $data['phone'],
                'pvl_affiliation' => $data['pvl_affiliation'],
            ));
            $user->save();

            $this->alert('<b>Convention registration successfully submitted.</b><br>You will be contacted by the PVL administrators with more information.', 'green');
            $this->redirectFromHere(array('action' => 'signup', 'id' => NULL));
            return;
        }

        $this->view->headTitle('Convention Signup Form');
        $this->renderForm($form);
    }

    public function deletesignupAction()
    {
        $this->acl->checkPermission('is logged in');

        $con = $this->_getConvention(TRUE);
        $user = $this->auth->getLoggedInUser();

        $record = ConventionSignup::getRepository()->findOneBy(array('convention_id' => $con->id, 'user_id' => $user->id));
        if ($record instanceof ConventionSignup)
            $record->delete();

        $this->alert('<b>Convention registration removed.</b>', 'green');

        $this->redirectFromHere(array('action' => 'signup', 'id' => NULL));
        return;
    }
}