<?php
namespace Nng\Nnfelogin\Controller;


/**
 * MainController
 */
class MainController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \Nng\Nnfelogin\Helper\AnyHelper
	 * @inject
	 */
	protected $anyHelper;

	/**
	* @var \Nng\Nnfelogin\Utilities\SettingsUtility
	* @inject
	*/
	protected $settingsUtility;
	
	/**
	* @var \Nng\Nnfelogin\Domain\Repository\FrontendUserRepository
	* @inject
	*/
	protected $frontendUserRepository;

	/**
	* @var \Nng\Nnfelogin\Domain\Service\FrontendUserService
	* @inject
	*/
	protected $frontendUserService;
	
	/**
	* @var \Nng\Nnfelogin\Domain\Service\EncryptionService
	* @inject
	*/
	protected $encryptionService;
	
	
	/**
	* SignalSlot Dispatcher
	*
	* @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	* @inject
	*/
	protected $signalSlotDispatcher;

	
	
	protected $viewVars;
	
	protected $pathPartials;
	
	
	/**
	* Initializes the current action
	* @return void
	*/
	protected function initializeAction() {
		
//		$GLOBALS['TSFE']->set_no_cache();
		if (!$this->request) $this->request = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Request');
		if (!$this->view) $this->view = $this->objectManager->get('TYPO3\CMS\Fluid\View\StandaloneView');
				
		$this->cObj = $this->configurationManager->getContentObject();
		$this->TS = $this->settingsUtility->getTsSetup();
		$this->settings = $this->settingsUtility->getMergedSettings();

		// So können mehrere PlugIns auf einer Seite platziert werden, ohne einander gegenseitig zu beeinflussen		
		$this->uniqueId = $this->anyHelper->getUniqueIdForContentObject();
		$this->_GPprefix = 'nnfelogin['.$this->uniqueId.']';

		$this->_GPall = (array) \TYPO3\CMS\Extbase\Utility\ArrayUtility::arrayMergeRecursiveOverrule(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET(), \TYPO3\CMS\Core\Utility\GeneralUtility::_POST());				
		$this->_GPvars = (array) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('nnfelogin');
		$this->_GP = (array) $this->_GPvars[$this->uniqueId];

		$this->encryptionKey = $this->encryptionService->getEncryptionKey();		
		$this->encryptionService->decryptArray( $this->_GP, array('pw', 'new_pw', 'new_pw_repeat', 'pw_confirm') );

		// Besondere Variablen mit übernehmen
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
			$this->_GP, array(
				'redirect_url'	=> \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl($this->_GPall['redirect_url'] ? $this->_GPall['redirect_url'] : $this->_GP['redirect_url'])
			)
		);
				
	}


	/**
	* Initializes the current view
	* @return void
	*/
	protected function initializeView() {

		if (!$this->request) $this->request = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Request');
		if (!$this->view) $this->view = $this->objectManager->get('TYPO3\CMS\Fluid\View\StandaloneView');
		
		$predefTemplate = $this->settings['predefTemplate'];
		if (!$predefTemplate) $predefTemplate = 'default';
		
		$templateSettings = $this->TS['predef.']["{$predefTemplate}."];
		$viewPath = $templateSettings['template'].$this->request->getControllerName().'/';
		$template = $viewPath.ucfirst(substr($this->actionMethodName, 0, -6)).'.html';

		if (!file_exists($template)) echo "nnfelogin: Template {$template} nicht gefunden.";
		$this->view->setTemplatePathAndFilename( $template );
		
		$partials = array($viewPath.'/Partials/');
		if ($templateSettings['partials.']) $partials += $templateSettings['partials.'];
		if ($templateSettings['partials']) $partials[] = $templateSettings['partials.'];
		$this->view->setPartialRootPaths($partials);
		$this->pathPartials = $partials;
		
		// JS-Dateien einbinden
		foreach ($templateSettings['includeJS.'] as $k=>$v) {
			$GLOBALS['TSFE']->additionalHeaderData['nnfelogin'.md5($v)] = '<script type="text/javascript" src="'.$v.'"></script>';
		}

		foreach ($templateSettings['includeCSS.'] as $k=>$v) {
			$GLOBALS['TSFE']->additionalHeaderData['nnfelogin'.md5($v)] = '<link rel="stylesheet" type="text/css" media="all" href="'.$v.'" />';
		}
		
		$this->viewVars = array(
			'settings'			=> $this->settings,
			'_GP'				=> $this->_GP,
			'redirect_url'		=> $this->_GP['redirect_url'],
			'cObjData'			=> $this->cObj->data,
			'uniqueId'			=> $this->uniqueId,
			'_GPprefix'			=> $this->_GPprefix,
			'feUser'			=> $GLOBALS['TSFE']->fe_user->user,
			'baseUrl'			=> $GLOBALS['TSFE']->baseUrl,
			'encryptionKey'		=> $this->encryptionService->createEncryptionKey()
		);
		
		$this->view->assignMultiple( $this->viewVars );
		
	}
	
	
	public function getTemplatePath () {
		$predefTemplate = $this->settings['predefTemplate'];
		if (!$predefTemplate) $predefTemplate = 'default';

		$templateSettings = $this->TS['predef.']["{$predefTemplate}."];

		return $templateSettings['template'].$this->request->getControllerName().'/';
	}
	
	
	/**
	 * action showFormAction
	 * Formular zeigen zum An / Abmelden eines fe_users
	 *
	 * @return void
	 */
	public function showLoginFormAction() {

		$_GP = $this->_GP;
		$view = array();

//		print_r($this->_GP);
//		return '';
		
//		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump( $GLOBALS['TSFE']->fe_user->user );

		// ---------------------------------------------
		// Benutzer wurde eingeloggt. Redirect prüfen?

		if ($_GP['loginSuccess'] || ($this->settings['forceLoginRedirect'] && $GLOBALS['TSFE']->fe_user->user)) {
			if ($pid = $this->_GP['redirect_url']) $this->anyHelper->httpRedirect($pid);
			if ($pid = $this->settings['loginRedirectPid']) $this->anyHelper->httpRedirect( $pid );
		}

		// ---------------------------------------------
		// Benutzer hat E-Mail und Passwort eingegeben
		
		if ($_GP['validate']) {
			
			// ist der encryptionKey identisch mit dem Key, der in der Session gespeichert wurde?
			if ($this->encryptionService->validateEncryptionKey($this->_GP['encryptionKey'])) {
				
				$user = $this->frontendUserService->validate( $_GP['email'], $_GP['pw'] );
				
				if (is_object($user)) {
//					\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($user);
					
					if (!count($user->getUsergroup())) {
						$view['errors'] = array('noGroup'=>1);
					} else {
						$this->frontendUserService->startFeUserSession( $user );
						$this->anyHelper->httpRedirect( null, array('loginSuccess'=>1, 'redirect_url'=>$this->_GP['redirect_url'] ), $this->_GPprefix );
					}
					
				} else {
					
					$view['errors'] = array('pw'=>1, 'errorCode'=>$user);
					$view['user'] = array(
						'pw'	=> '', 
						'email'	=> substr(strip_tags($_GP['email']), 0, 60)
					);
				}
				
			} else {
				$view['errors'] = array('encryptionKey'=>1);
			}
		}
		
		// ---------------------------------------------
		// Benutzer will sich ausloggen
		
		if ($_GP['logout']) {
			$this->frontendUserService->endFeUserSession();
			
			if ($pid = $this->settings['logoutRedirectPid']) $this->anyHelper->httpRedirect( $pid );
			$this->anyHelper->httpRedirect();

		}
				
		$this->view->assignMultiple( $view );
		
	}
	
	

	/**
	*  action resetPasswordDispatcher
	*  "Passwort vergessen"-Funktion als Signal/Slot bereitstellen
	*
	*  $this->signalSlotDispatcher->dispatch('TYPO3\EsweApi\Domain\Service\UserService', 'emitResetPassword', array('email'=>'david@99grad.de'));
	*
	*  Signal
	*  @param array $email
	*  @return void
	*/

	public function resetPasswordDispatcher( $email ){

        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
        $logger->info('NNFeLogin MainController:resetPasswordDispatcher', array('email' => json_encode($email) ));

		if (!trim($email)) return;
		if (!($user = $this->frontendUserRepository->findOneByEmail( $email ))) return;
//		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump( $user );

		$this->initializeAction();
		$this->initializeView();
		
		$tmplPath = 'typo3conf/ext/nnfelogin/Resources/Private/Templates/Default/Main/';
		$linkParams = $this->frontendUserService->generateForgotPasswordParams( $user->getUid() );

		$html = $this->anyHelper->renderTemplate( 
			$tmplPath . 'EmailResetPassword.html', 
			array_merge($this->viewVars, array('feUser'=>$user), $linkParams), 
			true,
			$this->pathPartials
		);

		$this->anyHelper->send_email(array_merge($this->settings['forgotPassword'], array(
			'toEmail'	=> $user->getEmail(),
			'html'		=> $html
		)));
		
	}

	
	
	/**
	*  action forgotPasswordFormAction
	*  Formular für "Passwort vergessen"-Funktion
	*
	*/

	public function showForgotPasswordFormAction () {
	
		$_GP = array_merge( (array) $this->_GP, (array) $_GET );
		
		$email = $_GP['email'];
		$view = array('data' => $_GP, 'errors'=>array());
		$view['mode'] = 'form';
		$tmplPath = $this->getTemplatePath();
		
		// Benutzer hat E-Mail (oder Kunden-Nummer / User-Name) eingegeben um Passwort zu ändern
		if ($_GP['validate']) {

			$user = $this->frontendUserRepository->findByCustomField( $_GP['email'], $this->settings['usernameFields'] );
//			\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump( $user );

			if (!is_object($user)) {

				// Keinerlei Benutzer gefunden
				$view['errors']['email'] = 1;

			} else if (count($user) > 1) {
				// Benutzer konnte nicht eindeutig identifiziert werden, z.B. weil die E-Mail doppelt verwendet wurde
				$view['errors']['not_unique'] = 1;
							
			} else {
			
				// Alles klar – jetzt Mail versenden
				$view['mode'] = 'mailsent';
				$user = $user->getFirst();
				$linkParams = $this->frontendUserService->generateForgotPasswordParams( $user->getUid() );
				
				$html = $this->anyHelper->renderTemplate( 
					$tmplPath . 'EmailResetPassword.html', 
					array_merge($this->viewVars, array('feUser'=>$user), $linkParams), 
					true,
					$this->pathPartials
				);
				

				$this->anyHelper->send_email(array_merge($this->settings['forgotPassword'], array(
					'toEmail'	=> $user->getEmail(),
					'html'		=> $html
				)));
				
			}
		
		}
		
		
		// Benutzer hat auf den Link aus der E-Mail geklickt
		if ($_GP['forgothash']) {
			$user = $this->frontendUserRepository->findOneByUid( intval($_GP['user']) );
			if ($user && $this->frontendUserService->validateForgotPasswordHash( $_GP['user'], $_GP['forgothash'] ) ) {
				$view['mode'] = 'pwform';
				$view['forgothash'] = strip_tags($_GP['forgothash']);
				$view['user'] = strip_tags($_GP['user']);
				$view['feUser'] = $user;
			}
		}
		
		
		// Benutzer hat Formular für neues Passwort abgesendet
		if ($_GP['changepw'] && $_GP['forgothash']) {
			$user = $this->frontendUserRepository->findOneByUid( $_GP['user'], true );
			if ($user && $this->frontendUserService->validateForgotPasswordHash( $_GP['user'], $_GP['forgothash'] )) {
				
				if (strlen($_GP['pw']) < 6) {
					$view['errors']['pw_too_short'] = 1;
				} else if ($_GP['pw'] == $_GP['pw_confirm']) {
					$view['mode'] = 'pwchanged';
					
					$this->frontendUserService->updateUserPassword( $user->getUid(), $_GP['pw'] );
					//$this->frontendUserService->clearForgotHash( $user->getUid() );
					$view['feUser'] = $user;	
				} else {
					$view['mode'] = 'pwform';
					$view['errors']['pw'] = 1;
				}
			}
		}
		
		$this->view->assignMultiple( $view );
	}
	
	
	/**
	*  action showChangePasswordFormAction
	*  Formular für "Passwort ändern"-Funktion
	*
	*/
	
	public function showChangePasswordFormAction () {
	
		if (!$user = $this->frontendUserService->getCurrentUser()) {
			if ($pid = $this->settings['loginFormPid']) {
				$this->anyHelper->httpRedirect( $pid, array('redirect_url'=>$GLOBALS['TSFE']->id) );
			}
			return 'Bitte loggen Sie sich ein, um diese Funktion zu nutzen.';
		}
		
		$_GP = $this->_GP;
		
		$old_pw = $_GP['pw'];
		$new_pw = $_GP['new_pw'];
		$new_pw_repeat = $_GP['new_pw_repeat'];
		
		
		$view = array('data' => $_GP, 'errors'=>array());
		$view['mode'] = 'change_pwform';
		
		if ($_GP['update_pw']) {
			if (!$old_pw) $view['errors']['pw'] = 1;
			if ($new_pw != $new_pw_repeat) $view['errors']['new_pw_not_identical'] = 1;
			if (!$new_pw)  $view['errors']['new_pw'] = 1;
			
			// Alles ok
			if (!$view['errors']) {
				if (strlen($new_pw) < 5) {
					$view['errors']['pw_too_short'] = 1;
				} else {
					$user = $this->frontendUserService->validate($user['email'] ? $user['email'] : $user['username'], $old_pw);
					if (is_object($user)) {
			
						$this->frontendUserService->updateUserPassword($user->getUid(), $new_pw);
						$view['mode'] = 'password_changed';
						if ($pid = $this->settings['changePasswordRedirectPid']) {
							$this->anyHelper->httpRedirect( $pid );
						}
					} else {
						$view['errors']['pw'] = 1;
					}
				}
			}
		}
				
		$this->view->assignMultiple( $view );
		
	}
	
	
}