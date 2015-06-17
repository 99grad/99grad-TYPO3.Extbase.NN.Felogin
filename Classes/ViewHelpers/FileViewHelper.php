<?php

namespace Nng\Nnfelogin\ViewHelpers;


class FileViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
     * @var \Nng\Nnfelogin\Helper\FileHelper
     * @inject
     */
	protected $fileHelper;
    
   
    /**
     * @param string $func
     * @param string $filename
     */

	public function render( $func = 'type', $filename = null ) {
		
		if (!$filename) return;
		
		switch ($func) {
			case 'suffix':
				return $this->fileHelper->suffix($filename);
			case 'type':
				return $this->fileHelper->filetype($filename);
			case 'basename':
				return basename($filename);
			case 'filesize':
				if (!file_exists($filename)) return 0;
				return filesize($filename);
			case 'exists':
				return $this->fileHelper->exists($filename);
		}
		
	}
    
    
}
?>