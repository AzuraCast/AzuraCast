<?php
class Mlpma_ManageController extends \PVL\Controller\Action\Mlpma
{
	public function permissions()
	{
		return $this->acl->isAllowed('administer mlpma songs');
	}

	public function indexAction()
	{
		$this->redirectFromHere(array('controller' => 'index'));
	}

	public function importAction()
	{
		$pending_results = \PVL\MusicManager::checkPendingFolder();
		$this->view->output = $pending_results;
	}
}