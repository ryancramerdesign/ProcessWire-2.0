<?php

/**
 * ProcessWire Bootstrap
 *
 * This file may be used to bootstrap either the http web accessible
 * version, or the command line client version of ProcessWire. 
 *
 * Note: if you happen to change any directory references in here, please
 * do so after you have installed the site, as the installer is not informed
 * of any changes made in this file. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

define("PROCESSWIRE", 200); 

/**
 * Build the ProcessWire configuration
 *
 * @return Config
 *
 */
function ProcessWireBootConfig() {

	/*
	 * Define installation paths and urls
	 *
	 */
	$rootPath = dirname(__FILE__);
	if(DIRECTORY_SEPARATOR != '/') $rootPath = str_replace(DIRECTORY_SEPARATOR, '/', $rootPath); 

	if(isset($_SERVER['HTTP_HOST'])) {
		// when serving pages from a web server
		$rootURL = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\") . '/';
	} else {
		// when included from another app or command line script
		$rootURL = '/';
	}

	$wireDir = 'wire';
	$coreDir = "$wireDir/core";
	$siteDir = "site";
	$assetsDir = "$siteDir/assets";
	$adminTplDir = 'templates-admin';

	/*
	 * Setup ProcessWire class autoloads
	 *
	 */
	require("$rootPath/$coreDir/ProcessWire.php");

	/*
	 * Setup configuration data and default paths/urls
	 *
	 */
	$config = new Config();
	$config->urls = new Paths($rootURL); 
	$config->urls->modules = "$wireDir/modules/";
	$config->urls->siteModules = "$siteDir/modules/";
	$config->urls->core = "$coreDir/"; 
	$config->urls->assets = "$assetsDir/";
	$config->urls->cache = "$assetsDir/cache/";
	$config->urls->logs = "$assetsDir/logs/";
	$config->urls->files = "$assetsDir/files/";
	$config->urls->tmp = "$assetsDir/tmp/";
	$config->urls->templates = "$siteDir/templates/";
	$config->urls->adminTemplates = is_dir("$siteDir/$adminTplDir") ? "$siteDir/$adminTplDir/" : "$wireDir/$adminTplDir/";
	$config->paths = clone $config->urls; 
	$config->paths->root = $rootPath . '/';
	$config->paths->sessions = $config->paths->assets . "sessions/";

	/*
	 * Styles and scripts are CSS and JS files, as used by the admin application.
	 * But reserved here if needed by other apps and templates.
	 *
	 */
	$config->styles = new FilenameArray();
	$config->scripts = new FilenameArray();

	/*
	 * Include user-specified configuration options
	 *
	 */
	$configFile = "$rootPath/$siteDir/config.php";
	$configFileDev = "$rootPath/$siteDir/config-dev.php";
	@include(is_file($configFileDev) ? $configFileDev : $configFile); 

	/*
	 * If debug mode is on then echo all errors, if not then disable all error reporting
	 *
	 */
	if($config->debug) {
		error_reporting(E_ALL | E_STRICT); 
		ini_set('display_errors', 1);
	} else {
		error_reporting(0);
		ini_set('display_errors', 0);
	}

	/*
	 * If PW2 is not installed, go to the installer
	 *
	 */
	if(!$config->dbName && is_file("./install.php") && strtolower($_SERVER['REQUEST_URI']) == strtolower($rootURL)) {
		require("./install.php");
		exit(0);
	}

	/*
	 * Prepare any PHP ini_set options
	 *
	 */
	session_name($config->sessionName); 
	ini_set('session.use_cookies', true); 
	ini_set('session.use_only_cookies', 1);
	ini_set("session.gc_maxlifetime", $config->sessionExpireSeconds); 
	ini_set("session.save_path", rtrim($config->paths->sessions, '/')); 

	return $config; 
}

/*
 * If you include ProcessWire's index.php from another script, or from a
 * command-line script, the $wire variable is your connection to the API.
 *
 */
$wire = null;

/**
 * Build the ProcessWire configuration
 *
 */
$config = ProcessWireBootConfig(); 

/*
 * Load and execute ProcessWire
 *
 */
try { 
	/*
	 * Bootstrap ProcessWire's core and make the API available with $wire
	 *
	 */
	$wire = new ProcessWire($config); 

	/*
	 * Store the admin URL in the configuration
	 *
	 */
	if($config->adminRootPageID) $config->urls->admin = $wire->pages->get($config->adminRootPageID)->url; 

	/* 
	 * If we're not being called from another shell script or PHP page, then run the PageView process
	 *
	 */
	if(isset($_SERVER['HTTP_HOST']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
		$controller = new ProcessController(); 
		$controller->setProcessName("ProcessPageView"); 
		echo $controller->execute();

	} else {
		/*
		 * Some other script included this for non-http use or access to the API without 
		 * rendering any pages at this time. The $wire var may be used to access 
		 * the API after including this file. 
		 *
		 */
	}

} catch(Exception $e) {

	/*
	 * Formulate error message and send to the error handler
	 *
	 */

	$errorMessage = "Exception: " . $e->getMessage() . " (in " . $e->getFile() . " line " . $e->getLine() . ")";
	if($config->debug || ($wire && $wire->user && $wire->user->isSuperuser())) $errorMessage .= "\n\n" . $e->getTraceAsString();
	trigger_error($errorMessage, E_USER_ERROR); 
}

