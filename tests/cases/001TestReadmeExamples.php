<?php

class TestReadmeExamples extends JSONValidatorScaffold {
	public function testProduct() {
		$validator = JSONValidator::GetValidator(self::$_AssetsDirectory.'/products-specs.json');

		try {
			$this->assertTrue($validator->validatePath(self::$_AssetsDirectory.'/products.json', $info), "JSON File 'products.json' failed while checking against 'products-specs.json'.");
		} catch(\Exception $e) {
			echo "\n>>> JSONValidator Error: {$info[JV_FIELD_ERROR][JV_FIELD_MESSAGE]}\n";
			throw $e;
		}
	}
}
