<?php
/**
 * Edit Role Form
 */

$actions_raw = \Entity\Action::fetchArray('name');
$actions = [];
foreach ($actions_raw as $action)
    $actions[$action['id']] = $action['name'];

return [
    /**
     * Form Configuration
     */
    'form' => [
        'method' => 'post',
        'elements' => [

            'name' => ['text', [
                'label' => 'Role Name',
                'class' => 'half-width',
                'required' => true,
            ]],

            'actions' => ['multiCheckbox', [
                'label' => 'Actions',
                'multiOptions' => $actions,
            ]],

            'submit' => ['submit', [
                'type' => 'submit',
                'label' => 'Save Changes',
                'helper' => 'formButton',
                'class' => 'btn btn-lg btn-primary',
            ]],
        ],
    ],
];