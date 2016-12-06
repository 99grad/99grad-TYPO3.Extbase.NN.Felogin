<?php

namespace Nng\Nnfelogin\Utilities;

class SettingsUtility extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {


	/**
	 * @var \TYPO3\CMS\Extbase\Service\TypoScriptService
	 * @inject
	 */
	protected $typoscriptService;
	
	/**
	 * @var \TYPO3\CMS\Extbase\Service\FlexFormService
	 * @inject
	 */
	protected $flexFormService;
	
	protected $configurationManager;
	protected $request;
	
	protected $cObj;
	protected $settings;
	protected $mergedSettings;
	
	protected $configuration;
	
	
	public function initializeObject () {
	
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->configurationManager = $objectManager->get('TYPO3\CMS\Extbase\Configuration\ConfigurationManager');
		$this->request = $objectManager->get('TYPO3\CMS\Extbase\Mvc\Request');
		
		$this->cObj = $this->configurationManager->getContentObject();
		$this->configuration = $this->configurationManager->getConfiguration( \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK );
		$this->settings = $this->configuration['settings'];
		
		$flexform = $this->settings['flexform'];
		$ts = $this->remove_ts_setup_dots($this->getTsSetup());
		$this->settings = $ts['settings'];
		$this->settings['flexform'] = $flexform;
		
		$this->mergedSettings = $this->merge_settings_with_flexform();
		
	}


	public function getConfiguration () {
		return $this->configuration;
	}
	
	
	public function getMergedSettings () {
		return $this->mergedSettings;
	}
	

	/**
	* Bestimmte, wiederkehrende Voreinstellungen für die Views holen, z.B. Dropdown mit Länderliste etc.
	*
	*/

	
	public function getDefaultSettingsForView () {
		$settings = self::getTsSetup( false );
		return $settings;
	}
	
	
	
	/**
	* Merge flexform values with settings - only if flexform-value is not empty
	*
	*/
	private function merge_settings_with_flexform () {

		if (!$this->settings) return array();
		$tmp = array_merge_recursive( $this->settings );
		if (!$this->settings['flexform']) return $tmp;
		
		self::mergeRecursiveWithOverrule( $tmp, $this->settings['flexform'] );
		return $tmp;
	}
	
	static public function mergeRecursiveWithOverrule(array &$original, array $overrule) {
		foreach (array_keys($overrule) as $key) {
			if (!isset($original[$key])) $original[$key] = $overrule[$key];
			if (is_array($original[$key])) {
				if (is_array($overrule[$key])) {
					self::mergeRecursiveWithOverrule($original[$key], $overrule[$key]);
				}
			} elseif (trim($overrule[$key])) {
				$original[$key] = $overrule[$key];
			}
		}
		reset($original);
	}

	
    /**
	*	Get TypoScript Setup for plugin (with "name."-Syntax) as array
	*
	*/
	public static function getTsSetup ( $pageUid = false, $plugin = 'tx_nnfelogin' ) {
		
		$cacheID = '__tsSetupCache_'.$pageUid.'_'.$plugin;
		
		if (TYPO3_MODE == 'FE') {
			if (!$plugin) return $GLOBALS['TSFE']->tmpl->setup['plugin.'];
			return $GLOBALS['TSFE']->tmpl->setup['plugin.']["{$plugin}."];
		}
		
		if ($GLOBALS[$cacheID]) return $GLOBALS[$cacheID];

		if (!$pageUid) $pageUid = (int) $GLOBALS['_REQUEST']['popViewId'];
		if (!$pageUid) $pageUid = (int) preg_replace( '/(.*)(id=)([0-9]*)(.*)/i', '\\3', $GLOBALS['_REQUEST']['returnUrl'] );
		if (!$pageUid) $pageUid = (int) preg_replace( '/(.*)(id=)([0-9]*)(.*)/i', '\\3', $GLOBALS['_POST']['returnUrl'] );
		if (!$pageUid) $pageUid = (int) preg_replace( '/(.*)(id=)([0-9]*)(.*)/i', '\\3', $GLOBALS['_GET']['returnUrl'] );
		if (!$pageUid) $pageUid = (int) $GLOBALS['TSFE']->id;
		if (!$pageUid) $pageUid = (int) $_GET['id'];

		$sysPageObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\PageRepository');
		$rootLine = $sysPageObj->getRootLine($pageUid);
		$TSObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\TypoScript\ExtendedTemplateService');
		$TSObj->tt_track = 0;
		$TSObj->init();
		$TSObj->runThroughTemplates($rootLine);
		$TSObj->generateConfig();

		$GLOBALS[$cacheID] = !$plugin ? $TSObj->setup['plugin.'] : $TSObj->setup['plugin.']["{$plugin}."];
		
		if (!$plugin) return $TSObj->setup['plugin.'];
		return $TSObj->setup['plugin.']["{$plugin}."];
		
	}
	
	
	/**
	*	Sehr krasse Funktion:
	*	Die uid einen tt_content-Elements wird übergeben (i.d.R. das Extension-PlugIn für diese Extension)
	*	Rückgabe: Das TypoScript für diese Extension mit überlagerten settings-Werten aus dem FlexForm
	*
	*/
	public function getSetupForPlugin ( $tt_content_uid ) {
		$data = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			"*", 'tt_content', 
			'uid='.intval($tt_content_uid).' '.$this->getEnableFields( 'tt_content' ),
			'', '', '', ''
		);
		
		if (!$data) return array();		
		$ts = $this->remove_ts_setup_dots($this->getTsSetup());
		$flexform = $this->flexFormService->convertFlexFormContentToArray($data['pi_flexform']);
		$this->mergeRecursiveWithOverrule( $ts['settings'], $flexform['settings']['flexform'] );	
		return $ts;
	}
	
	
	
	/**
	*	Aller TCA Felder holen für bestimmte Tabelle
	*
	*/
	public static function getTCAColumns ( $table = 'tx_nnfelogin_domain_model_item' ) {
		$cols = $GLOBALS['TCA'][$table]['columns'];
		foreach ($cols as $k=>$v) {
			$cols[\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($k)] = $v;
		}
		return $cols;
	}
	
	/**
	*	Label eines bestimmten TCA Feldes holen
	*
	*/
	public static function getTCALabel ( $column = '', $table = 'tx_nnfelogin_domain_model_item' ) {
		$tca = self::getTCAColumns( $table );
		$label = $tca[$column]['label'];
		if ($LL = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label)) return $LL;
		return $label;
	}
	
	
	public function getEnableFields ( $table ) {
		return $GLOBALS['TSFE']->sys_page->enableFields( $table, $GLOBALS['TSFE']->showHiddenRecords);
	}
	
	
	public function getExtConf ( $param = null, $ext = 'nnfelogin' ) {
		$extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$ext]);
		return $param ? $extConfig[$param] : $extConfig;
	}
	
	/* --------------------------------------------------------------- 
	
		Wandelt die "."-Arrays eines TypoScripts um, damit z.B.
		per JSON oder Fluid darauf zugegriffen werden kann.
		
		array(
			'demo' 	=> 'oups',
			'demo.'	=> array(
				'test' 	=> '1',
				'was'	=> '2'
			)
		)
		
		wird zu:
		
		array(
			'demo' => array(
				'__' 	=> 'oups'
				'test' 	=> '1',
				'was'	=> '2'
			)
		)
		
	*/
	
	function remove_ts_setup_dots ($ts) {
		
		return $this->typoscriptService->convertTypoScriptArrayToPlainArray($ts);
		
		foreach ($ts as $key=>$v) {
			if (substr($key,-1) == '.' && is_array($v)) {
				$v = self::remove_ts_setup_dots($v);
				$r = $ts[substr($key,0,-1)];
				$ts[substr($key,0,-1)] = $v;
				if ($r) $ts[substr($key,0,-1)]['_typoScriptNodeValue'] = $r;
				unset($ts[$key]);
			}
		}
		return $ts;
	}
	
	function add_ts_setup_dots () {
		return $this->typoscriptService->convertPlainArrayToTypoScriptArray($ts);
//		return $this->typoscriptService->
	}
	
	
	function parse_flexform ( $xml, $sheetOnly='sDEF', $langOnly='lDEF' ) {
		if (!$xml) return array();
		$arr = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($xml);
		$flat = array();
		foreach ($arr['data'] as $sheet=>$subArr) {
			foreach ($subArr as $k => $v) {
				$flat = \TYPO3\CMS\Extbase\Utility\ArrayUtility::arrayMergeRecursiveOverrule($flat, $v);
				//$flat[$k] = $v['vDEF'];
			}
		}
		
		foreach ($flat as $k=>$v) {
			$flat[$k] = $v['vDEF'];
		}
		
		return $flat;
	}
	
	
	// Macht aus "pages_36|Dateiarchiv" -> [36]
	
	function parse_pids ( $selRef ) {
		$selected_uids = array();
		if (!$selRef) return array();
		$itemArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$selRef,1);
		if (is_array($itemArray)) {
			foreach ($itemArray as $kk => $vv) {
				$cuid = explode('|',$vv);
				$cuid = explode('_', $cuid[0]);
				$selected_uids[] = array_pop( $cuid );
			}
		}
		return $selected_uids;
	}
	
}

?>