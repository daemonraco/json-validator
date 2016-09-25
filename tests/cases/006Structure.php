<?php

class Structure extends JSONValidatorScaffold {
	public function testStructure() {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-structure.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification to test a container type cannot be loaded. {$exceptionMessage}");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-structure.json", $info);
		$this->assertTrue($check, "Validation failed on a valid file.");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-structure-wrongtype.json", $info);
		$this->assertFalse($check, "Validation succeeded on a mixed-type array when it shouldn't.");
		$this->assertRegExp("~Field at '//test' is not a structure\.~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "Extra information does not mention the error.");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-structure-required.json", $info);
		$this->assertFalse($check, "Validation succeeded on a mixed-type array when it shouldn't.");
		$this->assertRegExp("~Required field at '//test/field' is not present\.~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "Extra information does not mention the error.");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-structure-mixed.json", $info);
		$this->assertFalse($check, "Validation succeeded on a mixed-type array when it shouldn't.");
		$this->assertRegExp("~The type of field at //test/field is not string\.~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "Extra information does not mention the error.");
	}
}
