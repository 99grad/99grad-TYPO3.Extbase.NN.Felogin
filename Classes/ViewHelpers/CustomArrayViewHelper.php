<?php

namespace Nng\Nnfelogin\ViewHelpers;

class CustomArrayViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	* @var \Nng\Nnfelogin\Helper\AnyHelper
	* @inject
	*/
	protected $anyHelper;
	
	
 	/**
     * Will generate an array with the given path and value.
     *
     * Example:
     * <f:link.action arguments="{VH:customArray( path: 'searchParameter.equipment.{facetIterator.index}', value: '{facetOption.value}')}">
	 *
     * @param  string $path The path in dot notation E.g. "searchParameter.equipment{aVariable}"
     * @param  mixed $value The value to set.
     *
     * @return array The generated array.
     */

   public function render( $path, $value ) {
   		$array = array();
        $array = \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath( $array, $path, $value );
		return $array;
	}
}

?>


