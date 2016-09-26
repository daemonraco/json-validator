<?php

/**
 * @file json-validator.php
 * @author Alejandro Dario Simi
 */
require_once __DIR__.'/includes/define.php';
//
// Autoloading listener.
spl_autoload_register(function($class) {
	//
	// Known classes.
	static $knownClasses = [
		'JSONValidator' => '/includes/JSONValidator.php',
		'JSONValidatorException' => '/includes/JSONValidator.php',
		'JV\JSONPolicies' => '/includes/JSONPolicies.php',
		'JV\JSONPolicyException' => '/includes/JSONPolicies.php'
	];
	//
	// Loading...
	if(isset($knownClasses[$class])) {
		require_once __DIR__.$knownClasses[$class];
	}
});
