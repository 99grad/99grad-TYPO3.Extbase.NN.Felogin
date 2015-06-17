<?php

namespace Nng\Nnfelogin\ViewHelpers;

class InArrayViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	* Chooses the value of an Array by the given String
	* 
	* @param $arr string or array
	* @param string $key1 The Key who is in the Array
	* @return string The resulting value of the Array
	* @author Markus Rodler
	*/

    public function render( $arr, $needle = null ) {
    	if (!is_array($arr)) $arr = explode(',', $arr);
    	foreach ($arr as $k=>$v) $arr[$k] = trim($v);
    	if (in_array($needle, $arr)) return true;
		return '';
		
    }
}


?>