<?php

class TestReadmeExamples extends JSONValidatorScaffold {
	public function testProductSpecsFromFile() {
		$validator = JSONValidator::LoadFromFile(self::$_AssetsDirectory.'/products-specs.json');

		try {
			$this->assertTrue($validator->validatePath(self::$_AssetsDirectory.'/products.json', $info), "JSON File 'products.json' failed while checking against 'products-specs.json'.");
		} catch(\Exception $e) {
			echo "\n>>> JSONValidator Error: {$info[JV_FIELD_ERROR][JV_FIELD_MESSAGE]}\n";
			throw $e;
		}
	}
	public function testProductSpecsFromSrting() {
		$specs = file_get_contents(self::$_AssetsDirectory.'/products-specs.json');
		$validator = JSONValidator::LoadFromString($specs);

		try {
			$this->assertTrue($validator->validatePath(self::$_AssetsDirectory.'/products.json', $info), "JSON File 'products.json' failed while checking against 'products-specs.json'.");
		} catch(\Exception $e) {
			echo "\n>>> JSONValidator Error: {$info[JV_FIELD_ERROR][JV_FIELD_MESSAGE]}\n";
			throw $e;
		}
	}
}
