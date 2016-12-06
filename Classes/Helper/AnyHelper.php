<?php

namespace Nng\Nnfelogin\Helper;

class AnyHelper {

	
	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;
	
	
	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
	 * @inject
	 */
	protected $configurationManager;
	
	
	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 * @inject
	 */
	protected $cObj;
	
	
	
	public function initializeObject () {
		$this->cObj = $this->configurationManager->getContentObject();	
	}
	
	
	
	/* 
	 *	Old-School piBase-Object erzeugen um alte Plugins zu initialisieren
	 *
	 */
	 
	function piBaseObj () {
		$piObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Plugin\AbstractPlugin');
		$piObj->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
		return $piObj;
		/*
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
		$configurationManager = $objectManager->get('\TYPO3\CMS\Extbase\Configuration\ConfigurationManager');
		$piObj = $objectManager->create('\TYPO3\CMS\Frontend\Plugin\AbstractPlugin');
		$piObj->cObj = $configurationManager->getContentObject();
		*/
		return $piObj;
	}
	
	
	function setPageTitle ( $titleStr ) {
		$GLOBALS['TSFE']->page['title'] = $titleStr;
		$GLOBALS['TSFE']->indexedDocTitle = $titleStr;
	}
	
	
	/* --------------------------------------------------------------- 
		Eindeutige ID für dieses Content-Object holen
	*/
	
	public function getUniqueIdForContentObject () {

		// PlugIn wurde ganz normal im Backend gesetzt? Dann uid von tt_content zurückgeben
		if (!isset($this->cObj->data['doktype'] )) return $this->cObj->data['uid'];
		
		// Plugin wurde per TypoScript eingebunden
		$num = ++$GLOBALS['__nnfelogin_uniqueId'];
		return 'p'.$this->cObj->data['uid'].'i'.$num;
	}
	
	
	/* --------------------------------------------------------------- 
		Schlüssel erzeugen zur Validierung einer Abfrage
	*/
	
	function createKeyForUid ( $uid ) {
		$extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nnfelogin']);
		if ($extConfig['encodingKey'] == '99grad') die('<h1>nnfelogin</h1><p>Bitte aendere die "Salting Key" in der Extension-Konfiguration auf etwas anderes als "99grad" (im Extension-Manager auf die Extension klicken)</p>');
		return substr(strrev(md5($uid.$extConfig['encodingKey'])), 0, 8);
	}
	
	function validateKeyForUid ( $uid, $key ) {
		return self::createKeyForUid( $uid ) == $key;
	}
	
	/* --------------------------------------------------------------- */

	
	function trimExplode ( $del, $str ) {
		if (!trim($str)) return array();
		$str = explode($del, $str);
		foreach ($str as $k=>$v) $str[$k] = trim($v);
		return $str;
	}
	
	function trimExplodeArray ( $arr ) {
		if (!$arr) return array();
		if (!is_array($arr)) $arr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $arr);
		$final = array();
		foreach ($arr as $n) {
			if (trim($n)) $final[] = $n;
		}
		return $final;
	}
		
	/* ==================================================================================================
		Versand der Mail über SWIFT-Mailer

		$params	.fromEmail		=>	Absender E-Mail
				.fromName		=>	Absender Name
				.toEmail		=>	Array mit Empfängern der E-Mail (auch kommasep. Liste möglich)
				.subject		=>	Betreff der E-Mail
				.attachments	=>	Array mit vollständigem Pfad der Anhänge
				.inlineImages	=>	Array mit Pfad der Bilder, die als Inline-Image gesendet werden sollen
				.html			=>	HTML-Part der Mail
				.plaintext		=>	Plain-Text Mail
				
		http://docs.typo3.org/TYPO3/CoreApiReference/ApiOverview/Mail/Index.html
		http://swiftmailer.org/docs/messages.html
	*/


	function send_email ( $params, $conf = null ) {

		$mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_mail_message'); // TYPO3\\CMS\\Core\\Mail\\MailMessage
		
		$mail->setFrom(array( $params['fromEmail'] => $params['fromName'] ));
		
		$recipients = $this->trimExplodeArray($params['toEmail']);
		$mail->setTo($recipients);
		
		$mail->setSubject($params['subject']);
		
		$attachments = $this->trimExplodeArray($params['attachments']);
		foreach ($attachments as $path) {
			$attachment = \Swift_Attachment::fromPath($path);
			$mail->attach($attachment);
		}
		
		$inlineImages = $this->trimExplodeArray($params['inlineImages']);
		$html = $params['html'];
		$plaintext = $params['plaintext'] ? $params['plaintext'] : $this->html2plaintext($params['html']);
		
		
		foreach ($inlineImages as $img) {
			$cid = $mail->embed(\Swift_Image::fromPath($img));
			$html = str_replace( array(
					'http://'.$params['domain'].'/'.$img,
					'https://'.$params['domain'].'/'.$img,
					\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$img,
					$img
				), $cid, $html);
	}
	
		if ($html) {
			$mail->setBody($html, 'text/html');
			$mail->addPart($plaintext, 'text/plain');	
		} else {
			$mail->setBody($plaintext, 'text/plain');
		}
	
		$mail->setReturnPath( $params['returnPath_email'] );

		$sent = $mail->send();
		if (!$sent) {
			$fp = fopen('mail_error.log', 'a');
			$to = join(',', $recipients);
			fputs($fp, date('d.m.Y H:i:s')." {$to}\n\n\n");
			fclose($fp);
			
			$helpMail = $this->params['errorMail_email'];
			$mail->setReturnPath( $helpMail );
			$mail->setTo( $helpMail );
			$mail->setSubject('Mailversand: FEHLER!');
			$mail->send();
		}
	}
	
	
	/* --------------------------------------------------------------- */

	
	public function generateFallbackPathsForPartials ( $tmplPath, $num = 4 ) {
		$paths = array();			
		$tmplPathParts = explode('/', $tmplPath);
		for ($i = 1; $i < $num; $i++) $paths[] = join('/', array_slice($tmplPathParts,0,-$i)).'/Partials/';
		$paths[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nnfelogin').'Resources/Private/Partials/';
		return $paths;
	}
	
	function renderTemplate ( $path, $vars, $flattenVars = false, $pathPartials = null, $doubleRender = false ) {
		
		if (!$path || !file_exists($path)) return '';

		$view = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$view->setTemplatePathAndFilename($path);
		
		$view->setPartialRootPaths( $pathPartials ? (is_array($pathPartials) ? $pathPartials : array($pathPartials)) : $this->generateFallbackPathsForPartials($path) );

		if ($flattenVars) {
			if ($vars) $view->assignMultiple($vars);
		} else {
			$view->assign('data', $vars);
		}
		
		$html = $view->render();
		
		if ($doubleRender) {
			$view->setTemplateSource($html);
			$html = $view->render();
		}
		
		return $html;
	}
	
	
	function renderTemplateSource ( $template, $vars, $pathPartials = null ) {
		
		if (!$template) return '';
		
		if (strpos($template, '{namespace') === false) {
			$template = '{namespace VH=Nng\Nnfelogin\ViewHelpers}'.$template;
		}
		
		$view = $this->objectManager->get('TYPO3\CMS\Fluid\View\StandaloneView');		
		$view->setTemplateSource($template);
		$view->setPartialRootPaths( $pathPartials ? array($pathPartials) : $this->generateFallbackPathsForPartials($path) );
		$view->assignMultiple( $vars );
		$html = $view->render();
		
		return $html;
	}
	
	
	public function renderTypoScript ( $type, $setup ) {
		return $this->cObj->cObjGetSingle( $type, $setup );
	}

	/* --------------------------------------------------------------- 
		NOTICE, ERROR, WARNING, OK
		$this->anyHelper->addFlashMessage('so,so', 'ja ja');

	*/
	
	function addFlashMessage ( $title = '', $text = '', $type = 'OK') {
		
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$controllerContext = $objectManager->get('TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext');
		$controllerContext->getFlashMessageQueue()->enqueue(
			$objectManager->get( 'TYPO3\CMS\Core\Messaging\FlashMessage', $text, $title, constant('TYPO3\CMS\Core\Messaging\FlashMessage::'.$type), true )
		);
	}
	
	function renderFlashMessages () {
		
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$controllerContext = $objectManager->get('TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext');
		if (count($controllerContext->getFlashMessageQueue()->getAllMessages())) {
			return $this->renderTemplate('typo3conf/ext/nnfelogin/Resources/Private/Templates/FlashMessages.html', array() );
		}
		return '';
	}
	
	/* --------------------------------------------------------------- */
	

	function getSuffix ( $file ) {
		if (!$file) return false;
		return strtolower(pathinfo($file, PATHINFO_EXTENSION));
	}
	
	function cloneArray( $arr ) {
		$ret = array();
		foreach ($arr as $k=>$v) $ret[$k] = $v;
		return $ret;
	}
	
	function cleanIntList ( $str='', $returnArray = null ) {
		$is_arr = is_array($str);
		if (trim($str) == '') return (($returnArray == null && !$is_arr) || $returnArr === false) ? '' : array();
		if ($is_arr) $str = join(',', $str);
		$str = $GLOBALS['TYPO3_DB']->cleanIntList( $str );
		if (($returnArray == null && !$is_arr) || $returnArr === false) return $str;
		return explode(',', $str);
	}
			
	function get_obj_by_attribute ( &$data, $key, $val = false, $retArr = false ) {
		$ref = array();
		foreach ($data as $k=>$v) {
			if ($val === false) {
				if ($retArr === true) {
					if (!is_array($ref[$v[$key]])) $ref[$v[$key]] = array();
					$ref[$v[$key]][] = &$data[$k];
				} else {
					$ref[$v[$key]] = &$data[$k];
				}
			} else {
				$ref[$v[$key]] = $val === true ? $v : $v[$val];
			}
		}
		return $ref;
	}
	
	function html2plaintext ( $html ) {
		return strip_tags($html);
	}
	
	// --------------------------------------------------------------------------------------------------------------------
	// Weiterleitung über http-Header location:...
	
	static function httpRedirect ( $pid = null, $vars = array(), $varsPrefix = null ) {
		unset($vars['id']);
		if (!$pid) $pid = $GLOBALS['TSFE']->id;		
		$pi = self::piBaseObj();
		if ($varsPrefix) {
			$tmp = array($varsPrefix=>array());
			foreach ($vars as $k=>$v) $tmp[$varsPrefix][$k] = $v;
			$vars = $tmp;
		}

		$link = $pi->pi_getPageLink($pid, '', $vars);
		$link = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($link); 
		header('Location: '.$link); 
		exit(); 
	}

	
	static function sendToBrowser ( $path, $download = false, $filename = null ) {

		$isFile = realpath( $path ) !== '';
		if ($filename) $suffix = pathinfo($filename, PATHINFO_EXTENSION);
		if (!$suffix && $isFile) $suffix = pathinfo($path, PATHINFO_EXTENSION);
		if (!$suffix) $suffix = 'txt';
		if (!$isFile && !$filename) $filename = 'download';		
		if (!$filename) $filename = $isFile ? basename($path) : 'download.'.$suffix;

		ob_end_clean();
		if ($download) header('Content-disposition: attachment; filename='.$filename);
		header('Content-type: application/'.$suffix);
		
		if ($isFile) {
			//header('Content-Length: ' . filesize($path));
			readfile( $path );
		} else {
			//header('Content-Length: ' . strlen($path));
			echo $path;
		}
		die();
	}
	
}

?>