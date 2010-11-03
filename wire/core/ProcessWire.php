<?php

/**
 * ProcessWire API Bootstrap
 *
 * Initializes all the ProcessWire classes and prepares them for API use
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

define("PROCESSWIRE_CORE_PATH", dirname(__FILE__) . '/'); 

spl_autoload_register('ProcessWireClassLoader'); 

require(PROCESSWIRE_CORE_PATH . "Interfaces.php"); 
require(PROCESSWIRE_CORE_PATH . "Exceptions.php"); 
require(PROCESSWIRE_CORE_PATH . "Functions.php"); 

/**
 * ProcessWire Bootstrap class
 *
 * Gets ProcessWire's API ready for use
 *
 */ 
class ProcessWire extends Wire {

	const versionMajor = 2; 
	const versionMinor = 0; 
	const versionRevision = 0; 

	/**
	 * Given a Config object, instantiates ProcessWire and it's API
 	 *
	 */ 
	public function __construct(Config $config) {
		$this->config($config); 
		$this->load($config);
	}

	/**
	 * Populate ProcessWire's configuration with runtime and optional variables
 	 *
	 * $param Config $config
 	 *
	 */
	protected function config(Config $config) {

		Wire::setFuel('config', $config); 

		ini_set("date.timezone", $config->timezone);

		if(!$config->templateExtension) $config->templateExtension = 'tpl';
		if(!$config->httpHost) {
			if(isset($_SERVER['HTTP_HOST']) && $host = $_SERVER['HTTP_HOST']) {
				if(!preg_match('/^[-a-zA-Z0-9.:]+$/D', $host)) $host = '';
				$config->httpHost = $host;
			}
		}

		$config->https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on'; 
		$config->ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
		$config->version = self::versionMajor . "." . self::versionMinor . "." . self::versionRevision; 

	}

	/**
	 * Load's ProcessWire using the supplied Config and populates all API fuel
 	 *
	 * $param Config $config
 	 *
	 */
	public function load(Config $config) {
	
		Wire::setFuel('notices', new Notices()); 
		Wire::setFuel('sanitizer', new Sanitizer()); 
		Wire::setFuel('db', new Database($config->dbHost, $config->dbUser, $config->dbPass, $config->dbName)); 
		
		$modules = new Modules($config->paths->modules);
		$fieldtypes = new Fieldtypes();
		$fields = new Fields();
		$fieldgroups = new Fieldgroups();
		$templates = new Templates($fieldgroups, $config->paths->templates); 

		Wire::setFuel('modules', $modules); 
		Wire::setFuel('fieldtypes', $fieldtypes); 
		Wire::setFuel('fields', $fields); 
		Wire::setFuel('fieldgroups', $fieldgroups); 
		Wire::setFuel('templates', $templates); 

		Wire::setFuel('permissions', new Permissions()); 
		Wire::setFuel('roles', new Roles()); 
		Wire::setFuel('pages', new Pages(), true);
		Wire::setFuel('pagesRoles', new PagesRoles()); 
		Wire::setFuel('users', new Users()); 
		Wire::setFuel('user', Wire::getFuel('users')->getCurrentUser()); 
		Wire::setFuel('session', new Session()); 
		Wire::setFuel('input', new WireInput()); 

		$fieldtypes->init();
		$fields->init();
		$fieldgroups->init();
		$templates->init();
		$modules->init();

	}
}

/**
 * Handles dynamic loading of classes as registered with spl_autoload_register
 *
 */
function ProcessWireClassLoader($className) {

	if($className[0] == 'W' && $className != 'Wire' && strpos($className, 'Wire') === 0) {
		$className = substr($className, 4); 
	}

	$file = PROCESSWIRE_CORE_PATH . "$className.php"; 

	if(is_file($file)) {
		require_once($file); 

	} else if($modules = Wire::getFuel('modules')) {
		$modules->includeModule($className);

	} else die($className); 
}


