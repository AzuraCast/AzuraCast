<?php
namespace Modules\Frontend\Controllers;

use \Entity\User;
use \Entity\UserExternal;

class ProfileController extends BaseController
{
    public function indexAction()
    {
        $this->acl->checkPermission('is logged in');

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
            'requested' => array(
                'name' => 'Songs I Requested',
                'icon' => 'icon-question-sign',
                'items' => array(),
            ),
            'liked' => array(
                'name' => 'Songs I Liked',
                'icon' => 'icon-thumbs-up',
                'items' => array(),
            ),
            'disliked' => array(
                'name' => 'Songs I Disliked',
                'icon' => 'icon-thumbs-down',
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

            if ((int)$row['vote'] > 0)
                $song_lists['liked']['items'][] = $item;
            else
                $song_lists['disliked']['items'][] = $item;
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
        $this->acl->checkPermission('is logged in');

        $user = $this->auth->getLoggedInUser();
        $form = new \App\Form($this->current_module_config->forms->profile);

        $user_profile = $user->toArray();
        $user_profile['customization'] = array_merge(\App\Customization::getDefaults(), $user_profile['customization']);
        unset($user_profile['auth_password']);

        $form->setDefaults($user_profile);

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $user->fromArray($data);
            $user->save();

            foreach($data['customization'] as $custom_key => $custom_val)
                \App\Customization::set($custom_key, $custom_val);

            $this->alert('Profile saved!', 'green');
            $this->redirectFromHere(array('action' => 'index'));
            return;
        }

        $this->renderForm($form, 'edit', 'Edit Profile');
    }

    public function themeAction()
    {
        $skin = $this->getParam('skin', 'toggle');

        $current_skin = \App\Customization::get('theme');

        if ($skin == "toggle")
            $new_skin = ($current_skin == "dark") ? 'light' : 'dark';
        else
            $new_skin = $skin;

        \App\Customization::set('theme', $new_skin);

        $this->redirectToReferrer();
        return;
    }

    public function timezoneAction()
    {
        $form = new \App\Form($this->current_module_config->forms->timezone);
        $form->setDefaults(array(
            'timezone'      => \App\Customization::get('timezone'),
        ));

        if($_POST && $form->isValid($_POST))
        {
            $data = $form->getValues();

            \App\Customization::set('timezone', $data['timezone']);

            $this->alert('Time zone updated!', 'green');
            $this->redirectToStoredReferrer('customization');
            return;
        }

        $this->storeReferrer('customization');

        $this->view->setVar('title', 'Set Time Zone');
        $this->renderForm($form);
    }

    /**
     * Customize the default active stream for a station.
     */
    public function streamAction()
    {
        $this->doNotRender();

        $station_id = (int)$this->getParam('station', 0);
        $stream_id = (int)$this->getParam('stream', 0);

        $default_streams = (array)\App\Customization::get('stream_defaults');
        $default_streams[$station_id] = $stream_id;

        \App\Customization::set('stream_defaults', $default_streams);

        echo 'OK';
    }

}
