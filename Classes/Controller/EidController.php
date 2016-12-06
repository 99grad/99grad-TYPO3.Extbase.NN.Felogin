<?php

namespace Nng\Nnfelogin\Controller;

class EidController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
	
	
	/**
	* @var \Nng\Nnfelogin\Helper\AnyHelper
	* @inject
	*/
	protected $anyHelper;
	
	
	/**
	* Initializes the current action
	
	* @return void
	*/
	protected function initializeAction() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
	}
	
     /* 
     *	Wird von EidDispatcher.php aufgerufen, wenn in URL &eID=nnfilearchive übergeben wurde
     *
     *  index.php?&eID=nnfilearchive&action=download&uid=3
     *
     */
	function processRequestAction () {
	
		$_GP = $this->request->getArguments();

		$action = $_GP['action'];		
		$uid = (int) $_GP['uid'];
			
		switch ($action) {
			case 'download':
				break;
			default:
				die("eID Action {$action} unknown.");
		}
		die();
		
	}

	function validateAction () {
		$_GP = $this->request->getArguments();
		$uid = (int) $_GP['uid'];
		$key = $_GP['key'];
		if (!$this->anyHelper->validateKeyForUid($uid, $key)) die("Validierung fehlgeschlagen.");
		return true;
	}

}


?>