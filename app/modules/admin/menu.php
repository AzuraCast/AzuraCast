<?php
return array(
    'default' => array(
        'admin' => array(
            'label' => 'Administer',
            'module' => 'admin',
            'show_only_with_subpages' => TRUE,
			
            'order' => 5,
            'pages' => array(
            	'settings'	=> array(
					'label'	=> 'Settings',
					'module' => 'admin',
					'controller' => 'settings',
					'permission' => 'administer all',
				),

				'blocks'	=> array(
					'label'	=> 'Content Blocks',
					'module' => 'admin',
					'controller' => 'blocks',
					'permission' => 'administer blocks',
				),

				'stations'	=> array(
					'label'	=> 'Stations',
					'module' => 'admin',
					'controller' => 'stations',
					'permission' => 'administer stations',
				),

				'rotator'	=> array(
					'label'	=> 'Homepage Rotator',
					'module' => 'admin',
					'controller' => 'rotator',
					'permission' => 'administer rotator',
				),

				'artists' => array(
					'label'	=> 'Artists',
					'module' => 'admin',
					'controller' => 'artists',
					'permission' => 'administer artists',
				),

				'events' => array(
					'label'	=> 'Events',
					'module' => 'admin',
					'controller' => 'events',
					'permission' => 'administer events',
				),
				
                'users' => array(
                    'label' => 'Users',
                    'module' => 'admin',
                    'controller' => 'users',
					'action' => 'index',
					'permission' => 'administer all',
				),
				'permissions' => array(
					'label' => 'Permissions',
					'module' => 'admin',
					'controller' => 'permissions',
					'permission' => 'administer all',
					'pages'	=> array(
						'permissions_members' => array(
							'module'	=> 'admin',
							'controller' => 'permissions',
							'action'	=> 'members',
						),
					),
				),
                'files' => array(
                    'label' => 'Files',
                    'module' => 'admin',
                    'controller' => 'files',
                    'action' => 'index',
                    'permission' => 'administer all',
                ),
            ),
        ),
    ),
);