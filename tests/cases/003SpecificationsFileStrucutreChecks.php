<?php

class SpecificationsFileStrucutreChecks extends JSONValidatorScaffold {
	public function testSpecificationFileWithoutTypes() {
		foreach(['types', 'root'] as $field) {
			$exceptionMessage = false;

			try {
				JSONValidator::GetValidator(self::$_AssetsDirectory."/specs-no-field-{$field}.json");
			} catch(\JSONValidatorException $e) {
				$exceptionMessage = $e->getMessage();
			}

			$this->assertTrue($exceptionMessage !== false, "Not specifying the field '{$field}' doesn't trigger an error.");
			$this->assertRegExp("~JSONValidator: Specification has no field '{$field}'.~", $exceptionMessage, "The error message doesn't mention the field.");
		}
	}
	public function testWrongFieldRootSpecification() {
		$exceptionMessage = false;

		try {
			JSONValidator::GetValidator(self::$_AssetsDirectory.'/specs-wrong-root.json');
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}

		$this->assertTrue($exceptionMessage !== false, "Trying to use a wrong value on field 'root' doesn't trigger an error.");
		$this->assertRegExp("~JSONValidator: Type 'ROOT' is not well defined.~", $exceptionMessage, "The error message is not as expected.");
	}
	public function testWrongFieldTypesSpecification() {
		$exceptionMessage = false;

		try {
			JSONValidator::GetValidator(self::$_AssetsDirectory.'/specs-wrong-types.json');
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}

		$this->assertTrue($exceptionMessage !== false, "Trying to use a wrong value on field 'root' doesn't trigger an error.");
		$this->assertRegExp("~JSONValidator: Specification field 'types' is not an object.~", $exceptionMessage, "The error message is not as expected.");
	}
	public function testUnusedType() {
		$exceptionMessage = false;

		try {
			JSONValidator::GetValidator(self::$_AssetsDirectory.'/specs-unused-type.json');
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}

		$this->assertTrue($exceptionMessage !== false, "Trying to use a wrong value on field 'root' doesn't trigger an error.");
		$this->assertRegExp("~JSONValidator: Type 'UnusedType' is defined but not used.~", $exceptionMessage, "The error message is not as expected.");
	}
	public function testUndefinedType() {
		$exceptionMessage = false;

		try {
			JSONValidator::GetValidator(self::$_AssetsDirectory."/specs-undefined-type.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}

		$this->assertTrue($exceptionMessage !== false, "Trying to use a wrong value on field 'root' doesn't trigger an error.");
		$this->assertRegExp("~JSONValidator: Type 'UndefinedType' is used but not defined.~", $exceptionMessage, "The error message is not as expected.");
	}
}
