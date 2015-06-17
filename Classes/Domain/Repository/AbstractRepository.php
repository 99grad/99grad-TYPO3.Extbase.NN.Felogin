<?php
namespace Nng\Nnfelogin\Domain\Repository;


class AbstractRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * @var \Nng\Nnfelogin\Helper\AnyHelper
	 * @inject
	 */
	protected $anyHelper;
	
	/**
	* @var \Nng\Nnfelogin\Utilities\SettingsUtility
	* @inject
	*/
	protected $settingsUtility;

	/**
	* @var \Nng\Nnfelogin\Utilities\CacheUtility
	* @inject
	*/
	protected $cacheUtility;
	

	public $settings;
	
	/**
	* @return void
	*/
	public function initializeObject() {
		$this->TS = $this->settingsUtility->getTsSetup();
		$this->settings = $this->settingsUtility->getMergedSettings();
	}

}