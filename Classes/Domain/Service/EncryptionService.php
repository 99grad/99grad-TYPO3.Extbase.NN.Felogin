<?php

namespace Nng\Nnfelogin\Domain\Service;

class EncryptionService {
				
	
	/**
	 * @var \Nng\Nnfelogin\Helper\AnyHelper
	 * @inject
	 */
	protected $anyHelper;

	
	private $currentEncryptionKey;
	
	
	/**
	* initializeObject
	*
	* @return void
	*/
	
	public function initializeObject(){
		$this->uniqueId = $this->anyHelper->getUniqueIdForContentObject();
		$ses = $GLOBALS['TSFE']->fe_user->getKey( 'ses', 'nnfelogin' );
		$this->currentEncryptionKey = $ses['encKeys'][$this->uniqueId];
	}
	

	/**
	* Erzeugt einen neuen Encryption Key für dieses PlugIn und speichert ihn in der Session
	*
	* @return string
	*/
	
	function createEncryptionKey () {
	
		$val = substr(preg_replace('/[^0-9]/i', '', md5(mktime().uniqid())), 10);
		$key = $this->uniqueId;
		
		$arr = (array) $GLOBALS['TSFE']->fe_user->getKey( 'ses', 'nnfelogin' );
		$arr['encKeys'][$key] = $val;
		
		$GLOBALS['TSFE']->fe_user->setKey( 'ses', "nnfelogin", $arr );
		return $val;
	}


	/**
	* Holt den letzten generierten Encryption Key
	*
	* @return string
	*/

	function getEncryptionKey () {
		return $this->currentEncryptionKey;
	}


	/**
	* Prüft, ob der übergebene Key identisch ist mit dem Key aus der Session
	*
	* @return boolean
	*/

	function validateEncryptionKey ( $key ) {
		return $this->currentEncryptionKey == $key;
	}
	

	/**
	* Mehrere Variablen (meistens $_GP-Vars) decodieren
	*
	* @return boolean
	*/
	
	function decryptArray ( &$arr, $fields = null ) {
		
		if (!$arr) return $arr;
		$fields = (array) $fields;
		foreach ($fields as $field) {
			if (isset($arr[$field])) $arr[$field] = $this->decryptPassword( $arr[$field] );
		}
		return $arr;
	}
	
	
	/**
	* Decodiert ein Passwort anhand des aktuellen Keys aus der Session
	*
	* @return string
	*/
	
	function decryptPassword ( $str ) {
		
		if (!$str) return '';
		$ords = $this->unistr_to_ords($str);
		$arr = array();
		$key = $this->currentEncryptionKey;
		for ($i = 0; $i < count($ords); $i++) {
			$arr[] = ($ords[$i] - ord(mb_substr($key, $i%strlen($key), 1)) + 48 );// ( String.fromCharCode(opt.key.charCodeAt(i%opt.key.length)*1 + str.charCodeAt(i) - 48) );
		}
		return $this->ords_to_unistr($arr);
	}
	
	function ords_to_unistr($ords, $encoding = 'UTF-8'){
		$str = '';
		for($i = 0; $i < sizeof($ords); $i++){
			$v = $ords[$i];
			$str .= pack("N",$v);
		}
		$str = mb_convert_encoding($str,$encoding,"UCS-4BE");
		return $str;			
	}
	
	function unistr_to_ords($str, $encoding = 'UTF-8'){		
		$str = mb_convert_encoding($str,"UCS-4BE",$encoding);
		$ords = array();

		for($i = 0; $i < mb_strlen($str,"UCS-4BE"); $i++){		
			$s2 = mb_substr($str,$i,1,"UCS-4BE");					
			$val = unpack("N",$s2);			
			$ords[] = $val[1];				
		}		
		return $ords;
	}

	
}
	