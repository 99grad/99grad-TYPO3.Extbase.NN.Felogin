<?php

namespace Nng\Nnfelogin\ViewHelpers;

class CustomIfViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

/**
     * Escapes oder Decodes special characters with their escaped counterparts as needed.
     *
     * @param string $value
     * @param string $type The type, one of html, entities, url
     * @param string $encoding
     * @return string the altered string.
     * @api
     */
    public function render($value = NULL, $type = 'html', $encoding = NULL) {
 
        if (empty($value)) {
            $value = $this->renderChildren();
        }
 
        switch ($type) {
            case 'html_decode':
                return \TYPO3\CMS\Core\Utility\GeneralUtility::htmlspecialchars_decode($value);
                break;
            default:
                return parent::render($value, $type, $encoding);
                break;
        }
    }
    
}

?>


