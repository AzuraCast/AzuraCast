<?php
/**
 * Edit User form 
 */

return array(	
	/**
	 * Form Configuration
	 */
	'form' => array(
		'method'		=> 'post',
		'elements'		=> array(
			
			'name'		=> array('text', array(
				'label' => 'Name',
	        )),
			
			'email'	=> array('text', array(
				'label' => 'E-mail Address (Username)',
				'validators' => array('EmailAddress'),
				'required' => true,
			)),

			'auth_password'	=> array('password', array(
				'label' => 'Reset Password',
			)),
			
			'roles'			=> array('multiCheckbox', array(
				'label' => 'Roles',
				'multiOptions' => \Entity\Role::fetchSelect(),
			)),
			
			'submit'		=> array('submit', array(
				'type'	=> 'submit',
				'label'	=> 'Save Changes',
				'helper' => 'formButton',
				'class' => 'ui-button',
			)),
		),
	),
);