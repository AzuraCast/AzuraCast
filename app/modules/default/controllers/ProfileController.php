<?php
use \Entity\User;
use \Entity\UserExternal;

class ProfileController extends \DF\Controller\Action
{
    public function permissions()
    {
        return $this->acl->isAllowed('is logged in');
    }

    public function indexAction()
    {
        $user = $this->auth->getLoggedInUser();
        $this->view->user = $user;

        // Process external authentication providers.
        $external_providers = UserExternal::getExternalProviders();

        if (count($user->external_accounts) > 0)
        {
            foreach($user->external_accounts as $ext)
            {
                $external_providers[$ext->provider]['existing'] = $ext;
            }
        }

        $this->view->external_providers = $external_providers;

        // Create song lists.
        $song_lists = array(
            'liked' => array(
                'name' => 'Songs I Liked',
                'items' => array(),
            ),
            'requested' => array(
                'name' => 'Songs I Requested',
                'items' => array(),
            ),
        );

        $liked_raw = $this->em->createQuery('SELECT sv, s, st FROM Entity\SongVote sv JOIN sv.song s JOIN sv.station st WHERE sv.user_id = :user_id ORDER BY sv.timestamp DESC')
            ->setParameter('user_id', $user->id)
            ->getArrayResult();

        foreach($liked_raw as $row)
        {
            $item = array(
                'timestamp' => $row['timestamp'],
                'station' => $row['station'],
                'song' => $row['song'],
            );
            $song_lists['liked']['items'][] = $item;
        }

        $requested_raw = $this->em->createQuery('SELECT sr, s, tr, st FROM Entity\StationRequest sr JOIN sr.station st JOIN sr.track tr JOIN tr.song s WHERE sr.user_id = :user_id ORDER BY sr.timestamp DESC')
            ->setParameter('user_id', $user->id)
            ->getArrayResult();

        foreach($requested_raw as $row)
        {
            $item = array(
                'timestamp' => $row['timestamp'],
                'station' => $row['station'],
                'song' => $row['track']['song'],
            );
            $song_lists['requested']['items'][] = $item;
        }

        $this->view->song_lists = $song_lists;
    }

    public function editAction()
    {
        $user = $this->auth->getLoggedInUser();
        $form = new \DF\Form($this->current_module_config->forms->profile);

        $user_profile = $user->toArray();
        $user_profile['customization'] = array_merge(\PVL\Customization::getDefaults(), $user_profile['customization']);
        $form->setDefaults($user_profile);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $user->fromArray($data);

            if (!empty($data['new_password']))
                $user->setAuthPassword($data['new_password']);

            $user->save();

            $this->alert('Profile saved!', 'green');
            $this->redirectFromHere(array('action' => 'index'));
            return;
        }

        $this->view->headTitle('Edit Profile');
        $this->renderForm($form);
    }

    public function themeAction()
    {
        $skin = $this->_getParam('skin', 'toggle');

        $current_skin = \PVL\Customization::get('theme');

        if ($skin == "toggle")
            $new_skin = ($current_skin == "dark") ? 'light' : 'dark';
        else
            $new_skin = $skin;

        \PVL\Customization::set('theme', $new_skin);

        $this->redirectToReferrer();
        return;
    }

    public function timezoneAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->timezone);
        $form->setDefaults(array(
            'timezone'      => \PVL\Customization::get('timezone'),
        ));

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            \PVL\Customization::set('timezone', $data['timezone']);

            $this->alert('Time zone updated!', 'green');
            $this->redirectToStoredReferrer('customization');
            return;
        }

        $this->storeReferrer('customization');

        $this->view->headTitle('Set Time Zone');
        $this->renderForm($form);
    }

}
