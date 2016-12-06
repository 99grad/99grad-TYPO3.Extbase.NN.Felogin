<?php

namespace Nng\Nnfelogin\Utilities;

class CacheUtility extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {


	protected $configurationManager;
	protected $request;
	
	protected $cObj;
	protected $settings;
	protected $configuration;
	
	public function __construct () {
	
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->configurationManager = $objectManager->get('TYPO3\CMS\Extbase\Configuration\ConfigurationManager');
		$this->request = $objectManager->get('TYPO3\CMS\Extbase\Mvc\Request');
		
		$this->cObj = $this->configurationManager->getContentObject();
		$this->configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$this->settings = $this->configuration['settings'];
		
		$this->gpVars = $this->request->getArguments();	

	}
	
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Caching Functions
	// 
	// http://buzz.typo3.org/people/steffen-kamper/article/using-cache-in-extensions/
	//
	// funcName 	->	Functionsname, der einen Cache Wert holen oder setzen möchte (für eindeutige ID)
	// hashVars		->	String oder Array, der Parameter für Cache bestimmt (z.B. GET-Vars oder TS-Setup)
	// data			->	String / HTML-Code / Array der Daten, die gespeichert werden sollen
	
	
	public function getCache ( $funcName = null, $hashVars = null ) {
		$data = \TYPO3\CMS\Frontend\Page\PageRepository::getHash( $this->getCacheHash($hashVars, $funcName) );
		if (!$data) return false;
		$data = unserialize($data);
		return isset($data['__string']) ? $data['__string'] : $data;
	}


	public function setCache ( $data, $funcName = null, $hashVars = null ) {
		$tmp = $data;
		if (!is_array($data)) $tmp = array('__string'=>$data);

		// Um Mitternacht endet der Cache		
		$lifetime = mktime(23,59,59)+1-time();
		
		\TYPO3\CMS\Frontend\Page\PageRepository::storeHash( $this->getCacheHash($hashVars, $funcName), serialize($tmp), $this->getCacheIdentifier($funcName), $lifetime );
		return $data;
	}


	public function setRamCache ( $data, $funcName = null, $hashVars = null ) {
		if (!$hashVars) $hashVars = array($funcName);
		if (!$data) $data = 'null';
		$GLOBALS[$this->getCacheHash($hashVars, $funcName)] = $data;
		return $data;
	}

	public function getRamCache ( $funcName = null, $hashVars = null ) {
		if (!$hashVars) $hashVars = array($funcName);
		return isset($GLOBALS[$this->getCacheHash($hashVars, $funcName)]) ? $GLOBALS[$this->getCacheHash($hashVars, $funcName)] : false;
	}


	private function getCacheHash ( $hashVars = null, $funcName = null ) {
		if (!$funcName) $funcName = $this->viewType;
		if (!$hashVars) {
			$hashVars = array(
				'pid' 		=> $GLOBALS['TSFE']->id, 
				'lang' 		=> $GLOBALS['TSFE']->sys_language_uid,
				'uid' 		=> $this->cObj->data['uid'],
				'ffValues'	=> join(array_values($this->settings['flexform'])),
				'date'		=> date('Y-m-d')
			);
			$hashVars = array_merge($hashVars, $this->gpVars);
		}
		if (is_array($hashVars)) $hashVars = join('|',array_values($hashVars)).join('|', array_keys($hashVars));
		return '_'.md5($this->extKey.'-'.$hashVars.'-'.$funcName);		
	}

	private function getCacheIdentifier( $funcName = null ) {
		if (!$funcName) $funcName = $this->viewtype;
		return $this->extKey.'-'.$funcName;
	}
	
}

?>