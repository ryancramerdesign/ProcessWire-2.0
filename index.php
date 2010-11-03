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

$wire = null;
$rootPath = dirname(__FILE__);
$rootURL = isset($_SERVER['HTTP_HOST']) ? substr($rootPath, strlen($_SERVER['DOCUMENT_ROOT'])) . '/' : '/';
$wireDir = 'wire';
$coreDir = "$wireDir/core";
$siteDir = "site";
$assetsDir = "$siteDir/assets";

// setup ProcessWire class autoloads
require("$rootPath/$coreDir/ProcessWire.php");

// setup configuration data and default paths/urls
$config = new Config();
$config->urls = new Paths($rootURL); 
$config->urls->modules = "$wireDir/modules/";
$config->urls->core = "$coreDir/"; 
$config->urls->assets = "$assetsDir/";
$config->urls->cache = "$assetsDir/cache/";
$config->urls->logs = "$assetsDir/logs/";
$config->urls->files = "$assetsDir/files/";
$config->urls->tmp = "$assetsDir/tmp/";
$config->urls->templates = "$siteDir/templates/";
$config->urls->adminTemplates = "$wireDir/templates-admin/";
$config->paths = clone $config->urls; 
$config->paths->root = $rootPath . '/';
$config->paths->sessions = $config->paths->assets . "sessions/";

// styles and scripts are CSS and JS files, as used by the admin application
// may be used by other apps and templates as needed too
$config->styles = new FilenameArray();
$config->scripts = new FilenameArray();

// include user-specified configuration options
require($rootPath . '/' . $siteDir . "/config.php"); 

// if debug mode is on then echo all errors, if not then disable all error reporting
if($config->debug) {
	error_reporting(E_ALL | E_STRICT); 
	ini_set('display_errors', 1);
} else {
	error_reporting(0);
	ini_set('display_errors', 0);
}

// If PW2 installed installed, go to the installer
if(!$config->dbName && is_file("./install.php") && $_SERVER['REQUEST_URI'] == $rootURL) {
	require("./install.php");
	exit(0);
}

// prepare session variables, kept here out in the open in case you need to change them
session_name($config->sessionName); 
ini_set('session.use_cookies', true); 
ini_set('session.use_only_cookies', 1);
ini_set("session.gc_maxlifetime", $config->sessionExpireSeconds); 
ini_set("session.save_path", $config->paths->sessions); 

// load and execute ProcessWire
try { 
	// bootstrap ProcessWire's core and make the API available
	$wire = new ProcessWire($config); 

	// store the admin URL in the configuration
	if($config->adminRootPageID) $config->urls->admin = $wire->pages->get($config->adminRootPageID)->url; 

	// if we're not being called from another shell script or PHP page, then run the PageView process
	if(isset($_SERVER['HTTP_HOST']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
		$controller = new ProcessController(); 
		$controller->setProcessName("ProcessPageView"); 
		echo $controller->execute();
	} else {
		// some other script included this for non-http use or access to the API without rendering any pages at this time. 
		// The global $wire var may be used to access the API after including this file. 
	}

} catch(Exception $e) {

	// display errors if debug mode is on or if superuser logged in 
	if($config->debug || ($wire && $wire->user && $wire->user->isSuperuser())) {
		if(isset($_SERVER['HTTP_HOST'])) echo "<pre>";
		echo "ProcessWire Exception: " . $e->getMessage() . "\n\n" . $e->getTraceAsString();
		echo "\n\nNote: This error message was shown because debug mode is on or you are logged in as a superuser.\n\n";
	} else {
		// public facing users just get a http 500 error
		header("HTTP/1.1 500 Internal Server Error"); 
		echo "Internal Server Error";
	}

	// log the exception
	$log = new FileLog($config->paths->logs . "errors.txt"); 
	$log->save(($wire && $wire->user ? $wire->user->name : 'unknown') . ': ' . ($wire && $wire->page ? $wire->page->path : '/?/') . ': ' . $e->getMessage()); 

}

