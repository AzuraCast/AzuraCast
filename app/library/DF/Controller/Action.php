<?php
namespace DF\Controller;

use \DF\Url;
use \DF\Flash;

class Action extends \Zend_Controller_Action
{
	public $config;
	public $module_config;
	public $auth;
	public $acl;
	public $test_mode;
	
    public function init()
    {
		$this->config = $this->view->config = \Zend_Registry::get('config');
		$this->module_config = $this->view->module_config = \Zend_Registry::get('module_config');
		$this->auth = $this->view->auth = \Zend_Registry::get('auth');
		$this->acl = $this->view->acl = \Zend_Registry::get('acl');
        $this->em = \Zend_Registry::get('em');
		
		$this->module_name = $this->_getModuleName();
		\Zend_Registry::set('module_name', $this->module_name);
		
		if (isset($this->module_config[$this->module_name]))
			$this->current_module_config = $this->module_config[$this->module_name];
			
		$this->test_mode = $this->isTestMode();
		
		if ($this->test_mode)
		{
			$front = $this->getFrontController();
			$front->setParam('noErrorHandler', true);
		}

        $this->preInit();

		$isAllowed = $this->permissions();
		
		if (!$isAllowed)
		{
			if (!\DF\Auth::isLoggedIn())
				throw new \DF\Exception\NotLoggedIn();
			else
				throw new \DF\Exception\PermissionDenied();
		}

        $this->postInit();
    }

    public function preInit() {}
    public function postInit() {}
	
	protected function _getModuleName()
	{
		$front = $this->getFrontController();  
		$request = $this->getRequest();  
		$module = $request->getModuleName();  
		
		if (!$module)
			$module = $front->getDispatcher()->getDefaultModule();
		
		return $module;
	}
	protected function _getControllerName()
	{
		return $this->getRequest()->getControllerName();
	}
	protected function _getActionName()
	{
		return $this->getRequest()->getActionName();
	}
	
	protected function _getMvcPath($appendPath = null)  
    {  
        if (!isset($this->_mvcPath)) {  
            $front   = $this->getFrontController();  
            $module  = $this->_getModuleName(); 
            $dirs    = $front->getControllerDirectory();  
              
            $this->_mvcPath = dirname($dirs[$module]);  
        }  
          
        if (null !== $appendPath) {  
            if (!is_string($appendPath)) {  
                throw new \Zend_Controller_Action_Exception();  
            }  
            return $this->_mvcPath . DIRECTORY_SEPARATOR . $appendPath;  
        }  
          
        return $this->_mvcPath;  
    } 
    
    public function permissions()
    {
        return true;
    }
	
	public function preDispatch()
	{
		$is_ajax = ($this->isAjax());
		$this->view->is_ajax = $is_ajax;
		
		if ($is_ajax)
	        \Zend_Layout::getMvcInstance()->disableLayout();
	}
	
	public function postDispatch()
	{
		$db_log = $this->em->getConnection()->getConfiguration()->getSQLLogger();
		
		if ($db_log instanceof \Doctrine\DBAL\Logging\SQLLogger)
		{
			if (DF_APPLICATION_ENV != "production" || $this->acl->isAllowed('administer all'))
			{
				$logger = new \Zend_Log(new \Zend_Log_Writer_Firebug);
				
				foreach((array)$db_log->queries as $query)
					$logger->info(round($query['executionMS'], 5).': '.$query['sql']);
			}
		}
	}
	
	public function isAjax()
	{
		return $this->_request->isXmlHttpRequest();
	}
	
	public function renderJson($json_data)
	{
		$this->doNotRender();
		$this->getResponse()->appendBody(\Zend_Json::encode($json_data));
	}
	
    public function getResource($resource_name)
    {
        if( isset($this->getInvokeArg('bootstrap')->getContainer()->{$resource_name}) )
            return $this->getInvokeArg('bootstrap')->getContainer()->{$resource_name};
        else
            return false;
    }
	
	
	public function flash($message, $level = Flash::INFO)
	{
		return $this->alert($message, $level);
	}
	public function alert($message, $level = Flash::INFO)
	{
		$func_args = func_get_args();
		call_user_func_array('\DF\Flash::addMessage', $func_args);
	}

    protected function redirect($url, array $options = null)
    {
        if( $options === null )
        {
            $options = array(
                'prependBase' => false,
                'exit' => true,
            );
        }
        
        $this->_helper->_redirector->gotoUrl($url, $options);
    }
	protected function redirectHome()
	{
		$this->redirect(Url::route(array(
			'module'		=> 'default',
			'controller'	=> 'index',
			'action'		=> 'index',
		)));
	}
	protected function redirectHere()
	{
		$this->redirect(Url::route(array(), NULL, FALSE));
	}
	
	protected function redirectToRoute()
	{
		$func_args = func_get_args();
		$routed_url = call_user_func_array('\DF\Url::route', $func_args);
		$this->redirect($routed_url);
	}
	
	protected function redirectFromHere()
	{
		$func_args = func_get_args();
		$routed_url = Url::route($func_args[0], NULL, FALSE);
		$this->redirect($routed_url);
	}

    protected function doNotRender()
    {
        $this->_helper->viewRenderer->setNoRender();
        \Zend_Layout::getMvcInstance()->disableLayout();
    }
    
    protected function isTestMode()
    {
		return (defined('DF_TEST_MODE') && DF_TEST_MODE == true);
    }
    
    protected function renderForm($form, $mode = 'edit', $form_title = NULL)
    {
		$this->_helper->viewRenderer->setNoRender();
		
		$body = '';
		
		if ($form_title)
		{
			// Show visible title.
			$body .= '<h2>'.$form_title.'</h2>';
		}
		
		if ($this->isAjax())
		{
			// Show title if otherwise not set.
			if (!$form_title)
			{
				$title = current($this->view->headTitle()->getIterator());
				$body .= '<h2 class="page_title">'.$title.'</h2>';
			}
			
			// Proper action routing.
			if (!$form->getAction())
			{
				$form->setAction(\DF\Url::current());
			}
		}
		
		// Form render mode.
		if ($mode == 'edit')
			$body .= $form->render();
		else
			$body .= $form->renderView();
		
		$this->getResponse()->appendBody($body);
    }
	
	/**
	 * Referrer storage
	 */
    protected function storeReferrer($namespace = 'default', $loose = true)
    {
        $session = new \Zend_Session_Namespace('df_referrer_'.strtolower($namespace));

        if( !isset($session->url) || ($loose && isset($session->url) && Url::current() != Url::referrer()) )
            $session->url = Url::referrer();
    }

    protected function getStoredReferrer($namespace = 'default')
    {
        $session = new \Zend_Session_Namespace('df_referrer_' . strtolower($namespace));
        return $session->url;
    }

    protected function clearStoredReferrer($namespace = 'default')
    {
        $session = new \Zend_Session_Namespace('df_referrer_' . strtolower($namespace));
        unset($session->url);
    }

    protected function redirectToStoredReferrer($namespace = 'default', $default_url = false)
    {
        $referrer = $this->getStoredReferrer($namespace);
        $this->clearStoredReferrer($namespace);

        if( trim($referrer) == '' )
            if( $default_url )
                $referrer = $default_url;
            else
                $referrer = Url::baseUrl();

        $this->redirect($referrer);
    }

    protected function redirectToReferrer($default = false)
    {
        if( !$default )
            $default = Url::baseUrl();
        
        $this->redirect(Url::referrer($default));
    }
	
	/**
	 * CSRF security token validation
	 */
    protected function validateToken($token, $redirect = true)
    {
        if(!\DF\Csrf::validateToken($token) )
        {
            Flash::addMessage("Invalid Security Token");
            
            if( $redirect )
                $this->redirectToReferrer();
            else
                return false;
        }
        else
        {
            return true;
        }
    }
    
    /**
     * Parameter Handling
     */
    
    protected function convertGetToParam($params)
    {
		if (!is_array($params))
			$params = array($params);
		
		$url_changes = array();
		foreach($params as $param)
		{
			if (isset($_GET[$param]))
				$url_changes[$param] = $_GET[$param];
		}
		
		if (count($url_changes) > 0)
			$this->redirectFromHere($url_changes);
    }
}