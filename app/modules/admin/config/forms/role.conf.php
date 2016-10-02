<?php
/**
 * Edit Role Form
 */

/** @var \Doctrine\ORM\EntityManager $em */
$em = $di['em'];

return [
    /**
     * Form Configuration
     */
    'form' => [
        'method' => 'post',
        'elements' => [

            'name' => ['text', [
                'label' => _('Role Name'),
                'class' => 'half-width',
                'required' => true,
            ]],

            'actions' => ['multiCheckbox', [
                'label' => _('Actions'),
                'options' => $em->getRepository(\Entity\Action::class)->fetchSelect(),
            ]],

            'submit' => ['submit', [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'helper' => 'formButton',
                'class' => 'btn btn-lg btn-primary',
            ]],
        ],
    ],
];