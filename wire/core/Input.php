<?php

/**
 * ProcessWire WireInputData and WireInput
 *
 * WireInputData and the WireInput class together form a simple 
 * front end to PHP's $_GET, $_POST, and $_COOKIE superglobals.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * WireInputData manages one of GET, POST, COOKIE, or whitelist
 * 
 * Vars retrieved from here will not have to consider magic_quotes.
 * No sanitization or filtering is done, other than disallowing multi-dimensional arrays in input. 
 *
 * WireInputData specifically manages one of: get, post, cookie or whitelist, whereas the Input class 
 * provides access to the 3 InputData instances.
 *
 * Each WireInputData is not instantiated unless specifically asked for. 
 *
 */
class WireInputData implements ArrayAccess, IteratorAggregate, Countable {

	protected $stripSlashes = false;
	protected $data = array();

	public function __construct(array $input = array()) {
		$this->stripSlashes = get_magic_quotes_gpc();
		$this->setArray($input); 
	}

	public function setArray(array $input) {
		foreach($input as $key => $value) $this->__set($key, $value); 
		return $this; 
	}

	public function getArray() {
		return $this->data; 
	}

	public function __set($key, $value) {
		if(is_string($value) && $this->stripSlashes) $value = stripslashes($value); 
		if(is_array($value)) $value = $this->cleanArray($value); 
		$this->data[$key] = $value; 
	}

	protected function cleanArray(array $a) {
		$clean = array();
		foreach($a as $key => $value) {
			if(is_array($value)) continue; // we only allow one dimensional arrays
			if(is_string($value) && $this->stripSlashes) $value = stripslashes($value); 
			$clean[$key] = $value; 
		}
		return $clean;	
	}

	public function setStripSlashes($stripSlashes) {
		$this->stripSlashes = $stripSlashes ? true : false; 
	}

	public function __get($key) {
		if($key == 'whitelist') return $this->whitelist; 
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function getIterator() {
		return new ArrayObject($this->data); 
	}

	public function offsetExists($key) {
		return isset($this->data[$key]); 
	}

	public function offsetGet($key) {
		return $this->__get($key); 
	}

	public function offsetSet($key, $value) {
		$this->__set($key, $value); 
	}

	public function offsetUnset($key) {
		unset($this->data[$key]); 
	}

	public function count() {
		return count($this->data); 
	}

	public function removeAll() {
		$this->data = array();
	}


}

/**
 * Manages the group of GET, POST, COOKIE and whitelist vars, each of which is a WireInputData object.
 *
 */
class WireInput {

	protected $getVars = null;
	protected $postVars = null;
	protected $cookieVars = null;
	protected $whitelist = null;

	/**
	 * Retrieve a GET value or all GET values
	 *
	 * @param blank|string 
	 * 	If populated, returns the value corresponding to the key or NULL if it doesn't exist.
	 *	If blank, returns reference to the WireDataInput containing all GET vars. 
	 * @return null|mixed|WireDataInput
	 *
	 */
	public function get($key = '') {
		if(is_null($this->getVars)) $this->getVars = new WireInputData($_GET); 
		return $key ? $this->getVars->get($key) : $this->getVars; 
	}

	/**
	 * Retrieve a POST value or all POST values
	 *
	 * @param blank|string 
	 *	If populated, returns the value corresponding to the key or NULL if it doesn't exist.
	 *	If blank, returns reference to the WireDataInput containing all POST vars. 
	 * @return null|mixed|WireDataInput
	 *
	 */
	public function post($key = '') {
		if(is_null($this->postVars)) $this->postVars = new WireInputData($_POST); 
		return $key ? $this->postVars->get($key) : $this->postVars; 
	}

	/**
	 * Retrieve a COOKIE value or all COOKIE values
	 *
	 * @param blank|string 
	 *	If populated, returns the value corresponding to the key or NULL if it doesn't exist.
	 *	If blank, returns reference to the WireDataInput containing all COOKIE vars. 
	 * @return null|mixed|WireDataInput
	 *
	 */
	public function cookie($key = '') {
		if(is_null($this->cookieVars)) $this->cookieVars = new WireInputData($_COOKIE); 
		return $key ? $this->cookieVars->get($key) : $this->cookieVars; 
	}

	/**
	 * Get or set a whitelist var
	 *	
	 * Whitelist vars are used by modules and templates and assumed to be clean.
	 * 
	 * The whitelist is a list of variables specifically set by the application as clean for use elsewhere in the application.
	 * Only the version returned from this method should be considered clean.
	 * This whitelist is not specifically used by ProcessWire unless you populate it from your templates or the API. 
	 *
	 * @param string $key 
	 * 	If $key is blank, it assumes you are asking to return the entire whitelist. 
	 *	If $key and $value are populated, it adds the value to the whitelist.
	 * 	If $key is an array, it adds all the values present in the array to the whitelist.
	 * 	If $value is ommited, it assumes you are asking for a value with $key, in which case it returns it. 
	 * @param mixed $value
	 * 	See explanation for the $key param
	 * @return null|mixed|WireDataInput
	 * 	See explanation for the $key param 
	 *
	 */
	public function whitelist($key = '', $value = null) {
		if(is_null($this->whitelist)) $this->whitelist = new WireInputData(); 
		if(!$key) return $this->whitelist; 
		if(is_array($key)) return $this->whitelist->setArray($key); 
		if(is_null($value)) return $this->whitelist->__get($key); 
		$this->whitelist->__set($key, $value); 
		return $this->whitelist; 
	}

	/**	
	 * Retrieve the get, post, cookie or whitelist vars using a direct reference, i.e. $input->cookie
	 *
	 */
	public function __get($key) {
		$value = null;
		$gpc = array('get', 'post', 'cookie', 'whitelist'); 
		if(in_array($key, $gpc)) {
			$value = $this->$key(); 
		}
		return $value; 
	}
}
