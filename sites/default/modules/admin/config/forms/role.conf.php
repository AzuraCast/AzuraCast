<?php
/**
 * Edit Role Form
 */

$actions_raw = \Entity\Action::fetchArray('name');
$actions = array();
foreach($actions_raw as $action)
{
    $actions[$action['id']] = $action['name'];
}
 
return array(   
    /**
     * Form Configuration
     */
    'form' => array(
        'method'        => 'post',
        'elements'      => array(
                    
            'name'      => array('text', array(
                'label' => 'Role Name',
                'class' => 'half-width',
                'required' => true,
            )),
            
            'actions' => array('multiCheckbox', array(
                'label' => 'Actions',
                'multiOptions' => $actions,
            )),
            
            'submit'        => array('submit', array(
                'type'  => 'submit',
                'label' => 'Save Changes',
                'helper' => 'formButton',
                'class' => 'ui-button',
            )),
        ),
    ),
);