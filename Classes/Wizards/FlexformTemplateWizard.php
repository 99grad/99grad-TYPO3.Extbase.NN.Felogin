<?php
namespace Nng\Nnfelogin\Wizards;


class FlexformTemplateWizard {

	/**
	* @var \Nng\Nnfelogin\Utilities\SettingsUtility
	* @inject
	*/
	protected $settingsUtility;

	/**
    * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
    */
	protected $objectManager;
	
	
	function __construct () {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->settingsUtility = $this->objectManager->get("Nng\Nnfelogin\Utilities\SettingsUtility");
	}
	
	
	function user_insertPredefTemplates( $config, $a = null ) {
		
		$ts = $this->settingsUtility->getTsSetup();

		if (!$ts['predef.']) {
			$config['items'] = array( array('Kein TS gefunden - Template-Vorlagen können per plugin.tx_nnfelogin.predef definiert werden', '') );
			return $config;
		}
		
		foreach ($ts['predef.'] as $k=>$v) {
			$label = $v['label'];
			$label .= ' · '.str_replace( 'typo3conf/ext/nnfelogin/Resources/Private/Templates/', '', $v['template'] );
			$config['items'] = array_merge( $config['items'], array( array($label, substr($k,0,-1), '') ) );
		}
		
		return $config;
	}
	

}

?>