<?php

namespace Nng\Nnfelogin\ViewHelpers;

class FlashMessagesViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	* @var \Nng\Nnfelogin\Helper\AnyHelper
	* @inject
	*/
	protected $anyHelper;
	
	
   public function render() {    
		return $this->anyHelper->renderFlashMessages();
	}
	
}
?>


