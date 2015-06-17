<?php

namespace Nng\Nnfelogin\ViewHelpers;

 
class ContentElementViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {


	/**
	* @var \Nng\Nnfelogin\Helper\AnyHelper
	* @inject
	*/
	protected $anyHelper;
	
	
    private $cObj;
    
    /**
     *
     * Render
     *
     * Renders a content element
     *
     * @param int $uid
     * @param array $data
     * return string
     */
     
    public function render($uid, $data = null) {
    
    	$this->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tslib_cObj');
    	
    	if (!is_numeric($uid)) {
			$uidArr = $this->anyHelper->getContentElementByFluidUid( $uid );
			if (count($uidArr) > 1) {
				$str = "Content-Element mit Bezeichner <b>'{$uid}'</b> ist mehrfach vorhanden:";
				$arr = array();
				foreach ($uidArr as $row) {
					$arr[] = "uid: {$row['uid']} [pid: {$row['pid']}] ";
				}
				return $str.join(', ', $arr);
			}
			$uid = array_pop( $uidArr );
			$uid = $uid['uid'];
    	}
	
		$conf = array(
			'tables' => 'tt_content',
			'source' => $uid,
			'dontCheckPid' => 1
		);
		$html = $this->cObj->RECORDS($conf);
        	
		
        if ($data) {
        	$html = $this->anyHelper->renderTemplateSource($html, $data);
        }
        return $html ? $html : "Keine Content-Element mit uid '{$uid}' gefunden. Evtl. muss uid im View angepasst werden!";
    }

    
}

?>