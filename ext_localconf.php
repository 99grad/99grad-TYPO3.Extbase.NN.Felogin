<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Nng.' . $_EXTKEY,
	'Pi1',
	array(
		'Main' => 'showLoginForm, showForgotPasswordForm, showChangePasswordForm',
		
	),
	// non-cacheable actions
	array(
		'Main' => 'showLoginForm, showForgotPasswordForm, showChangePasswordForm',
		
	)
);


// -----------------------------------------------------------------------------------
// eID Dispatcher

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['nnfelogin'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nnfelogin').'Classes/Dispatcher/EidDispatcher.php';
