<?php

class BasicFilesValidations extends JSONValidatorScaffold {
	public function testNonExistingSpecificationFile() {
		$exceptionMessage = false;
		try {
			JSONValidator::GetValidator(self::$_AssetsDirectory.'/NON-EXISTING-SPECS-FILE');
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}

		$this->assertTrue($exceptionMessage !== false, "Using an unknown specification file doesn't trigger an error.");
		$this->assertRegExp("~JSONValidator: Path '.*/NON-EXISTING-SPECS-FILE' is not a file.~", $exceptionMessage, "The error message is not as expected.");
	}
	public function testInvalidSpecificationFile() {
		$exceptionMessage = false;
		try {
			JSONValidator::GetValidator(self::$_AssetsDirectory.'/invalid-spec.json');
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}

		$this->assertTrue($exceptionMessage !== false, "Using an invalid specification file doesn't trigger an error.");
		$this->assertRegExp("~JSONValidator: Path '.*/invalid-spec.json' is not a valid JSON file. \\[.*\\] .*~", $exceptionMessage, "The error message is not as expected.");
	}
	public function testNonExistingFileToValidate() {
		$validator = JSONValidator::GetValidator(self::$_AssetsDirectory.'/products-specs.json');

		$exceptionMessage = false;
		try {
			$validator->validatePath(self::$_AssetsDirectory.'/NON-EXISTING-FILE');
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}

		$this->assertTrue($exceptionMessage !== false, "Trying to validate a non existing file doesn't trigger an error.");
		$this->assertRegExp("~JSONValidator: Path '.*/NON-EXISTING-FILE' is not a file.~", $exceptionMessage, "The error message is not as expected.");
	}
	public function testInvalidFileToValidate() {
		$validator = JSONValidator::GetValidator(self::$_AssetsDirectory.'/products-specs.json');
		$check = $validator->validatePath(self::$_AssetsDirectory.'/invalid-spec.json', $info);

		$this->assertFalse($check, "Validation of an invalid JSON file should fail.");
		$this->assertTrue(is_array($info), "Extra information is not an array.");
		foreach([JV_FIELD_ERROR, JV_FIELD_ERRORS] as $field) {
			$this->assertArrayHasKey($field, $info, "Extra information does not have a field called '{$field}'.");
		}
		$this->assertArrayHasKey(JV_FIELD_MESSAGE, $info[JV_FIELD_ERROR], "On extra information, the main error does not have a field called '".JV_FIELD_ERROR."'.");
		$this->assertRegExp("~The give JSON is not valid. \\[.*\\] .*~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "The error message is not as expected.");
	}
}
