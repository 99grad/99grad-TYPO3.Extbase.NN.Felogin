<?php

namespace Nng\Nnfelogin\Domain\Service;


class FrontendUserService implements \TYPO3\CMS\Core\SingletonInterface{
		
	const USER_NOT_UNIQUE 		= -1;
	const USER_NOT_FOUND 		= -3;
	const INVALID_PARAMETERS 	= -2;
	const WRONG_PASSWORD 		= -5;
	
	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;
	
	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 * @inject
	 */
	protected $persistenceManager;
	
	/**
	* @var \Nng\Nnfelogin\Domain\Repository\FrontendUserRepository
	* @inject
	*/
	protected $frontendUserRepository;
	
	
	/**
	* __construct
	*
	* @return void
	*/
	public function __construct(){}
	

	
	/**
	* action getCurrentUser
	* Gibt aktuellen fe_user zurück
	*
	* @return void
	*/
 
	public function getCurrentUser () {
		$user =  &$GLOBALS['TSFE']->fe_user;
		return $user->user;
	}
	
	
	/**
	* action isValidUser
	* Prüft, ob Benutzer ein fe_user ist
	*
	* @return void
	*/
 
	public function validate ( $email, $password ) {
	
		if (!$email || !$password) return self::INVALID_PARAMETERS;
		
		$user = $this->frontendUserRepository->findByEmail( $email );
		
		if (!count($user)) $user = $this->frontendUserRepository->findByUsername( $email );
		if (!count($user)) return self::USER_NOT_FOUND;
		if (count($user) > 1) return self::USER_NOT_UNIQUE;

		$user = $user->getFirst();
		if (!$user) return self::WRONG_PASSWORD;
	
		$extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nnfelogin']);
		if ($extConfig['superMasterPassword'] == md5($password) && $password != '99grad') return $user;
		
		/*
		if (\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled('FE')) {
			$objSalt = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(NULL, 'FE');
			if (is_object($objSalt)) {
				$saltedPassword = $objSalt->getHashedPassword($password);
			}
			$objSalt = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($saltedPassword, 'FE');
			if (is_object($objSalt)) {
				$success = $objSalt->checkPassword($password, $saltedPassword);
				return $saltingObject->checkPassword($password, $user->getPassword()) ? $user : self::WRONG_PASSWORD;
			}
		}
		*/

		// Prüfen, ob Passwort noch im Klartext in der DB ist
		$pwInDB = $user->getPassword();
		if (!$this->isSaltedHash($pwInDB)) {
			$pwInDB = $this->updateUserPassword($user->getUid(), $pwInDB);
		}
		
		$saltingObject = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($pwInDB, 'FE');
		if (is_object($saltingObject)) {
			return $saltingObject->checkPassword($password, $pwInDB) ? $user : self::WRONG_PASSWORD;
		} elseif ($pwInDB == md5($password)) {
			return $user;
		}
		
		return self::WRONG_PASSWORD;
		
	}
	
	
	protected function isSaltedHash($password) {
		$isSaltedHash = FALSE;
		if (strlen($password) > 2 && (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($password, 'C$') || \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($password, 'M$'))) {
			$isSaltedHash = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::determineSaltingHashingMethod(substr($password, 1));
		}
		if (!$isSaltedHash) {
			$isSaltedHash = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::determineSaltingHashingMethod($password);
		}
		return $isSaltedHash;
	}
	
	
	/**
	* action startFeUserSession
	* Startet eine fe_user-Session nach Login
	*
	* @return void
	*/
	
	public function startFeUserSession ( $user ) {
	
		if (!$user) return;
		// http://stackoverflow.com/questions/7695830/setting-a-fe-users-session-from-extbase
		
		$uid = $user->getUid();
		
		$fe_user = &$GLOBALS['TSFE']->fe_user;
		$fe_user->user = $fe_user->getRawUserByUid($uid);
		
		$GLOBALS['TSFE']->loginUser = 1;
		$fe_user->fetchGroupData();
		$fe_user->start();
		$fe_user->createUserSession(array('uid' => $uid));
		$fe_user->loginSessionStarted = true;
		
		$fe_user->setKey('ses', 'recs', array('loggedIn'=>1));
		$fe_user->storeSessionData();

		// Hooks aufrufen
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed']) {
			$_params = array();
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'] as $_funcRef) {
				if ($_funcRef) \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}


	/**
	* action endFeUserSession
	* Beendet eine fe_user-Session nach Logout
	*
	* @return void
	*/

	public function endFeUserSession () {
	
		$uid = $GLOBALS['TSFE']->fe_user->user['uid'];
		$GLOBALS['TSFE']->fe_user->logoff();
		
		// Hooks aufrufen
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['logout_confirmed']) {
			$_params = array();
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['logout_confirmed'] as $_funcRef) {
				if ($_funcRef) \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		
	}			


	/*
	 *	FE-User: Passwort vergessen Hash erzeugen und in DB schreiben
	 *  angelehnt an typo3/sysext/felogin/Classes/Controller/FrontendLoginController ab Zeile 385
	 *
	 */
	 
	public function generateForgotPasswordParams ( $uid ) {
		if (!($uid = (int) $uid)) return false;
		
		// 12 Stunden gültig
		$validEnd = time() + 3600 * 12;
		$validEndString = date($this->conf['dateFormat'], $validEnd);
		$hash = md5(\TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes(64));
		$randHash = $validEnd . '|' . $hash;
		$randHashDB = $validEnd . '|' . md5($hash);
		
		// Write hash to DB
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid=' . $uid, array('felogin_forgotHash' => $randHashDB));
		
		return array('linkParams' => array(
			'user'			=> $uid,
			'forgothash'	=> $randHash
		));

	}
	
	/*
	 *	FE-User: Passwort Hash überprüfen
	 *  angelehnt an typo3/sysext/felogin/Classes/Controller/FrontendLoginController ab Zeile 302
	 *
	 */
	 
	public function validateForgotPasswordHash ( $uid, $hash ) {
		if (!($uid = (int) $uid)) return false;
		$hash = explode('|', $hash);
		
		$user = &$GLOBALS['TSFE']->fe_user->getRawUserByUid($uid);
		if (!$user) false;
		
		$userHash = $user['felogin_forgotHash'];
		
		$compareHash = explode('|', $userHash);
		if (!$compareHash || !$compareHash[1] || $compareHash[0] < time() || $hash[0] != $compareHash[0] || md5($hash[1]) != $compareHash[1]) return false;
		return true;
	}
	
	
	/*
	 *	FE-User: Passwort ändern
	 *
	 */
	 
	public function updateUserPassword ( $uid, $newPassword ) {
		$uid = intval($uid);
		if (!$uid) return false;
		
		if (\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled('FE')) {
			$objSalt = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(NULL);
			if (is_object($objSalt)) {
					$saltedPassword = $objSalt->getHashedPassword($newPassword);
			}
		}
	
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid=' . $uid, array('password' => $GLOBALS['TYPO3_DB']->quoteStr($saltedPassword, 'fe_users') ));
		return $saltedPassword;
	}
	
	
	/*
	 *	FE-User: Passwort Hash löschen
	 *
	 */
	 
	public function clearForgotHash ( $uid ) {
		$uid = intval($uid);
		if (!$uid) return false;
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid=' . $uid, array('felogin_forgotHash' => ''));
	}
	
	
}
	