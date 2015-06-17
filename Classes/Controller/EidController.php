<?php

namespace Nng\Nnfilearchive\Controller;

class EidController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	
	/**
	 * @var \Nng\Nnfilearchive\Controller\ItemController
	 * @inject
	 */
	protected $itemController;
	
	
	/**
	* @var \Nng\Nnfilearchive\Helper\AnyHelper
	* @inject
	*/
	protected $anyHelper;
	
	
	/**
	* Initializes the current action
	
	* @return void
	*/
	protected function initializeAction() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->memberController = $this->objectManager->create('\Nng\Nnfilearchive\Controller\ItemController');
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
				$this->memberController->downloadFile( $uid );
				break;
			case 'xxxxx':
				$this->validateAction();
				$this->memberController->doSomething( $uid );
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