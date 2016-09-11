<?php

/**
 * @file json-validator.php
 * @author Alejandro Dario Simi
 */
spl_autoload_register(function($class) {
	static $knownClasses = [
		'JSONValidator' => '/includes/JSONValidator.php',
		'JSONValidatorException' => '/includes/JSONValidator.php'
	];

	if(isset($knownClasses[$class])) {
		require_once __DIR__.$knownClasses[$class];
	}
});
