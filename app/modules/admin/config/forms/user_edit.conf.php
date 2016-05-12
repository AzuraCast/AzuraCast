<?php
/**
 * Edit User form 
 */

return array(   
    /**
     * Form Configuration
     */
    'form' => array(
        'method'        => 'post',
        'elements'      => array(
            
            'name'      => array('text', array(
                'label' => 'Name',
            )),
            
            'email' => array('text', array(
                'label' => 'E-mail Address (Username)',
                'validators' => array('EmailAddress'),
                'required' => true,
                'autocomplete' => 'off',
            )),

            'auth_password' => array('password', array(
                'label' => 'Reset Password',
                'description' => 'Leave blank to persist current password.',
                'autocomplete' => 'off',
            )),
            
            'roles'         => array('multiCheckbox', array(
                'label' => 'Roles',
                'multiOptions' => \Entity\Role::fetchSelect(),
            )),
            
            'submit'        => array('submit', array(
                'type'  => 'submit',
                'label' => 'Save Changes',
                'helper' => 'formButton',
                'class' => 'btn btn-lg btn-primary',
            )),
        ),
    ),
);