<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}


$temporaryColumns = array (
	'pwchanged' => array(
		'exclude' => 1,
		'l10n_mode' => 'mergeIfNotBlank',
		'label' => 'Letzte Passwort-Änderung',
		'config' => [
			'type' => 'input',
			'size' => 16,
			'max' => 20,
			'eval' => 'datetime',
			'default' => 0,
		]
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'fe_users',
	$temporaryColumns
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'fe_users',
	'pwchanged',
	'',
	'after:password'
);