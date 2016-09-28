<?php

use JV\JSONPolicies;

class Policies extends JSONValidatorScaffold {
	public function testIntPolicies() {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-int.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification cannot be loaded. {$exceptionMessage}");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-int.json", $info);
		$this->assertTrue($check, "Validation failed on a valid file.");
	}
	public function testFloatPolicies() {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-float.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification cannot be loaded. {$exceptionMessage}");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-float.json", $info);
		$this->assertTrue($check, "Validation failed on a valid file.");
	}
	public function testArrayPolicies() {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-array.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification cannot be loaded. {$exceptionMessage}");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-array.json", $info);
		$this->assertTrue($check, "Validation failed on a valid file.");
	}
	public function testStringPolicies() {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-string.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification cannot be loaded. {$exceptionMessage}");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-string.json", $info);
		$this->assertTrue($check, "Validation failed on a valid file.");
	}
	public function testStructurePolicies() {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-structure.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification cannot be loaded. {$exceptionMessage}");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-structure.json", $info);
//DEBUG
		if(!$check) {
			debugit($info);
		}
//DEBUG
		$this->assertTrue($check, "Validation failed on a valid file.");
	}
//	public function testBasic() {
//		$checker = JSONPolicies::Instance();
//
//		$result = $checker->check(10, JV_PRIMITIVE_TYPE_INT, 'max', [10], $info);
//		debugit([
//			'$result' => $result,
//			'$info' => $info
//		]);
//	}
}
