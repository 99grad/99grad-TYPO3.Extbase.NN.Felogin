<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$_EXTKEY,
	'Pi1',
	'NN Frontend Login'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'NN Frontend Login');

$pluginSignature = str_replace('_','',$_EXTKEY).'_pi1';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform.xml'); 


if (TYPO3_MODE == 'BE')	{
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['Nng\Nnfelogin\Wizicons\AddContentElementWizicon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Classes/Wizicons/AddContentElementWizicon.php';
}
