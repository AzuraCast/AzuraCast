<?php
/**
 * New User form 
 */

return array(	
	/**
	 * Form Configuration
	 */
	'form' => array(
		'method'		=> 'post',
		'elements'		=> array(
		
			'uin' => array('textarea', array(
				'label' => 'UIN',
				'required' => true,
				'attribs' => array(
					'class'		=> 'half-width full-height',
				),
				'description' => 'Multiple UINs can be added at once; enter each UIN on its own line.',
			)),
			
			'roles' => array('multicheckbox', array(
				'label' => 'Roles',
				'multiOptions' => \Entity\Role::fetchSelect(),
			)),
			
			'submit' => array('submit', array(
				'type'	=> 'submit',
				'label'	=> 'Create User',
				'helper' => 'formButton',
				'class' => 'ui-button',
			)),
		),
	),
);