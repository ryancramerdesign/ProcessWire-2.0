<?php

/**
 * ProcessWire Modules
 *
 * Loads and manages all runtime modules for ProcessWire
 *
 * Note that when iterating, find(), or calling any other method that returns module(s), excepting get(), a ModulePlaceholder may be
 * returned rather than a real Module. ModulePlaceholders are used in instances when the module may or may not be needed at runtime
 * in order to save resources. As a result, anything iterating through these Modules should check to make sure it's not a ModulePlaceholder
 * before using it. If it's a ModulePlaceholder, then the real Module can be instantiated/retrieved by $modules->get($className).
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Modules extends WireArray {

	/**
	 * Flag indicating the module may have only one instance at runtime. 
	 *
	 */
	const flagsSingular = 1; 

	/**
	 * Flag indicating that the module should be instantiated at runtime, rather than when called upon. 
	 *
	 */
	const flagsAutoload = 2; 

	/**
	 * Array of modules that are not currently installed, indexed by className => filename
	 *
	 */
	protected $installable = array(); 

	/**
	 * An array of module database IDs indexed by: class => id 
	 *
	 * Used internally for database operations
	 *
	 */
	protected $moduleIDs = array();

	/**
	 * Construct the Modules
	 *
	 * @param string $path Path to modules
	 * @param string $path2 Optional path to siteModules
	 * @see load()
	 *
	 */
	public function __construct($path, $path2 = null) {
		$this->load($path); 
		if($path2 && is_dir($path2)) $this->load($path2);
	}

	/**
	 * Modules class accepts only Module instances, per the WireArray interface
 	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Module;
	}

	/**
	 * The key/index used for each module in the array is it's class name, per the WireArray interface
 	 *
	 */
	public function getItemKey($item) {
		return $this->getModuleClass($item); 
	}

	/**
	 * There is no blank/generic module type, so makeBlankItem returns null
 	 *
	 */
	public function makeBlankItem() {
		return null; 
	}

	/**
	 * Initialize all the modules
	 *
	 */
	public function init() {
		foreach($this as $module) $module->init();
	}

	/**
	 * Given a disk path to the modules, instantiate all installed modules and keep track of all uninstalled (installable) modules. 
	 *
	 * @param string $path 
	 *
	 */
	protected function load($path, $level = 0) {

		static $installed = array();

		if(!count($installed)) {
			$result = $this->fuel('db')->query("SELECT id, class, flags FROM modules ORDER BY class");
			while($row = $result->fetch_assoc()) $installed[$row['class']] = $row;
		}

		$dir = new DirectoryIterator($path); 

		foreach($dir as $file) {

			if($file->isDot()) continue; 

			$filename = $file->getFilename();
			$pathname = $file->getPathname();

			if(DIRECTORY_SEPARATOR != '/') $pathname = str_replace(DIRECTORY_SEPARATOR, '/', $pathname); 

			if($file->isDir() && ($level < 1 || is_file("$pathname/$filename.module"))) $this->load($pathname, $level + 1); 
			if(substr($filename, -7) !== '.module') continue; 

			// $basename = $file->getBasename('.module'); // requires PHP 5.2.2 or newer
			$basename = basename($filename, '.module'); 

			if(!array_key_exists($basename, $installed)) {	
				$this->installable[$basename] = $pathname; 
				continue; 
			}

			$info = $installed[$basename]; 
			$this->setConfigPaths($basename, dirname($pathname)); 

			if($info['flags'] & self::flagsAutoload) { 
				// include the module and instantiate it
				include_once($pathname); 
				$module = new $basename(); 
			} else {
				// placeholder for a module, which is not yet included and instantiated
				$module = new ModulePlaceholder(); 
				//$module->id = $info['id']; 
				$module->setClass($basename); 
				$module->singular = $info['flags'] & self::flagsSingular; 
				$module->file = $pathname; 
			}
			$this->moduleIDs[$basename] = $info['id']; 

			//$module->name = $basename; 
			$this->add($module); 
		}
	}

	/**
	 * Setup entries in config->urls and config->paths for the given module
	 *
	 * @param string $moduleName
	 * @param string $path
	 *
	 */
	protected function setConfigPaths($moduleName, $path) {
		$config = $this->fuel('config'); 
		$path = rtrim($path, '/'); 
		$path = substr($path, strlen($config->paths->root)) . '/';
		$config->paths->set($moduleName, $path);
		$config->urls->set($moduleName, $path); 
	}

	/**
	 * Get the requsted Module or NULL if it doesn't exist. 
	 *
	 * If the module is a ModulePlaceholder, then it will be converted to the real module (included, instantiated, init'd) .
	 * If the module is not installed, but is installable, it will be installed, instantiated, and init'd. 
	 * This method is the only one guaranteed to return a real [non-placeholder] module. 
	 *
	 * @param string|int $key Module className or database ID
	 * @return Module|null
	 *
	 */
	public function get($key) {

		$module = null; 

		// check for optional module ID and convert to classname if found
		if(ctype_digit("$key")) {
			if(!$key = array_search($key, $this->moduleIDs)) return null;
		}

		if($module = parent::get($key)) {

			// check if it's a placeholder, and if it is then include/instantiate/init the real module 
			// OR check if it's non-singular, so that a new instance is created
			if($module instanceof ModulePlaceholder || !$this->isSingular($module)) {
				$placeholder = $module; 
				$class = $this->getModuleClass($placeholder); 
				if($module instanceof ModulePlaceholder) $this->includeModule($module); 
				$module = new $class(); 
				if($this->isSingular($placeholder)) $this->set($key, $module); 
			}

		} else if(array_key_exists($key, $this->getInstallable())) {
			// check if the request is for an uninstalled module 
			// if so, install it and return it 
			$module = $this->install($key); 
		}


		if($module) { 
			// if the module is configurable, then load it's config data
			// and set values for each before initializing themodule
			if($module instanceof ConfigurableModule) {
				$configData = $this->getModuleConfigData($module); 	
				foreach($configData as $key => $value) $module->$key = $value; 
			}

			$module->init();
		}

		return $module; 
	}

	/**
	 * Include the file for a given module, but don't instantiate it 
	 *
	 * @param ModulePlaceholder|Module|string Expects a ModulePlaceholder or className
	 * @return bool true on success
	 *
	 */
	public function includeModule($module) {

		if(is_string($module)) $module = parent::get($module); 
		if(!$module) return false; 

		if($module instanceof ModulePlaceholder) {
			include_once($module->file); 			
		} else {
			// it's already been included, no doubt
		}
		return true; 
	}

	/**
	 * Find modules based on a selector string and ensure any ModulePlaceholders are loaded in the returned result
	 *
	 * @param string $selector
	 * @return Modules
	 *	
	 */
	public function find($selector) {
		$a = parent::find($selector); 
		if($a) foreach($a as $key => $value) $a[$key] = $this->get($value->class); 
		return $a; 
	}

	/**
	 * Get an array of all modules that aren't currently installed
	 *
	 * @return array Array of elements with $className => $pathname
	 *
	 */
	public function getInstallable() {
		return $this->installable; 
	}

	/**
	 * Is the given class name installed?
	 *
	 * @param string $class
	 * @return bool
	 *
	 */
	public function isInstalled($class) {
		return ($this->get($class) !== null);
	}


	/**
	 * Is the given class name not installed?
	 *
	 * @param string $class
	 * @return bool
 	 *
	 */
	public function isInstallable($class) {
		return array_key_exists($class, $this->installable); 
	}

	/**
	 * Install the given class name
	 *
	 * @param string $class
	 * @return null|Module Returns null if unable to install, or instantiated Module object if successfully installed. 
	 *
	 */
	public function ___install($class) {
		if(!$this->isInstallable($class)) return null; 
		$pathname = $this->installable[$class]; 	
		require_once($pathname); 
		$this->setConfigPaths($class, dirname($pathname)); 

		$module = new $class();

		$flags = 0; 
		if($this->isSingular($module)) $flags = $flags | self::flagsSingular; 
		if($this->isAutoload($module)) $flags = $flags | self::flagsAutoload; 

		$sql = 	"INSERT INTO modules SET " . 
			"class='" . $this->fuel('db')->escape_string($class) . "', " . 
			"flags=$flags ";

		$result = $this->fuel('db')->query($sql); 
		//$module->id = $this->fuel('db')->insert_id; 
		$this->moduleIDs[$class] = $this->fuel('db')->insert_id;

		$this->add($module); 
		unset($this->installable[$class]); 

		// note: the module's install is called here because it may need to know it's module ID for installation of permissions, etc. 
		if(method_exists($module, '___install') || method_exists($module, 'install')) {
			$module->install();
		}

		return $module; 
	}

	/**
	 * Uninstall the given class name
	 *
	 * @param string $class
	 * @return bool
	 *
	 */
	public function ___uninstall($class) {

		$module = $this->get($class); 
		if(!$module) throw new WireException("Attempt to uninstall Module '$class' that is not installed"); 

		// if the moduleInfo contains a non-empty 'permanent' property, then it's not uninstallable
		$info = $module->getModuleInfo(); 
		if(!empty($info['permanent'])) throw new WireException("Module '$class' is permanent and thus not uninstallable"); 


		if(method_exists($module, '___uninstall') || method_exists($module, 'uninstall')) {
			// note module's uninstall method may throw an exception to abort the uninstall
			$module->uninstall();
		}

		$moduleID = $this->getModuleID($class); 

		if($moduleID) {
			// delete any permissions that this module may have installed
			$permissions = $this->fuel('permissions')->find("modules_id={$moduleID}"); 
			foreach($permissions as $permission) $this->fuel('permissions')->delete($permission); 	
		}


		$result = $this->fuel('db')->query("DELETE FROM modules WHERE class='" . $this->fuel('db')->escape_string($class) . "' LIMIT 1"); 
		return $result;
	}

	/**
	 * Returns the database ID of a given module class, or 0 if not found
	 *
	 * @param string|Module $class
	 * @return int
	 *
	 */
	public function getModuleID($class) {

		if(is_object($class)) {
			if($class instanceof Module) $class = $this->getModuleClass($class); 
				else throw new WireException("Unknown module type"); 
		}

		return isset($this->moduleIDs[$class]) ? (int) $this->moduleIDs[$class] : 0; 
	}

	/**
	 * Returns the module's class name. 
	 *
	 * Given a numeric database ID, returns the associated module class name or false if it doesn't exist
	 *
	 * Given a Module or ModulePlaceholder instance, returns the Module's class name. 
	 *
	 * If the module has a className() method then it uses that rather than PHP's get_class().
	 * This is important because of placeholder modules. For example, get_class would return 
	 * 'ModulePlaceholder' rather than the correct className for a Module.
	 *
	 * @return string|false The Module's class name or false if not found. 
	 *	Note that 'false' is only possible if you give this method a non-Module, or an integer ID 
	 * 	that doesn't correspond to a module ID. 
	 *
	 */
	public function getModuleClass($module) {

		if($module instanceof Module) {
			if(method_exists($module, 'className')) return $module->className();	
			return get_class($module); 

		} else if(is_int($module) || ctype_digit("$module")) {
			return array_search((int) $module, $this->moduleIDs); 

		} 

		return false; 
	}


	/**
	 * Returns the standard array of information for a Module
	 *
	 * @param string|Module|int $module May be class name, module instance, or module ID
	 * @return array
	 *	
	 */
	public function getModuleInfo($module) {

		if($module instanceof Module || ctype_digit("$module")) {
			$module = $this->getModuleClass($module); 
		}

		if(!class_exists($module)) return array(
			'title' => $module, 
			'summary' => 'Inactive', 
			'version' => 0, 
			); 

		//$func = $module . "::getModuleInfo"; // requires PHP 5.2.3+
		//return call_user_func($func);
		return call_user_func(array($module, 'getModuleInfo'));
	}

	/**
	 * Given a class name, return an array of configuration data specified for the Module
	 *
	 * Corresponds to the modules.data table in the database
	 *
	 * Applicable only for modules that implement the ConfigurableModule interface
	 *
	 * @param string|Module $className
	 * @return array
	 *
	 */
	public function getModuleConfigData($className) {

		if(is_object($className)) $className = $className->className();
		if(!$id = $this->moduleIDs[$className]) return array();

		// if the class doesn't implement ConfigurableModule, then it's not going to have any configData
		if(!in_array('ConfigurableModule', class_implements($className))) return array();

		$result = $this->fuel('db')->query("SELECT data FROM modules WHERE id=$id"); 
		list($data) = $result->fetch_array(); 
		if(empty($data)) return array();
		$data = json_decode($data, true); 

		return $data; 	
	}

	/**
	 * Given a module class name and an array of configuration data, save it for the module
	 *
	 * @param string|Module $className
	 * @param array $configData
	 * @return bool True on success
	 *
	 */
	public function saveModuleConfigData($className, array $configData) {
		if(is_object($className)) $className = $className->className();
		if(!$id = $this->moduleIDs[$className]) throw new WireException("Unable to find ID for Module '$className'"); 
		$json = count($configData) ? json_encode($configData) : '';
		return $this->fuel('db')->query("UPDATE modules SET data='" . $this->fuel('db')->escape_string($json) . "' WHERE id=$id"); 
	}

	/**
	 * Is the given module Singular (single instance)?
	 *
	 * isSingular and isAutoload Module methods have been deprecated. So this method, and isAutoload() 
	 * exist in part to enable singular and autoload properties to be set in getModuleInfo, rather than 
	 * with methods. 
 	 *
	 * Note that isSingular() and isAutoload() are not deprecated for ModulePlaceholder, so the Modules
	 * class isn't going to stop looking for them. 
	 *
	 * @param Module $module
	 * @return bool 
 	 *
	 */
	public function isSingular(Module $module) {
		$info = $module->getModuleInfo();
		if(isset($info['singular'])) return $info['singular'];
		if(method_exists($module, 'isSingular')) return $module->isSingular();
		// $info = call_user_func(array($module, 'getModuleInfo'));
		return false;
	}

	/**
	 * Is the given module Autoload (automatically loaded at runtime)?
	 *
	 * @param Module $module
	 * @return bool 
 	 *
	 */
	public function isAutoload(Module $module) {
		$info = $module->getModuleInfo();
		if(isset($info['autoload'])) return $info['autoload'];
		if(method_exists($module, 'isAutoload')) return $module->isAutoload();
		return false; 
	}


}

