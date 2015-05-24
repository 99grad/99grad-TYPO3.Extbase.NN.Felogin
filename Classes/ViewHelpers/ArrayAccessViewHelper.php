<?php

namespace Nng\Nnfilearchive\ViewHelpers;

class ArrayAccessViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	* Chooses the value of an Array by the given String
	* 
	* @param array $arr The associative Array with the key/value-pairs to search in
	* @param string $key1 The Key who is in the Array
	* @param string $key2 The Key who is in the Array	
	* @param string $key3 The Key who is in the Array	
	* @param string $fallback Fallback in case return value is empty	
	* @return string The resulting value of the Array
	* @author Markus Rodler
	*/

    public function render( $arr, $key1, $key2 = null, $key3 = null, $fallback = null ) {
    	$ref = is_object($arr) ? $arr->$key1 : (isset($arr[$key1]) ? $arr[$key1] : $fallback);
		if ($key2) $ref = is_object($ref) ? $ref->$key2 : $ref[$key2];
		if ($key3) $ref = is_object($ref) ? $ref->$key3 : $ref[$key3];
		return $ref;
		
    }
}


?>