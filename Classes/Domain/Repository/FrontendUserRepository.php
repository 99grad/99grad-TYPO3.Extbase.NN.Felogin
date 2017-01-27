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
	
	/**
	* action findByUsernameOrEmail
	* Findet einen Benutzer anhand seiner E-Mail oder Benutzernamens
	* 
	*  @param string $email
	*  @return void|array
	*/
	public function findByUsernameOrEmail () {
		$data = parent::findAll();
		return $data;
	}
	
	
	/**
	* action findOneByEmail
	* Findet einen Benutzer anhand seiner E-Mail
	* 
	*  @param string $email
	*  @return void|array
	*/
	public function findOneByEmail ( $email = null ) {
		$email = trim($email);
		if (!$email) return false;
		return parent::findOneByEmail( $email );
	}
	
	
	/**
	* action findByCustomField
	* Prüft, ob Benutzer unter freiem Kriterium existiert, z.B. einer eigenen Spalte für Kunden-Nummer
	* 
	*  @param string $email
	*  @param string|array $fields
	*  @return void|array
	*/
	public function findByCustomField ( $email = null, $fields = null ) {
		$email = trim($email);
		if (!$email) return false;

		$fields = $this->anyHelper->trimExplodeArray( $fields );
		$tcaColumns = $this->settingsUtility->getTCAColumns( 'fe_users' );
		if ($existingCols = array_intersect( $fields, array_keys($tcaColumns))) {
			foreach ($existingCols as $col) {
				$query = $this->createQuery();
				$query->matching( $query->equals( $col, $email ) );
				$data = $query->execute();
				if (count($data) > 0) return $data;
			}
		}
		return false;
	}
	
}