<?php

class PrimitiveTypes extends JSONValidatorScaffold {
	//
	// Internal properties.
	protected $_allTypes = [
		JV_PRIMITIVE_TYPE_ARRAY,
		JV_PRIMITIVE_TYPE_BOOLEAN,
		JV_PRIMITIVE_TYPE_FLOAT,
		JV_PRIMITIVE_TYPE_INT,
		JV_PRIMITIVE_TYPE_MIXED,
		JV_PRIMITIVE_TYPE_OBJECT,
		JV_PRIMITIVE_TYPE_REGEXP,
		JV_PRIMITIVE_TYPE_STRING
	];
	//
	// Test cases.
	public function testPrimitives() {
		foreach($this->_allTypes as $type) {
			$this->runTestsForType($type);
		}
	}
	public function testCrossedPrimitives() {
		foreach($this->_allTypes as $type) {
			//
			// 'mixed' has to be tested in a different way.
			if($type == JV_PRIMITIVE_TYPE_MIXED) {
				break;
			}

			$this->runCrossTestsForType($type);
		}
	}
	public function testPrimitiveMixedCrossed() {
		$type = JV_PRIMITIVE_TYPE_MIXED;
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-{$type}.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification to test the primitive type '{$type}' cannot be loaded. {$exceptionMessage}");

		foreach($this->_allTypes as $crossType) {
			//
			// 'mixed' against 'mixed' is already tested at this
			// point.
			if($crossType == $type) {
				break;
			}

			$check = $validator->validatePath(self::$_AssetsDirectory."/test-{$crossType}.json", $info);
			$this->assertTrue($check, "Cross validation between any type and '{$type}' should match.");
		}
	}
	//
	// Internal methods.
	protected function runCrossTestsForType($type) {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-{$type}.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification to test the primitive type '{$type}' cannot be loaded. {$exceptionMessage}");

		foreach($this->_allTypes as $crossType) {
			if($crossType == $type) {
				break;
			}

			$check = $validator->validatePath(self::$_AssetsDirectory."/test-{$crossType}.json", $info);
			$this->assertFalse($check, "Cross validation between types '{$crossType}' and '{$type}' should not match.");
		}
	}
	protected function runTestsForType($type) {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-{$type}.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification to test the primitive type '{$type}' cannot be loaded. {$exceptionMessage}");

		$check = $validator->validatePath(self::$_AssetsDirectory."/test-{$type}.json", $info);
		$this->assertTrue($check, "Validation for type '{$type}' failed.");
	}
}
