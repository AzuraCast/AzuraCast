<?php
namespace Modules\Admin\Controllers;

use Entity\Station;
use Entity\Podcast;

class ContactController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer all');
    }

    public function indexAction()
    {
        // Assemble list of stations.
        $all_stations = $this->em->createQuery('SELECT s, u FROM Entity\Station s LEFT JOIN s.managers u WHERE s.is_active = 1')->getArrayResult();
        $station_select = array();
        $station_contacts = array();

        foreach($all_stations as $station)
        {
            $station_id = $station['id'];
            $station_contact = array();

            if (!empty($station['contact_email']))
                $station_contact[] = strtolower($station['contact_email']);

            if (!empty($station['managers']))
            {
                foreach($station['managers'] as $manager)
                    $station_contact[] = strtolower($manager['email']);
            }

            if (!empty($station_contact))
                $station_text = $station['name'];
            else
                $station_text = '<span class="text-disabled" title="No e-mail addresses on file!">'.$station['name'].'</span>';

            $station_select[$station_id] = $station_text;
            $station_contacts[$station_id] = array_unique($station_contact);
        }

        // Assemble list of podcasts.
        $all_podcasts = $this->em->createQuery('SELECT p, u FROM Entity\Podcast p LEFT JOIN p.managers u WHERE p.is_approved = 1')->getArrayResult();
        $podcast_select = array();
        $podcast_contacts = array();

        foreach($all_podcasts as $podcast)
        {
            $podcast_id = $podcast['id'];
            $podcast_contact = array();

            if (!empty($podcast['contact_email']))
                $podcast_contact[] = strtolower($podcast['contact_email']);

            if (!empty($podcast['managers']))
            {
                foreach($podcast['managers'] as $manager)
                    $podcast_contact[] = strtolower($podcast['email']);
            }

            if (!empty($podcast_contact))
                $podcast_text = $podcast['name'];
            else
                $podcast_text = '<span class="text-disabled" title="No e-mail addresses on file!">'.$podcast['name'].'</span>';

            $podcast_select[$podcast_id] = $podcast_text;
            $podcast_contacts[$podcast_id] = array_unique($podcast_contact);
        }

        // Assemble contact form.
        $form_info = $this->current_module_config->forms->contact->form->toArray();

        $form_info['groups']['recipients']['elements']['stations'][1]['multiOptions'] = $station_select;
        $form_info['groups']['recipients']['elements']['podcasts'][1]['multiOptions'] = $podcast_select;

        $form = new \DF\Form($form_info);

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            $email_to = array();

            foreach((array)$data['stations'] as $station_id)
                $email_to = array_merge($email_to, $station_contacts[$station_id]);

            foreach((array)$data['podcasts'] as $podcast_id)
                $email_to = array_merge($email_to, $podcast_contacts[$podcast_id]);

            $email_to = array_unique($email_to);

            \DF\Messenger::send(array(
                'to'        => $email_to,
                'subject'   => $data['subject'],
                'template'  => 'bulkcontact',
                'vars'      => array(
                    'body' => nl2br($data['body']),
                ),
            ));

            $this->alert('<b>Message sent to '.count($email_to).' recipient(s)!</b>', 'green');

            return $this->redirectHere();
        }

        $this->view->form = $form;
    }
}
