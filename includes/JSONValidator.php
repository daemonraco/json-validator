<?php

/**
 * @file JSONValidator.php
 * @author Alejandro Dario Simi
 */
//
// Class aliases.
use JV\JSONPolicies;

/**
 * @class JSONValidatorException
 */
class JSONValidatorException extends Exception {
	
}

/**
 * @class JSONValidator
 * This class holds all the logic to validate a JSON string based on a
 * specification.
 */
class JSONValidator {
	//
	// Protected class properties.
	/**
	 * @var string[] List of types that may contain sub-types.
	 */
	protected static $_ContainerTypes = [
		JV_CONTAINER_TYPE_ARRAY,
		JV_CONTAINER_TYPE_OBJECT
	];
	/**
	 * @var string[string] @todo doc
	 */
	protected static $_PolicyTypeTranslations = [
		JV_CONTAINER_TYPE_ARRAY => JV_PTYPE_CONTAINER_ARRAY,
		JV_CONTAINER_TYPE_OBJECT => JV_PTYPE_CONTAINER_OBJECT
	];
	/**
	 * @var string[] List of all types known as primitive, these don't
	 * require complex checks.
	 */
	protected static $_PrimitiveTypes = [
		JV_PRIMITIVE_TYPE_ARRAY,
		JV_PRIMITIVE_TYPE_BOOLEAN,
		JV_PRIMITIVE_TYPE_FLOAT,
		JV_PRIMITIVE_TYPE_INT,
		JV_PRIMITIVE_TYPE_MIXED,
		JV_PRIMITIVE_TYPE_OBJECT,
		JV_PRIMITIVE_TYPE_STRING
	];
	/**
	 * @var string[] List of all types known as primitive that have a specific
	 * way to be checked.
	 */
	protected static $_PrimitiveSpecialTypes = [
		JV_PRIMITIVE_TYPE_REGEXP
	];
	/**
	 * @var string This is the pattern all type alias specifications should
	 * match.
	 */
	protected static $_PatternTypeAliases = '/^(?P<name>[a-zA-Z0-9]+)(?P<mods>(\\{\\}|\\[\\])?)$/';
	/**
	 * @var string This is the pattern all field type specifications should
	 * match.
	 */
	protected static $_PatternFieldType = '/^(?P<required>[+-]?)(?P<type>[a-zA-Z0-9]+)$/';
	//
	// Protected properties.
	/**
	 * @var mixed[string] @todo doc
	 */
	protected $_policies = [];
	/**
	 * @var \JV\JSONPolicies @todo doc
	 */
	protected $_policiesValidator = false;
	/**
	 * @var string Name of the first type to check.
	 */
	protected $_root = false;
	/**
	 * @var mixed[string] This is the loaded specification as is.
	 */
	protected $_specs = false;
	/**
	 * @var string Physical location of the specification.
	 */
	protected $_specsPath = false;
	/**
	 * @var mixed[string] List of all loaded types associated to their
	 * configurations.
	 */
	protected $_types = [];
	/**
	 * @var string[] List of all non primitive types mentioned across all
	 * type specifications.
	 */
	protected $_usedTypes = [];
	//
	// Magic methods.
	/**
	 * Class constructor.
	 */
	protected function __construct() {
		$this->_policiesValidator = JSONPolicies::Instance();
	}
	//
	// Public methods.
	/**
	 * This method validates a JSON string against the loaded specification.
	 *
	 * @param string $jsonString JSON string to validate.
	 * @param mixed[string] $info Extra information about the validation.
	 * @return boolean Returns TRUE if the JSON string is valid.
	 * @throws \JSONValidatorException
	 */
	public function validate($jsonString, &$info = false) {
		$ok = true;
		//
		// Initializing the extra information structure.
		$info = [
			JV_FIELD_ERROR => false,
			JV_FIELD_ERRORS => [],
		];
		//
		// Loading JSON string.
		$json = json_decode($jsonString);
		//
		// Checking for syntax errors.
		if(!$json) {
			$ok = false;
			$info[JV_FIELD_ERRORS][] = [
				JV_FIELD_MESSAGE => 'The give JSON is not valid. ['.json_last_error().'] '.json_last_error_msg()
			];
		} else {
			//
			// Validating the main field.
			$ok = $this->validateType($json, '/', $this->_root[JV_FIELD_TYPE], $info[JV_FIELD_ERRORS]);
		}
		//
		// Getting the most important error.
		if(count($info[JV_FIELD_ERRORS])) {
			$info[JV_FIELD_ERROR] = $info[JV_FIELD_ERRORS][0];
		}

		return $ok;
	}
	/**
	 * This method is an alias for 'validate()' that takes a JSON file path
	 * instead of a JSON string.
	 *
	 * @param string $path Absolute path of a JSON file to validate.
	 * @param mixed[string] $info Extra information about the validation.
	 * @return boolean Returns TRUE if the file's contents are valid.
	 * @throws \JSONValidatorException
	 */
	public function validatePath($path, &$info = false) {
		$ok = false;
		//
		// Checking if the path is a valid file and readable.
		if(!is_file($path)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$path}' is not a file.");
		} elseif(!is_readable($path)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$path}' is not readable.");
		} else {
			//
			// Forwarding checks.
			$ok = $this->validate(file_get_contents($path), $info);
		}

		return $ok;
	}
	//
	// Protected methods.
	/**
	 * This method takes a type specified as a string and transforms it into a
	 * explained type structure.
	 *
	 * @param string $typeName Name of the field or type to expand.
	 * @param string $typeString Specification string.
	 * @param boolean $isField When TRUE this method expands a field's type
	 * specification, otherwise, expands a type's type specification.
	 * @return mixed[string] Returns a structure that reflex important aspects
	 * of a type string.
	 * @throws \JSONValidatorException
	 */
	protected function expandType($typeName, $typeString, $isField = true) {
		//
		// Default values.
		$out = [];
		$reqMatch = false;
		//
		// Checking what kind of analysis should be done, field's type or
		// type's type.
		if($isField) {
			//
			// Checking if it's a type's type specification.
			if(!preg_match(self::$_PatternFieldType, $typeString, $reqMatch)) {
				throw new JSONValidatorException(__CLASS__.": Type '{$typeName}' is not well defined.");
			}
			//
			// Loading information from the specification.
			$out[JV_FIELD_REQUIRED] = $reqMatch['required'] == '+';
			$out[JV_FIELD_TYPE] = $reqMatch['type'];
		} else {
			//
			// Checking if it's a simple alias specification or
			// something else.
			if(!preg_match(self::$_PatternTypeAliases, $typeString, $reqMatch)) {
				//
				// 'Something else' could be a regular expression.
				//
				// Note: The use of '@' is bad, but it seems to be
				// the only way to validate a regular expression,
				// unless you want to do something also bad.
				if(@preg_match($typeString, null) === false) {
					throw new JSONValidatorException(__CLASS__.": Type '{$typeName}' is not well defined.");
				} else {
					//
					// This is a regular expression.
					$out[JV_FIELD_TYPE] = JV_PRIMITIVE_TYPE_REGEXP;
					$out[JV_FIELD_REGEXP] = $typeString;
				}
			} else {
				//
				// Loading information from the specification.
				$out[JV_FIELD_TYPE] = $reqMatch['name'];
			}
		}
		//
		// Checking if this is related to a primitive type.
		$out[JV_FIELD_PRIMITIVE] = in_array($out[JV_FIELD_TYPE], self::$_PrimitiveTypes);
		//
		// Loading type modifiers on aliases.
		$out[JV_FIELD_MODS] = isset($reqMatch['mods']) ? $reqMatch['mods'] : false;
		//
		// Expanding the information of known modifiers.
		switch($out[JV_FIELD_MODS]) {
			case '[]':
				$out[JV_FIELD_CONTAINER] = JV_CONTAINER_TYPE_ARRAY;
				break;
			case '{}':
				$out[JV_FIELD_CONTAINER] = JV_CONTAINER_TYPE_OBJECT;
				break;
			default:
				$out[JV_FIELD_CONTAINER] = false;
		}
		//
		// Counting it as an used type.
		$this->_usedTypes[] = $out[JV_FIELD_TYPE];

		return $out;
	}
	/**
	 * This method takes a JSON specification file, loads its contents and
	 * parses the specification.
	 *
	 * @throws JSONValidatorException
	 */
	protected function load() {
		//
		// Checking for mandatory fields.
		foreach(['types', 'root'] as $field) {
			if(!isset($this->_specs->{$field})) {
				throw new JSONValidatorException(__CLASS__.": Specification has no field '{$field}'.");
			}
		}
		//
		// Field 'types' should be an object.
		if(!is_object($this->_specs->types)) {
			throw new JSONValidatorException(__CLASS__.": Specification field 'types' is not an object.");
		}
		//
		// Checking and loading each non primitive type specification.
		foreach($this->_specs->types as $typeName => $typeConf) {
			$aux = [];
			//
			// If the configuration is an object, this type will be an
			// object with a specific list of fields.
			// If it's an array, it will be a list of possibles types.
			// If it's a string it may be something else.
			if(is_object($typeConf)) {
				$aux[JV_FIELD_STYPE] = JV_STYPE_STRUCTURE;
				//
				// Loading each known field of the structure.
				$aux[JV_FIELD_FIELDS] = [];
				foreach($typeConf as $fieldName => $fieldType) {
					$aux[JV_FIELD_FIELDS][$fieldName] = $this->expandType($typeName, $fieldType);
				}
			} elseif(is_array($typeConf)) {
				$aux[JV_FIELD_STYPE] = JV_STYPE_TYPES_LIST;
				//
				// Loading each possible type.
				$aux[JV_FIELD_TYPES] = [];
				foreach($typeConf as $type) {
					$aux[JV_FIELD_TYPES][] = $type;
					//
					// Counting it as an used type.
					$this->_usedTypes[] = $type;
				}
			} elseif(is_string($typeConf)) {
				//
				// Expanding type
				$aux[JV_FIELD_TYPE] = $this->expandType($typeName, $typeConf, false);
				//
				// Checking if it's just an alias or a regular
				// expression checker.
				$aux[JV_FIELD_STYPE] = $aux[JV_FIELD_TYPE][JV_FIELD_TYPE] == JV_PRIMITIVE_TYPE_REGEXP ? JV_STYPE_REGEXP : JV_STYPE_ALIAS;
			} else {
				throw new JSONValidatorException(__CLASS__.": Type '{$typeName}' is not well defined.");
			}
			//
			// Adding this type to the list.
			$this->_types[$typeName] = $aux;
		}
		//
		// Loading root type.
		if(is_string($this->_specs->root)) {
			$this->_root = $this->expandType('ROOT', $this->_specs->root, false);
			//
			// Counting it as an used type.
			$this->_usedTypes[] = $this->_specs->root;
		} else {
			throw new JSONValidatorException(__CLASS__.": Root type is not well defined.");
		}
		//
		// Clearing used types list.
		$this->_usedTypes = array_values(array_diff(array_unique($this->_usedTypes), self::$_PrimitiveTypes, self::$_PrimitiveSpecialTypes));
		//
		// Validating known types.
		$this->validateUsedTypes();
		//
		// Loading policies.
		$this->loadPolicies();
	}
	/**
	 * This method loads specifications from a file.
	 *
	 * @param string $path Abosulte path from where to load an specification.
	 * @throws \JSONValidatorException
	 */
	protected function loadPath($path) {
		//
		// Saving the path
		$this->_specsPath = $path;
		//
		// Checking if the JSON specification file is actually a file and
		// readable.
		if(!is_file($this->_specsPath)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$this->_specsPath}' is not a file.");
		} elseif(!is_readable($this->_specsPath)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$this->_specsPath}' is not readable.");
		} else {
			$this->loadSpec(file_get_contents($this->_specsPath));
		}
	}
	/**
	 * @todo doc
	 *
	 * @throws \JSONValidatorException
	 */
	protected function loadPolicies() {
		//
		// Checking if there are policies.
		if(isset($this->_specs->policies)) {
			//
			// Loading each policy set.
			foreach($this->_specs->policies as $name => $conf) {
				//
				// Checking if the affected type exists.
				if(isset($this->_types[$name])) {
					$policy = [];
					foreach($conf as $k => $v) {
						$policy[$k] = $v;
					}
					$this->_policies[$name] = $policy;
				} else {
					throw new JSONValidatorException(__CLASS__.": Policy defined for an unknown type named '{$name}'");
				}
			}
			//
			// Searching for unknown policies.
			$knownPolicies = JSONPolicies::KnownPolicies();
			foreach($this->_policies as $name => $policies) {
				$typeSpec = $this->_types[$name];

				$policyType = false;
				switch($typeSpec[JV_FIELD_STYPE]) {
					case JV_STYPE_ALIAS:
						if($typeSpec[JV_FIELD_TYPE][JV_FIELD_CONTAINER]) {
							$policyType = self::$_PolicyTypeTranslations[$typeSpec[JV_FIELD_TYPE][JV_FIELD_CONTAINER]];
						} else {
							if($typeSpec[JV_FIELD_TYPE][JV_FIELD_PRIMITIVE]) {
								$policyType = $typeSpec[JV_FIELD_TYPE][JV_FIELD_TYPE];
							} else {
								throw new JSONValidatorException(__CLASS__.": Alias policies can only applied when it points to a primitive type (type: '{$name}').");
							}
						}
						break;
					case JV_STYPE_STRUCTURE:
						$policyType = JV_STYPE_STRUCTURE;
						break;
					case JV_STYPE_TYPES_LIST:
					case JV_STYPE_REGEXP:
					default:
						throw new JSONValidatorException(__CLASS__.": There are no known policies for a '{$typeSpec[JV_FIELD_STYPE]}' type specification (type: '{$name}').");
				}

				if($policyType) {
					foreach($policies as $policy => $mods) {
						if(!isset($knownPolicies[$policyType]) || !in_array($policy, $knownPolicies[$policyType])) {
							throw new JSONValidatorException(__CLASS__.": Uknwon policy '{$policy}' for type '{$policyType}' (type: '{$name}').");
						}
					}
				}
			}
		}
	}
	/**
	 * This method loads specifications from a string.
	 *
	 * @param string $jsonString Specification as a string.
	 * @throws \JSONValidatorException
	 */
	protected function loadSpec($jsonString) {
		//
		// Reading and parsing the content.
		$this->_specs = json_decode($jsonString);
		//
		// Checking for syntax errors.
		if(!$this->_specs) {
			throw new JSONValidatorException(__CLASS__.": Path '{$this->_specsPath}' is not a valid JSON file. [".json_last_error().'] '.json_last_error_msg());
		}
		//
		// Attempting to load specs.
		$this->load();
	}
	/**
	 * This method validates a field's value as if it were a list of other
	 * items and forwards validations for each one.
	 *
	 * @param mixed $json Field value to check.
	 * @param string $path Virtual path where this value is located.
	 * @param mixed[string] $typeSpec Internal specification for the type to check.
	 * @param mixed[string] $errors List of found errors (in/out parameters).
	 * @return boolean Returns TRUE if all of its items match.
	 * @throws \JSONValidatorException
	 */
	protected function validateContainer($json, $path, $typeSpec, &$errors) {
		//
		// Default values.
		$ok = true;
		$subPath = "{$path}/?";
		$containerType = $typeSpec[JV_FIELD_TYPE][JV_FIELD_CONTAINER];
		//
		// Checking if it's a valid container type.
		if(!in_array($containerType, self::$_ContainerTypes)) {
			$errors[] = [
				JV_FIELD_MESSAGE => "Field at '{$path}' is not a valid container."
			];
			$ok = false;
		} elseif(!is_array($json) && !is_object($json)) {
			$errors[] = [
				JV_FIELD_MESSAGE => "Field at '{$path}' is not a container."
			];
			$ok = false;
		} elseif($containerType == JV_CONTAINER_TYPE_OBJECT && !is_object($json)) {
			$errors[] = [
				JV_FIELD_MESSAGE => "Field at '{$path}' is a container but not an object."
			];
			$ok = false;
		} elseif($containerType == JV_CONTAINER_TYPE_ARRAY && !is_array($json)) {
			$errors[] = [
				JV_FIELD_MESSAGE => "Field at '{$path}' is a container but not an array."
			];
			$ok = false;
		} else {
			//
			// Checking each entry.
			foreach($json as $key => $value) {
				//
				// Building the right path for further logs.
				switch($containerType) {
					case JV_CONTAINER_TYPE_OBJECT:
						$subPath = "{$path}/{$key}";
						break;
					case JV_CONTAINER_TYPE_ARRAY:
						$subPath = "{$path}[{$key}]";
						break;
				}
				//
				// Forwarding the validation for the current item.
				if(!$this->validateType($value, $subPath, $typeSpec[JV_FIELD_TYPE][JV_FIELD_TYPE], $errors)) {
					$ok = false;
					break;
				}
			}
		}

		return $ok;
	}
	/**
	 * This method validates a field's value based on a type name assuming
	 * it's a primitive type value.
	 *
	 * @param string $value Value to validate.
	 * @param string $type Primitive type name to validate.
	 * @return boolean Returns TRUE if it matches.
	 */
	protected function validatePrimitive($value, $type) {
		$ok = false;

		switch($type) {
			case JV_PRIMITIVE_TYPE_ARRAY:
				$ok = is_array($value);
				break;
			case JV_PRIMITIVE_TYPE_BOOLEAN:
				$ok = is_bool($value);
				break;
			case JV_PRIMITIVE_TYPE_FLOAT:
				$ok = is_float($value);
				break;
			case JV_PRIMITIVE_TYPE_INT:
				$ok = is_int($value);
				break;
			case JV_PRIMITIVE_TYPE_MIXED:
				$ok = true;
				break;
			case JV_PRIMITIVE_TYPE_OBJECT:
				$ok = is_object($value);
				break;
			case JV_PRIMITIVE_TYPE_STRING:
				$ok = is_string($value);
				break;
		}

		return $ok;
	}
	/**
	 * This method validates a field's value against a regular expression.
	 *
	 * @param string $value Value to validate.
	 * @param string $regexp Regular expression string to use as pattern.
	 * @return boolean Returns TRUE if it matches.
	 */
	protected function validateRegExp($value, $regexp) {
		return preg_match($regexp, $value);
	}
	/**
	 * This is the main method to validate a field value against a type.
	 *
	 * @param mixed $json Field value to check.
	 * @param string $path Virtual path where this value is located.
	 * @param string $typeName Type name.
	 * @param mixed[string] $errors List of found errors (in/out parameters).
	 * @return boolean Returns TRUE if it matches.
	 * @throws \JSONValidatorException
	 */
	protected function validateType($json, $path, $typeName, &$errors) {
		$ok = false;
		//
		// Checking if it's a primitive type or not.
		if(in_array($typeName, self::$_PrimitiveTypes)) {
			//
			// Forwarding to a simple validation.
			$ok = $this->validatePrimitive($json, $typeName);
		} else {
			//
			// Type's specification shortcut.
			$typeSpec = $this->_types[$typeName];
			//
			// Checking where this check should be forwarded.
			switch($typeSpec[JV_FIELD_STYPE]) {
				case JV_STYPE_STRUCTURE:
					$ok = $this->validateTypeStructure($json, $path, $typeSpec, $errors);
					//
					// Checking policies.
					if($ok && isset($this->_policies[$typeName])) {
						$subErrors = false;
						$enrichedMods = [
							JV_FIELD_FIELDS => $typeSpec[JV_FIELD_FIELDS],
							JV_FIELD_MODS => false
						];
						foreach($this->_policies[$typeName] as $policy => $mods) {
							$enrichedMods[JV_FIELD_MODS] = $mods;
							if(!$this->_policiesValidator->check($json, JV_STYPE_STRUCTURE, $policy, $enrichedMods, $subErrors)) {
								$ok = false;
								$errors[] = [
									JV_FIELD_MESSAGE => "Field at '{$path}' doesn't respect its policies. {$subErrors[JV_FIELD_ERROR]}"
								];
								break;
							}
						}
					}
					break;
				case JV_STYPE_TYPES_LIST:
					$ok = $this->validateTypeList($json, $path, $typeSpec, $errors);
					break;
				case JV_STYPE_REGEXP:
					$ok = $this->validateRegExp($json, $typeSpec[JV_FIELD_TYPE][JV_FIELD_REGEXP]);
					if(!$ok) {
						$errors[] = [
							JV_FIELD_MESSAGE => "Field at '{$path}' does not match the pattern '{$typeSpec[JV_FIELD_TYPE][JV_FIELD_REGEXP]}'."
						];
					}
					break;
				case JV_STYPE_ALIAS:
					$policyType = false;
					//
					// Checking if this alias is a container.
					if($typeSpec[JV_FIELD_TYPE][JV_FIELD_CONTAINER]) {
						$policyType = self::$_PolicyTypeTranslations[$typeSpec[JV_FIELD_TYPE][JV_FIELD_CONTAINER]];
						$ok = $this->validateContainer($json, $path, $typeSpec, $errors);
					} else {
						$policyType = $typeSpec[JV_FIELD_TYPE][JV_FIELD_TYPE];
						$ok = $this->validateTypeAlias($json, $path, $typeSpec, $errors);
					}
					//
					// Checking policies.
					if($ok && isset($this->_policies[$typeName])) {
						$subErrors = false;
						foreach($this->_policies[$typeName] as $policy => $mods) {
							if(!$this->_policiesValidator->check($json, $policyType, $policy, $mods, $subErrors)) {
								$ok = false;
								$errors[] = [
									JV_FIELD_MESSAGE => "Field at '{$path}' doesn't respect its policies. {$subErrors[JV_FIELD_ERROR]}"
								];
								break;
							}
						}
					}
					break;
			}
		}
		//
		// Adding a generic error information.
		if(!$ok) {
			$errors[] = [
				JV_FIELD_MESSAGE => "The type of field at {$path} is not {$typeName}."
			];
		}

		return $ok;
	}
	/**
	 * This method forwards the validation of a field's value to another type
	 * because the current one is just an alias.
	 *
	 * @param mixed $json Field value to check.
	 * @param string $path Virtual path where this value is located.
	 * @param mixed[string] $typeSpec Internal specification for the type to check.
	 * @param mixed[string] $errors List of found errors (in/out parameters).
	 * @return boolean Returns TRUE if it matches.
	 * @throws \JSONValidatorException
	 */
	protected function validateTypeAlias($json, $path, $typeSpec, &$errors) {
		return $this->validateType($json, $path, $typeSpec[JV_FIELD_TYPE][JV_FIELD_TYPE], $errors);
	}
	/**
	 * This method validates a field's value against a list of types until one
	 * of them matches.
	 *
	 * @param mixed $json Field value to check.
	 * @param string $path Virtual path where this value is located.
	 * @param mixed[string] $typeSpec Internal specification for the type to check.
	 * @param mixed[string] $errors List of found errors (in/out parameters).
	 * @return boolean Returns TRUE if the value matches one of the listed
	 * types.
	 * @throws \JSONValidatorException
	 */
	protected function validateTypeList($json, $path, $typeSpec, &$errors) {
		//
		// Default errors.
		$ok = false;
		$subErrors = [];
		//
		// Checking all types in the list until one matches.
		foreach($typeSpec[JV_FIELD_TYPES] as $typeName) {
			if($this->validateType($json, $path, $typeName, $subErrors)) {
				$ok = true;
				break;
			}
		}
		//
		// Checking if a type matched, otherwise an error should be
		// attached.
		if(!$ok) {
			$errors[] = [
				JV_FIELD_MESSAGE => "Wrong type at '{$path}' (allowed types '".implode("', '", $typeSpec[JV_FIELD_TYPES])."').",
				JV_FIELD_ERRORS => $subErrors
			];
		}

		return $ok;
	}
	/**
	 * This method validates a field's value against a specific structure.
	 *
	 * @param mixed $json Field value to check.
	 * @param string $path Virtual path where this value is located.
	 * @param mixed[string] $typeSpec Internal specification for the type to check.
	 * @param mixed[string] $errors List of found errors (in/out parameters).
	 * @return boolean Returns TRUE if it matches.
	 * @throws \JSONValidatorException
	 */
	protected function validateTypeStructure($json, $path, $typeSpec, &$errors) {
		$ok = true;
		//
		// Basic check.
		if(is_object($json)) {
			//
			// Checking each known field against the field's value.
			foreach($typeSpec[JV_FIELD_FIELDS] as $fieldName => $fieldConf) {
				//
				// Checking if it's present.
				if(isset($json->{$fieldName})) {
					if(!$this->validateType($json->{$fieldName}, "{$path}/{$fieldName}", $fieldConf[JV_FIELD_TYPE], $errors)) {
						$ok = false;
						break;
					}
				} else {
					//
					// If it's not present and it should, this check
					// failes and an error is attached.
					if($fieldConf[JV_FIELD_REQUIRED]) {
						$errors[] = [
							JV_FIELD_MESSAGE => "Required field at '{$path}/{$fieldName}' is not present."
						];
						$ok = false;
						break;
					}
				}
			}
		} else {
			$errors[] = [
				JV_FIELD_MESSAGE => "Field at '{$path}' is not a structure."
			];
			$ok = false;
		}

		return $ok;
	}
	/**
	 * This method runs some validations on the list of specified types:
	 * 	- Used types that weren't defined.
	 * 	- Defined types that weren't used.
	 *
	 * @throws \JSONValidatorException
	 */
	protected function validateUsedTypes() {
		//
		// Validating undefined types.
		foreach($this->_usedTypes as $name) {
			if(!isset($this->_types[$name])) {
				throw new JSONValidatorException(__CLASS__.": Type '{$name}' is used but not defined.");
			}
		}
		//
		// Validating unused types.
		foreach($this->_types as $name => $conf) {
			if(!in_array($name, $this->_usedTypes)) {
				throw new JSONValidatorException(__CLASS__.": Type '{$name}' is defined but not used.");
			}
		}
	}
	//
	// Public class methods.
	/**
	 * This factory method creates a validator for each requested path. If it
	 * was already requested it won't reload and re-analyze the specification.
	 *
	 * @param string $path Abosulte path from where to load an specification.
	 * @return JSONValidator Fully loaded validator.
	 * @throws \JSONValidatorException
	 */
	public static function LoadFromFile($path) {
		//
		// Validators cache.
		static $knownValidators = [];
		//
		// Checking if it was already loaded.
		if(!isset($knownValidators[$path])) {
			//
			// Creating a new validator.
			$knownValidators[$path] = new self();
			//
			// Loading...
			$knownValidators[$path]->loadPath($path);
		}
		//
		// Returning the requested validator.
		return $knownValidators[$path];
	}
	/**
	 * This factory method creates a validator based on a specification.
	 *
	 * @param string $jsonString Specification as a string.
	 * @return JSONValidator Fully loaded validator.
	 * @throws \JSONValidatorException
	 */
	public static function LoadFromString($jsonString) {
		//
		// Creating a validator.
		$validator = new self();
		//
		// Loading...
		$validator->loadSpec($jsonString);
		//
		// Returning the requested validator.
		return $validator;
	}
}
