<?php
namespace PVL\Controller\Action;

use \Entity\ArchiveGenre;
use \Entity\ArchiveSong;

class Mlpma extends \DF\Controller\Action
{
	public function init()
	{
    parent::init();

		\Zend_Layout::getMvcInstance()->setLayout('mlpma');
	}

	public function preDispatch()
	{
		parent::preDispatch();
		\Zend_Layout::getMvcInstance()->enableLayout();

		$this->view->top_genres = ArchiveGenre::getTop(5);
	}
}