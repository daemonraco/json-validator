<?php

/**
 * @file JSONPolicies.php
 * @author Alejandro Dario Simi
 */

namespace JV;

//
// Class aliases.
use JSONValidatorException;

/**
 * @class JSONPolicyException
 */
class JSONPolicyException extends JSONValidatorException {
	
}

/**
 * @class JSONPolicies
 * @todo doc
 */
class JSONPolicies {
	//
	// Protected properties.
	//
	// Magic methods.
	/**
	 * Class constructor.
	 */
	protected function __construct() {
		
	}
	//
	// Public methods.
	/**
	 * @todo doc
	 *
	 * @param type $value @todo doc
	 * @param type $type @todo doc
	 * @param type $policy @todo doc
	 * @param type $mods @todo doc
	 * @param mixed[string] $info @todo doc
	 * @return boolean Returns TRUE if the check succeeded.
	 * @throws \JV\JSONPolicyException
	 */
	public function check($value, $type, $policy, $mods = false, &$info = false) {
		$info = [
			JV_FIELD_ERROR => false
		];
		$ok = true;

		$policyFunc = str_replace(' ', '', "check".ucwords("{$type} {$policy}"));
		if(method_exists($this, $policyFunc)) {
			if(!$this->{$policyFunc}($value, $mods, $message)) {
				$info = [
					JV_FIELD_ERROR => $message
				];
				$ok = false;
			}
		} else {
			throw new JSONPolicyException("Unknown policy '{$policy}' for type '{$type}'.");
		}

		return $ok;
	}
	//
	// Protected methods.
	protected function checkArrayExcept($value, $mods, &$message) {
		$ok = true;

		foreach($value as $v) {
			if(in_array($v, $mods)) {
				$message = "Value '{$v}' is not allowed.";
				$ok = false;
				break;
			}
		}

		return $ok;
	}
	protected function checkArrayMax($value, $mods, &$message) {
		$message = "The number of elements is greater than '{$mods}'.";
		return count($value) <= $mods;
	}
	protected function checkArrayMin($value, $mods, &$message) {
		$message = "The number of elements is lower than '{$mods}'.";
		return count($value) >= $mods;
	}
	protected function checkArrayOnly($value, $mods, &$message) {
		$ok = true;

		foreach($value as $v) {
			if(!in_array($v, $mods)) {
				$message = "Value '{$v}' is not allowed.";
				$ok = false;
				break;
			}
		}

		return $ok;
	}
	protected function checkFloatExcept($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return !in_array($value, $mods);
	}
	protected function checkFloatMax($value, $mods, &$message) {
		$message = "Value is greater than '{$mods}'.";
		return $value <= $mods;
	}
	protected function checkFloatMin($value, $mods, &$message) {
		$message = "Value is lower than '{$mods}'.";
		return $value >= $mods;
	}
	protected function checkFloatOnly($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return in_array($value, $mods);
	}
	protected function checkIntExcept($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return !in_array($value, $mods);
	}
	protected function checkIntMax($value, $mods, &$message) {
		$message = "Value is greater than '{$mods}'.";
		return $value <= $mods;
	}
	protected function checkIntMin($value, $mods, &$message) {
		$message = "Value is lower than '{$mods}'.";
		return $value >= $mods;
	}
	protected function checkIntOnly($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return in_array($value, $mods);
	}
	protected function checkStringExcept($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return !in_array($value, $mods);
	}
	protected function checkStringMax($value, $mods, &$message) {
		$message = "Value is longer than '{$mods}'.";
		return strlen($value) <= $mods;
	}
	protected function checkStringMin($value, $mods, &$message) {
		$message = "Value is shorter than '{$mods}'.";
		return strlen($value) >= $mods;
	}
	protected function checkStringOnly($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return in_array($value, $mods);
	}
	//
	// Public class methods.
	/**
	 * @todo doc
	 *
	 * @return \JV\JSONPolicies @todo doc
	 */
	public static function Instance() {
		static $instance = false;

		if(!$instance) {
			$instance = new self;
		}

		return $instance;
	}
}
