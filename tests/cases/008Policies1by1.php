<?php

use JV\JSONPolicies;

class Policies1by1 extends JSONValidatorScaffold {
	public function testArrayExcept() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check([1, 2, 3, 4, 6, 8, 9, 10], JV_PRIMITIVE_TYPE_ARRAY, JV_POLICY_EXCEPT, [5, 7], $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], JV_PRIMITIVE_TYPE_ARRAY, JV_POLICY_EXCEPT, [5, 7], $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value '.*' is not allowed.~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testArrayMax() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], JV_PRIMITIVE_TYPE_ARRAY, JV_POLICY_MAX, 10, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], JV_PRIMITIVE_TYPE_ARRAY, JV_POLICY_MAX, 10, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~The number of elements is greater than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testArrayMin() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], JV_PRIMITIVE_TYPE_ARRAY, JV_POLICY_MIN, 10, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check([1, 2, 3, 4, 5, 6, 7, 8, 9], JV_PRIMITIVE_TYPE_ARRAY, JV_POLICY_MIN, 10, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~The number of elements is lower than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testArrayOnly() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check([5, 5, 7], JV_PRIMITIVE_TYPE_ARRAY, JV_POLICY_ONLY, [5, 7], $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check([5, 6, 5, 7], JV_PRIMITIVE_TYPE_ARRAY, JV_POLICY_ONLY, [5, 7], $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value '.*' is not allowed.~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testContinerArrayMax() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check([1, 2.1, '3', 4.1, '5', 6, 7.1, '8', 9, 10.1], JV_PTYPE_CONTAINER_ARRAY, JV_POLICY_MAX, 10, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check([1, 2.1, '3', 4.1, '5', 6, 7.1, '8', 9, 10.1, '11'], JV_PTYPE_CONTAINER_ARRAY, JV_POLICY_MAX, 10, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~The number of elements is greater than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testContinerArrayMin() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check([1, 2.1, '3', 4.1, '5', 6, 7.1, '8', 9, 10.1], JV_PTYPE_CONTAINER_ARRAY, JV_POLICY_MIN, 10, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check([1, 2.1, '3', 4.1, '5', 6, 7.1, '8', 9], JV_PTYPE_CONTAINER_ARRAY, JV_POLICY_MIN, 10, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~The number of elements is lower than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testFloatExcept() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check(2.4, JV_PRIMITIVE_TYPE_FLOAT, JV_POLICY_EXCEPT, [3.7, 6.3], $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(3.7, JV_PRIMITIVE_TYPE_FLOAT, JV_POLICY_EXCEPT, [3.7, 6.3], $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value '.*' is not allowed~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testFloatMax() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check(10.0, JV_PRIMITIVE_TYPE_FLOAT, JV_POLICY_MAX, 10.0, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(11.0, JV_PRIMITIVE_TYPE_FLOAT, JV_POLICY_MAX, 10.0, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value is greater than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testFloatMin() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check(10.0, JV_PRIMITIVE_TYPE_FLOAT, JV_POLICY_MIN, 10.0, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(9.0, JV_PRIMITIVE_TYPE_FLOAT, JV_POLICY_MIN, 10.0, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value is lower than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testFloatOnly() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check(3.7, JV_PRIMITIVE_TYPE_FLOAT, JV_POLICY_ONLY, [3.7, 6.3], $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(2.4, JV_PRIMITIVE_TYPE_FLOAT, JV_POLICY_ONLY, [3.7, 6.3], $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value '.*' is not allowed~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testIntExcept() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check(2, JV_PRIMITIVE_TYPE_INT, JV_POLICY_EXCEPT, [3, 6], $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(3, JV_PRIMITIVE_TYPE_INT, JV_POLICY_EXCEPT, [3, 6], $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value '.*' is not allowed~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testIntMax() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check(10, JV_PRIMITIVE_TYPE_INT, JV_POLICY_MAX, 10, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(11, JV_PRIMITIVE_TYPE_INT, JV_POLICY_MAX, 10, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value is greater than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testIntMin() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check(10, JV_PRIMITIVE_TYPE_INT, JV_POLICY_MIN, 10, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(9, JV_PRIMITIVE_TYPE_INT, JV_POLICY_MIN, 10, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value is lower than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testIntOnly() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check(3, JV_PRIMITIVE_TYPE_INT, JV_POLICY_ONLY, [3, 6], $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(2, JV_PRIMITIVE_TYPE_INT, JV_POLICY_ONLY, [3, 6], $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value '.*' is not allowed~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testStringExcept() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check('aaa', JV_PRIMITIVE_TYPE_STRING, JV_POLICY_EXCEPT, ['bbb', 'ccc'], $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check('bbb', JV_PRIMITIVE_TYPE_STRING, JV_POLICY_EXCEPT, ['bbb', 'ccc'], $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value '.*' is not allowed~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testStringMax() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check('abcdefghij', JV_PRIMITIVE_TYPE_STRING, JV_POLICY_MAX, 10, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check('abcdefghijk', JV_PRIMITIVE_TYPE_STRING, JV_POLICY_MAX, 10, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value is longer than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testStringMin() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check('abcdefghij', JV_PRIMITIVE_TYPE_STRING, JV_POLICY_MIN, 10, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check('abcdefghi', JV_PRIMITIVE_TYPE_STRING, JV_POLICY_MIN, 10, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value is shorter than~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testStringOnly() {
		$checker = JSONPolicies::Instance();

		$applied = $checker->check('bbb', JV_PRIMITIVE_TYPE_STRING, JV_POLICY_ONLY, ['bbb', 'ccc'], $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check('aaa', JV_PRIMITIVE_TYPE_STRING, JV_POLICY_ONLY, ['bbb', 'ccc'], $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Value '.*' is not allowed~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
	public function testStructureStrict() {
		$checker = JSONPolicies::Instance();

		$mods = [
			JV_FIELD_MODS => true,
			JV_FIELD_FIELDS => ['f1', 'f2']
		];

		$applied = $checker->check(json_decode('{"f1":10,"f2":11}'), JV_STYPE_STRUCTURE, JV_POLICY_STRICT, $mods, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(json_decode('{"f1":10}'), JV_STYPE_STRUCTURE, JV_POLICY_STRICT, $mods, $info);
		$this->assertTrue($applied, $info[JV_FIELD_ERROR]);

		$applied = $checker->check(json_decode('{"f1":10,"f2":11,"f3":12}'), JV_STYPE_STRUCTURE, JV_POLICY_STRICT, $mods, $info);
		$this->assertFalse($applied, 'Check should have failed.');
		$this->assertRegExp("~Unknown fields: ~", $info[JV_FIELD_ERROR], 'Error is not mentioned.');
	}
}
