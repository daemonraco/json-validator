<?php

/**
 * @file JSONPolicies.php
 * @author Alejandro Dario Simi
 */

namespace JV;

/**
 * @class JSONPolicies
 * This class holds the logic to check field policies, for examples length limits,
 * allowed values, etc.
 */
class JSONPolicies {
	//
	// Protected class properties.
	/**
	 * @var mixed[string] Known policy associations.
	 */
	protected static $_KnownPolicies = [
		JV_PRIMITIVE_TYPE_ARRAY => [JV_POLICY_EXCEPT, JV_POLICY_MAX, JV_POLICY_MIN, JV_POLICY_ONLY],
		JV_PRIMITIVE_TYPE_FLOAT => [JV_POLICY_EXCEPT, JV_POLICY_MAX, JV_POLICY_MIN, JV_POLICY_ONLY],
		JV_PRIMITIVE_TYPE_INT => [JV_POLICY_EXCEPT, JV_POLICY_MAX, JV_POLICY_MIN, JV_POLICY_ONLY],
		JV_PRIMITIVE_TYPE_STRING => [JV_POLICY_EXCEPT, JV_POLICY_MAX, JV_POLICY_MIN, JV_POLICY_ONLY],
		JV_STYPE_STRUCTURE => [JV_POLICY_STRICT],
		JV_PTYPE_CONTAINER_ARRAY => [JV_POLICY_MAX, JV_POLICY_MIN]
	];
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
	 * Generic policy checker, it validates and triggers the right logic.
	 *
	 * @param mixed $value Value to check.
	 * @param string $type Type to verify.
	 * @param string $policy Policy to verify on the given type.
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $info Extra information about the verification.
	 * @return boolean Returns TRUE if the check succeeded.
	 * @throws \JV\JSONPolicyException
	 */
	public function check($value, $type, $policy, $mods = false, &$info = false) {
		//
		// Default values.
		$info = [
			JV_FIELD_ERROR => false
		];
		$ok = true;
		//
		// Guessing the right policy checker method.
		$policyFunc = str_replace(' ', '', "check".ucwords(preg_replace('/[-_]/', ' ', "{$type} {$policy}")));
		//
		// Checking if it's possibly to validate the policy.
		if(method_exists($this, $policyFunc)) {
			//
			// Checking if it applies.
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
	/**
	 * Policy verification: array-except
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
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
	/**
	 * Policy verification: array-max
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkArrayMax($value, $mods, &$message) {
		$message = "The number of elements is greater than '{$mods}'.";
		return count($value) <= $mods;
	}
	/**
	 * Policy verification: array-min
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkArrayMin($value, $mods, &$message) {
		$message = "The number of elements is lower than '{$mods}'.";
		return count($value) >= $mods;
	}
	/**
	 * Policy verification: array-only
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
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
	/**
	 * Policy verification: container(array-like)-max
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkContinerArrayMax($value, $mods, &$message) {
		$message = "The number of elements is greater than '{$mods}'.";
		return count($value) <= $mods;
	}
	/**
	 * Policy verification: container(array-like)-min
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkContinerArrayMin($value, $mods, &$message) {
		$message = "The number of elements is lower than '{$mods}'.";
		return count($value) >= $mods;
	}
	/**
	 * Policy verification: float-except
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkFloatExcept($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return !in_array($value, $mods);
	}
	/**
	 * Policy verification: float-max
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkFloatMax($value, $mods, &$message) {
		$message = "Value is greater than '{$mods}'.";
		return $value <= $mods;
	}
	/**
	 * Policy verification: float-min
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkFloatMin($value, $mods, &$message) {
		$message = "Value is lower than '{$mods}'.";
		return $value >= $mods;
	}
	/**
	 * Policy verification: float-only
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkFloatOnly($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return in_array($value, $mods);
	}
	/**
	 * Policy verification: int-except
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkIntExcept($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return !in_array($value, $mods);
	}
	/**
	 * Policy verification: int-max
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkIntMax($value, $mods, &$message) {
		$message = "Value is greater than '{$mods}'.";
		return $value <= $mods;
	}
	/**
	 * Policy verification: int-min
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkIntMin($value, $mods, &$message) {
		$message = "Value is lower than '{$mods}'.";
		return $value >= $mods;
	}
	/**
	 * Policy verification: int-only
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkIntOnly($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return in_array($value, $mods);
	}
	/**
	 * Policy verification: string-except
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkStringExcept($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return !in_array($value, $mods);
	}
	/**
	 * Policy verification: string-max
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkStringMax($value, $mods, &$message) {
		$message = "Value is longer than '{$mods}'.";
		return strlen($value) <= $mods;
	}
	/**
	 * Policy verification: string-min
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkStringMin($value, $mods, &$message) {
		$message = "Value is shorter than '{$mods}'.";
		return strlen($value) >= $mods;
	}
	/**
	 * Policy verification: string-only
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkStringOnly($value, $mods, &$message) {
		$message = "Value '{$value}' is not allowed.";
		return in_array($value, $mods);
	}
	/**
	 * Policy verification: structure-strict
	 *
	 * @param mixed $value value to check
	 * @param mixed $mods Extra values to use when verifying.
	 * @param string $message When it fails, this message explains the reason.
	 * @return boolean Returns TRUE when the policy is applied.
	 */
	protected function checkStructureStrict($value, $mods, &$message) {
		$ok = true;

		if($mods[JV_FIELD_MODS]) {
			$unknownKeys = array_diff(array_keys(get_object_vars($value)), $mods[JV_FIELD_FIELDS]);
			$ok = empty($unknownKeys);
			$message = "Unknown fields: '".implode("', '", $unknownKeys)."'.";
		}

		return $ok;
	}
	//
	// Public class methods.
	/**
	 * Singleton accessor.
	 *
	 * @return \JV\JSONPolicies Returns the single instance of this class.
	 */
	public static function Instance() {
		static $instance = false;

		if(!$instance) {
			$instance = new self;
		}

		return $instance;
	}
	/**
	 * This calss method provides access to a list of accepted policies.
	 *
	 * @return mixed[string] Returns a list of policies.
	 */
	public static function KnownPolicies() {
		return self::$_KnownPolicies;
	}
}
