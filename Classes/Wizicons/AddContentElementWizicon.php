<?php

namespace Nng\Nnfelogin\Wizicons;


class AddContentElementWizicon {

		/**
         * Processing the wizard items array
         *
         * @param array $wizardItems The wizard items
         * @return array Modified array with wizard items
         */
        function proc($wizardItems)     {
                $wizardItems['plugins_tx_nnfelogin'] = array(
                        'icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nnfelogin') . 'Resources/Public/Icons/wizicon.png',
                        'title' => 'NN Frontend Login',
                        'description' => 'Formular zur Einloggen über das Frontend.',
                        'params' => '&defVals[tt_content][CType]=list&&defVals[tt_content][list_type]=nnfelogin_pi1'
                );

                return $wizardItems;
        }
        
}

?>