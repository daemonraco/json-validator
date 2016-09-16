<?php

class TestReadmeExamples extends JSONValidatorScaffold {
	public function testProduct() {
		$validator = JSONValidator::GetValidator(JV_DIRECTORY_ASSETS.'/cases/001TestReadmeExamples/products-specs.json');

		try {
			$this->assertTrue($validator->validatePath(JV_DIRECTORY_ASSETS.'/cases/001TestReadmeExamples/products.json', $info), "JSON File 'products.json' failed while checking against 'products-specs.json'.");
		} catch(\Exception $e) {
			echo "\n>>> JSONValidator Error: {$info[JV_FIELD_ERROR][JV_FIELD_MESSAGE]}\n";
//			print_r($info);
//			echo "\n";
			throw $e;
		}
	}
}
