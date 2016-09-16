<?php

/**
 * @file JSONValidator.php
 * @author Alejandro Dario Simi
 */

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
	 *
	 * @param string $path Abosulte path from where to load an specification.
	 * @throws \JSONValidatorException
	 */
	protected function __construct($path) {
		//
		// Saving the path
		$this->_specsPath = $path;
		//
		// Attepting to load specs.
		$this->load();
	}
	//
	// Public mehtods.
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
		// Initializing the extra information strcuture.
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
			// Forwaring checks.
			$ok = $this->validate(file_get_contents($path), $info);
		}

		return $ok;
	}
	//
	// Protected mehtods.
	/**
	 * @todo doc
	 *
	 * @param type $typeName @todo doc
	 * @param type $typeString @todo doc
	 * @param type $isField @todo doc
	 * @return boolean @todo doc
	 * @throws \JSONValidatorException
	 */
	protected function expandType($typeName, $typeString, $isField = true) {
		$out = [];

		$reqMatch = false;
		if($isField) {
			if(!preg_match(self::$_PatternFieldType, $typeString, $reqMatch)) {
				throw new JSONValidatorException(__CLASS__.": Type '{$typeName}' is not well defined.");
			}

			$out[JV_FIELD_REQUIRED] = $reqMatch['required'] == '+';
			$out[JV_FIELD_TYPE] = $reqMatch['type'];
		} else {
			if(!preg_match(self::$_PatternTypeAliases, $typeString, $reqMatch)) {
				if(@preg_match($typeString, null) === false) {
					throw new JSONValidatorException(__CLASS__.": Type '{$typeName}' is not well defined.");
				} else {
					//
					// This is a regular expresion.
					$out[JV_FIELD_TYPE] = JV_PRIMITIVE_TYPE_REGEXP;
					$out[JV_FIELD_REGEXP] = $typeString;
				}
			} else {
				$out[JV_FIELD_TYPE] = $reqMatch['name'];
			}
		}

		$out[JV_FIELD_PRIMITIVE] = in_array($out[JV_FIELD_TYPE], self::$_PrimitiveTypes);

		$out[JV_FIELD_MODS] = isset($reqMatch['mods']) ? $reqMatch['mods'] : false;
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
		// Checking if the JSON specification file is actually a file and
		// readable.
		if(!is_file($this->_specsPath)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$this->_specsPath}' is not a file.");
		} elseif(!is_readable($this->_specsPath)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$this->_specsPath}' is not readable.");
		} else {
			//
			// Reading and parsing the content.
			$this->_specs = json_decode(file_get_contents($this->_specsPath));
			//
			// Checking for syntax errors.
			if(!$this->_specs) {
				throw new JSONValidatorException(__CLASS__.": Path '{$this->_specsPath}' is not a valid JSON file. [".json_last_error().'] '.json_last_error_msg());
			}
		}
		//
		// Checking for mandatory fields.
		foreach(['types', 'root'] as $field) {
			if(!isset($this->_specs->{$field})) {
				throw new JSONValidatorException(__CLASS__.": Specification has no field '{$field}'.");
			}
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
	}
	/**
	 * @todo doc
	 *
	 * @param type $json @todo doc
	 * @param type $path @todo doc
	 * @param type $typeSpec @todo doc
	 * @param type $errors @todo doc
	 * @return boolean @todo doc
	 * @throws \JSONValidatorException
	 */
	protected function validateContainer($json, $path, $typeSpec, &$errors) {
		$ok = true;

		$subPath = "{$path}/?";
		$containerType = $typeSpec[JV_FIELD_TYPE][JV_FIELD_CONTAINER];
		foreach($json as $key => $value) {
			switch($containerType) {
				case JV_CONTAINER_TYPE_OBJECT:
					$subPath = "{$path}/{$key}";
					break;
				case JV_CONTAINER_TYPE_ARRAY:
					$subPath = "{$path}[{$key}]";
					break;
			}

			if(!$this->validateType($value, $subPath, $typeSpec[JV_FIELD_TYPE][JV_FIELD_TYPE], $errors)) {
				$ok = false;
				break;
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
	 * @param string $regexp Regular expresion string to use as pattern.
	 * @return boolean Returns TRUE if it matches.
	 */
	protected function validateRegExp($value, $regexp) {
		return preg_match($regexp, $value);
	}
	/**
	 * @todo doc
	 *
	 * @param type $json @todo doc
	 * @param type $path @todo doc
	 * @param type $typeName @todo doc
	 * @param type $errors @todo doc
	 * @return type @todo doc
	 * @throws \JSONValidatorException
	 */
	protected function validateType($json, $path, $typeName, &$errors) {
		$ok = false;

		if(in_array($typeName, self::$_PrimitiveTypes)) {
			$ok = $this->validatePrimitive($json, $typeName);
		} else {
			$typeSpec = $this->_types[$typeName];

			switch($typeSpec[JV_FIELD_STYPE]) {
				case JV_STYPE_STRUCTURE:
					$ok = $this->validateTypeStructure($json, $path, $typeSpec, $errors);
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
					if($typeSpec[JV_FIELD_TYPE][JV_FIELD_CONTAINER]) {
						$ok = $this->validateContainer($json, $path, $typeSpec, $errors);
					} else {
						$ok = $this->validateTypeAlias($json, $path, $typeSpec, $errors);
					}
					break;
			}
		}
		if(!$ok) {
			$errors[] = [
				JV_FIELD_MESSAGE => "The type of field at {$path} is not {$typeName}."
			];
		}

		return $ok;
	}
	/**
	 * @todo doc
	 *
	 * @param type $json @todo doc
	 * @param type $path @todo doc
	 * @param type $typeSpec @todo doc
	 * @param type $errors @todo doc
	 * @return type @todo doc
	 * @throws \JSONValidatorException
	 */
	protected function validateTypeAlias($json, $path, $typeSpec, &$errors) {
		return $this->validateType($json, $path, $typeSpec[JV_FIELD_TYPE][JV_FIELD_TYPE], $errors);
	}
	/**
	 * @todo doc
	 *
	 * @param type $json @todo doc
	 * @param type $path @todo doc
	 * @param type $typeSpec @todo doc
	 * @param type $errors @todo doc
	 * @return boolean @todo doc
	 * @throws \JSONValidatorException
	 */
	protected function validateTypeList($json, $path, $typeSpec, &$errors) {
		$ok = false;

		$subErrors = [];
		foreach($typeSpec[JV_FIELD_TYPES] as $typeName) {
			if($this->validateType($json, $path, $typeName, $subErrors)) {
				$ok = true;
				break;
			}
		}
		if(!$ok) {
			$errors[] = [
				JV_FIELD_MESSAGE => "Wrong type at '{$path}' (allowed types '".implode("', '", $typeSpec[JV_FIELD_TYPES])."').",
				JV_FIELD_ERRORS => $subErrors
			];
		}

		return $ok;
	}
	/**
	 * @todo doc
	 *
	 * @param type $json @todo doc
	 * @param type $path @todo doc
	 * @param type $typeSpec @todo doc
	 * @param type $errors @todo doc
	 * @return boolean @todo doc
	 * @throws \JSONValidatorException
	 */
	protected function validateTypeStructure($json, $path, $typeSpec, &$errors) {
		$ok = true;

		foreach($typeSpec[JV_FIELD_FIELDS] as $fieldName => $fieldConf) {
			if(isset($json->{$fieldName})) {
				if(!$this->validateType($json->{$fieldName}, "{$path}/{$fieldName}", $fieldConf[JV_FIELD_TYPE], $errors)) {
					$ok = false;
					break;
				}
			} else {
				if($fieldConf[JV_FIELD_REQUIRED]) {
					$errors[] = [
						JV_FIELD_MESSAGE => "Requiered field at '{$path}/{$fieldName}' is not present."
					];
					$ok = false;
					break;
				}
			}
		}

		return $ok;
	}
	/**
	 * @todo doc
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
	 * was already requested it won't reload and re-analyse the specification.
	 *
	 * @param string $path Abosulte path from where to load an specification.
	 * @return JSONValidator Fully loaded validator.
	 * @throws \JSONValidatorException
	 */
	public static function GetValidator($path) {
		//
		// Validators cache.
		static $knwonValidators = [];
		//
		// Checking if it was already loaded.
		if(!isset($knwonValidators[$path])) {
			//
			// Creating a new validator.
			$knwonValidators[$path] = new self($path);
		}
		//
		// Returning the requeste validator.
		return $knwonValidators[$path];
	}
}
