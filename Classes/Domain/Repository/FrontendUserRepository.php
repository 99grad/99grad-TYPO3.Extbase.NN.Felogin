<?php
namespace Nng\Nnfelogin\Domain\Repository;


class FrontendUserRepository extends \Nng\Nnfelogin\Domain\Repository\AbstractRepository {

	public function initializeObject () {
	
		parent::initializeObject();
		$querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
		if ($storagePid = $this->settings['storagePid']) {
			$querySettings->setStoragePageIds(
				\TYPO3\CMS\Extbase\Utility\ArrayUtility::trimExplode(',', $storagePid)
			);
		} else {
			$querySettings->setRespectStoragePage( false );
		}
		$this->setDefaultQuerySettings($querySettings);
	
	}
	
	
	public function findByUsernameOrEmail () {
		$data = parent::findAll();
		return $data;
	}
	
	public function findOneByEmail ( $email = null ) {
		$email = trim($email);
		if (!$email) return false;
		return parent::findOneByEmail( $email );
	}
	
	/*
	function getAllUserUidsWithEntry () {
		if ($cache = $this->cacheUtility->getRamCache( __METHOD__ )) return $cache;		
		$data = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			"fe_user", 
			'tx_nnfilearchive_domain_model_item', 
			'1=1 '.$this->settingsUtility->getEnableFields( 'tx_nnfilearchive_domain_model_item' ), 
			'fe_user', 
			'', 
			'', 
			'fe_user'
		);
		$data = array_keys($data);
		$this->cacheUtility->setRamCache( $data, __METHOD__ );
		return $data;
	}
	*/
}