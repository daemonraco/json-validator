<?php

class Container extends JSONValidatorScaffold {
	public function testContainerOfArrayType() {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-container-array.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification to test a container type cannot be loaded. {$exceptionMessage}");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-container-array.json", $info);
		$this->assertTrue($check, "Validation failed on a valid file.");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-container-array-mixtypes.json", $info);
		$this->assertFalse($check, "Validation succeeded on a mixed-type array when it shouldn't.");
		$this->assertRegExp("~The type of field at //test\[0\] is not string\.~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "Extra information does not mention the error.");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-container-array-notarray.json", $info);
		$this->assertFalse($check, "Validation succeeded on a non-array when it shouldn't.");
		$this->assertRegExp("~Field at '//test' is a container but not an array\.~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "Extra information does not mention the error.");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-container-array-wrongtype.json", $info);
		$this->assertFalse($check, "Validation succeeded on a simple string when it shouldn't.");
		$this->assertRegExp("~Field at '//test' is not a container\.~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "Extra information does not mention the error.");
	}
	public function testContainerOfObjectType() {
		$validator = false;
		$exceptionMessage = false;

		try {
			$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory."/specs-container-object.json");
		} catch(\JSONValidatorException $e) {
			$exceptionMessage = $e->getMessage();
		}
		$this->assertFalse(boolval($exceptionMessage), "Specification to test a container type cannot be loaded. {$exceptionMessage}");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-container-object.json", $info);
		$this->assertTrue($check, "Validation failed on a valid file.");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-container-object-mixtypes.json", $info);
		$this->assertFalse($check, "Validation succeeded on a mixed-type object when it shouldn't.");
		$this->assertRegExp("~The type of field at //test/field is not string\.~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "Extra information does not mention the error.");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-container-object-notobject.json", $info);
		$this->assertFalse($check, "Validation succeeded on a non-object when it shouldn't.");
		$this->assertRegExp("~Field at '//test' is a container but not an object\.~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "Extra information does not mention the error.");

		$info = false;
		$check = $validator->validatePath(self::$_AssetsDirectory."/test-container-object-wrongtype.json", $info);
		$this->assertFalse($check, "Validation succeeded on a simple string when it shouldn't.");
		$this->assertRegExp("~Field at '//test' is not a container\.~", $info[JV_FIELD_ERROR][JV_FIELD_MESSAGE], "Extra information does not mention the error.");
	}
}
