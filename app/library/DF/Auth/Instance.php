<?php
namespace DF\Auth;

use \Entity\User;

class Instance
{
	protected $_adapter;
	protected $_session;
    protected $_user = NULL;
    protected $_masqueraded_user = NULL;
	
	public function __construct()
	{
		$this->_session = $this->getSession();
	}
	
	public function getSession()
    {
		$class_name = strtolower(str_replace(array('\\', '_'), array('', ''), get_called_class()));
		return \DF\Session::get('auth_'.$class_name.'_user');
    }
    
	public function login()
    {
        if ($this->isLoggedIn() || php_sapi_name() == 'cli')
            return true;
        else
			return $this->authenticate();
    }
    
    public function authenticate()
    {
        $result = $this->_adapter->authenticate();
        
        unset($this->_session->user_id);
        unset($this->_session->masquerade_user_id);
        
        return $result;
    }

	public function logout($destination = NULL, $unset_session = true)
    {
		unset($this->_session->identity);
		unset($this->_session->user_id);
        unset($this->_session->masquerade_user_id);
        
        if ($unset_session)
			@session_unset();
		
		if (method_exists($this->_adapter, 'logout'))
			$this->_adapter->logout($destination);
    }

	public function isLoggedIn()
    {
        if( php_sapi_name() == 'cli' )
            return false;
        
        $user = $this->getUser();
        return ($user instanceof User);
    }
    
    public function getLoggedInUser($real_user_only = FALSE)
    {
		if ($this->isMasqueraded() && !$real_user_only)
			return $this->getMasquerade();
		else
			return $this->getUser();
	}
    
	public function getUser()
    {
        if ($this->_user === NULL)
        {
			$user_id = (int)$this->_session->user_id;
			
			if ($user_id == 0)
			{
				$this->_user = FALSE;
				return false;
			}
			
			$user = User::find($user_id);
			if ($user instanceof User)
			{
				$this->_user = $user;
			}
			else
			{
				unset($this->_session->user_id);
				$this->_user = FALSE;
				$this->logout();
				
				throw new Exception\InvalidUser;
			}
		}
		
		return $this->_user;
    }
    
    public function setUser(User $user)
    {
		// Prevent any previous identity from being used.
		unset($this->_session->identity);
		
		$this->_session->user_id = $user->id;
		$this->_user = $user;
		return true;
    }
    
	public function getAdapter()
	{
		return $this->_adapter;
	}
	public function setAdapter($adapter)
	{
		$this->_adapter = $adapter;
	}
	public function setAdapterOptions($options)
	{
		if (method_exists($this->_adapter, 'setOptions'))
			$this->_adapter->setOptions($options);
	}
	
	public function exists($response = null)
    {
		$user_id = (int)$this->_session->user_id;
		$user = User::find($user_id);
		return ($user instanceof User);
    }
    
    public function getIdentity()
    {
		return $this->_session->identity;
    }
    public function setIdentity($identity)
    {
		$this->_session->identity = $identity;
	}
	public function clearIdentity()
	{
		unset($this->_session->identity);
	}
	
	/** 
	 * Masquerading
	 */
	
	public function masqueradeAsUser($user_info)
    {
		if (!($user_info instanceof User))
			$user_info = User::getRepository()->findOneByUsername($user_info);
        
        $this->_session->masquerade_user_id = $user_info->id;
        $this->_masqueraded_user = $user;
    }

    public function endMasquerade()
    {
		unset($this->_session->masquerade_user_id);
        $this->_masqueraded_user = null;
    }
    
    public function getMasquerade()
    {
        return $this->_masqueraded_user;
    }

    public function isMasqueraded()
    {
		if (!$this->isLoggedIn())
		{
			$this->_masqueraded_user = FALSE;
			return NULL;
		}
		
        if ($this->_masqueraded_user === NULL)
        {
            if (!$this->_session->masquerade_user_id)
            {
				$this->_masqueraded_user = FALSE;
			}
			else
			{
				$mask_user_id = (int)$this->_session->masquerade_user_id;
				if ($mask_user_id != 0)
					$user = User::find($mask_user_id);
				
				if ($user instanceof User)
				{
					$this->_masqueraded_user = $user;
				}
				else
				{
					unset($this->_session->user_id);
					unset($this->_session->masquerade_user_id);
					
					$this->_masqueraded_user = FALSE;
				}
			}
        }
        
        return $this->_masqueraded_user;
    }
}