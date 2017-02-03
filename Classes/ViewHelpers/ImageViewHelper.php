<?php
namespace Nng\Nnfelogin\ViewHelpers;

class ImageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper {

 	/**
	 * @var \Nng\Nnfelogin\Utilities\SettingsUtility
	 * @inject
	 */
	protected $settingsUtility;
	

	/**
	 * Resizes a given image (if required) and renders the respective img tag
	 *
	 * @see http://typo3.org/documentation/document-library/references/doc_core_tsref/4.2.0/view/1/5/#id4164427
	 * @param string $src a path to a file, a combined FAL identifier or an uid (integer). If $treatIdAsReference is set, the integer is considered the uid of the sys_file_reference record. If you already got a FAL object, consider using the $image parameter instead
	 * @param string $width width of the image. This can be a numeric value representing the fixed width of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.
	 * @param string $height height of the image. This can be a numeric value representing the fixed height of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.
	 * @param integer $minWidth minimum width of the image
	 * @param integer $minHeight minimum height of the image
	 * @param integer $maxWidth maximum width of the image
	 * @param integer $maxHeight maximum height of the image
	 * @param boolean $treatIdAsReference given src argument is a sys_file_reference record
	 * @param FileInterface|AbstractFileFolder $image a FAL object
	 * @param mixed $crop
	 * @param mixed $absolute
	 * @param boolean $embed add image to embed in email
	 *
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 * @return string Rendered tag
	 */
	public function render($src = NULL, $width = NULL, $height = NULL, $minWidth = NULL, $minHeight = NULL, $maxWidth = NULL, $maxHeight = NULL, $treatIdAsReference = FALSE, $image = NULL, $crop = null, $absolute = false, $embed = false) {
		
		$tag = parent::render($src, $width, $height, $minWidth, $minHeight, $maxWidth, $maxHeight, $treatIdAsReference, $image);
		if (!$tag) return '';
		
		if (!$embed) $tag = str_replace('src="', 'src="'.$this->settingsUtility->getBaseURL(), $tag );		

		preg_match( '@src="([^"]+)"@' , $tag, $match );
		$uri = array_pop($match);
		
		if (!$GLOBALS['email_inlineimages']) $GLOBALS['email_inlineimages'] = array();
		if ($embed) $GLOBALS['email_inlineimages'][] = $uri;

		return parent::render($src, $width, $height, $minWidth, $minHeight, $maxWidth, $maxHeight, $treatIdAsReference, $image);
	}
}
