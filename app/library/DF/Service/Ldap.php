<?php
/**
 * LDAP Authentication Adapter
 */

namespace DF\Service;

define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);

use \Entity\Role;

class Ldap
{
	public static function authenticate($username, $password)
	{
		$ldapconn = self::init();
		$user_entry = self::findByUsername($username);
		
		if ($user_entry)
		{
			$user_dn = $user_entry['ldap_dn'];
			$login = @ldap_bind($ldapconn, $user_dn, $password);

			if ($user_dn && $login)
			{
				return new \Zend_Auth_Result(
					\Zend_Auth_Result::SUCCESS,
					$user_entry,
					array()
				);
			}
			else
			{
				return new \Zend_Auth_Result(
					\Zend_Auth_Result::FAILURE,
					null,
					(array)'Login failed.'
				);
			}
		}
		else
		{
			return new \Zend_Auth_Result(
				\Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
				null,
				array(
					'Username not found.'
				)
			);
		}
	}

	public static function search($query)
	{
		$fields_to_search = array(
			'sAMAccountName',
			'cn',
		);

		foreach($fields_to_search as $search_field)
		{
			$result = self::findByField($search_field, $query);
			if ($result)
				return $result;
		}

		return NULL;
	}
	
	public static function findById($id)
	{ return self::findByField('sID', $id); }
	
	public static function findByUsername($username)
	{ return self::findByField('sAMAccountName', $username); }

	public static function findByName()
	{ return self::findByField('cn', $username); }

	public static function findByField($field_name, $field_val)
	{
		$settings = self::getSettings();
		$ldapconn = self::init();

		$search_dn = $field_name.'='.$field_val;
		$results = ldap_search($ldapconn, $settings['basedn'], $search_dn);
		$entries = ldap_get_entries($ldapconn, $results);

		if ($entries['count'] > 0)
		{
			$entry = $entries[0];
			return self::processEntry($entry);
		}

		return NULL;
	}

	public static function processEntry($entry)
	{
		$settings = self::getSettings();

		// Parse user roles.
		$user_roles = array();

		foreach((array)$entry['memberof'] as $group_item)
		{
			$addr_parts = ldap_explode_dn($group_item, 1);
    		$group_name = ($addr_parts) ? trim($addr_parts[0]) : NULL;

			if (isset($settings['role_mapping'][$group_name]))
			{
				$role_name = $settings['role_mapping'][$group_name];

				if ($role_name instanceof Role)
				{
					$user_roles[] = $role->id;
				}
				else
				{
					$role = Role::getRepository()->findOneByName($role_name);
					if ($role instanceof Role)
						$user_roles[] = $role->id;
				}
			}
		}

		// Parse department name.
		$addr_parts = ldap_explode_dn($entry['dn'], 1);
    	$addr_parts = ($addr_parts) ? array_reverse($addr_parts) : array();
    	$dept_name = $addr_parts[6];
    	
    	return array(
			'ldap_guid'		=> base64_encode($entry['objectguid'][0]),
			'ldap_dn'		=> $entry['dn'],
			'username'		=> $entry['samaccountname'][0],
			'firstname'		=> $entry['givenname'][0],
			'lastname'		=> $entry['sn'][0],
			'title'			=> $entry['title'][0],
			'dept'			=> $dept_name,
			'email'			=> $entry['mail'][0],
			'phone'			=> $entry['telephonenumber'][0],
			'roles'			=> $user_roles,
		);
	}

	public static function init()
	{
		static $ldapconn;
		if (!$ldapconn)
		{
			if (DF_APPLICATION_ENV == "standalone")
				throw new \DF\Exception('LDAP authentication called in a standalone environment.');
			
			$settings = self::getSettings();
			
			ldap_set_option($ldapconn, LDAP_OPT_DEBUG_LEVEL, 7);
			
			$ldapconn = ldap_connect($settings['server']);
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3); 
			ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
			
			if ($settings['tls'])
			{
				putenv('LDAPTLS_REQCERT=never') or die('Failed to setup the env');
				ldap_start_tls($ldapconn);
			}

			@ldap_bind($ldapconn, $settings['binddn'], $settings['bindpw']);
		}

		return $ldapconn;
	}

	public static function getSettings()
	{
		static $settings;
		if (!$settings)
		{
			$config = \Zend_Registry::get('config');
			$settings = $config->services->ldap->toArray();
		}
		return $settings;
	}
}