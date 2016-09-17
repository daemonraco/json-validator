<?php

class JSONValidatorScaffold extends PHPUnit_Framework_TestCase {
	static $_AssetsDirectory = false;
	public static function setUpBeforeClass() {
		$reflector = new ReflectionClass(get_called_class());
		$path = pathinfo($reflector->getFileName());
		self::$_AssetsDirectory = JV_DIRECTORY_ASSETS."/cases/{$path['filename']}";
	}
	public static function tearDownAfterClass() {
		self::$_AssetsDirectory = false;
	}
}
