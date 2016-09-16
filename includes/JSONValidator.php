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
	 * @var string @todo doc
	 */
	protected static $_PatternTypeAliases = '/^(?P<name>[a-zA-Z0-9]+)(?P<mods>(\\{\\}|\\[\\])?)$/';
	/**
	 * @var string @todo doc
	 */
	protected static $_PatternFieldType = '/^(?P<required>[+-]?)(?P<type>[a-zA-Z0-9]+)$/';
	//
	// Protected properties.
	/**
	 * @var mixed[string] @todo doc
	 */
	protected $_root = false;
	/**
	 * @var mixed[string] @todo doc
	 */
	protected $_specs = false;
	/**
	 * @var string @todo doc
	 */
	protected $_specsPath = false;
	/**
	 * @var mixed[string] @todo doc
	 */
	protected $_types = [];
	/**
	 * @var string[] @todo doc
	 */
	protected $_usedTypes = [];
	//
	// Magic methods.
	/**
	 * @todo doc
	 *
	 * @param string $path @todo doc
	 */
	public function __construct($path) {
		$this->_specsPath = $path;
		$this->load();
	}
	//
	// Public mehtods.
	/**
	 * @todo doc
	 *
	 * @param string $jsonString @todo doc
	 * @param mixed[string] $info @todo doc
	 * @return boolean @todo doc
	 * @throws \JSONValidatorException
	 */
	public function validate($jsonString, &$info = false) {
		$ok = true;

		$info = [
			JV_FIELD_ERROR => false,
			JV_FIELD_ERRORS => [],
		];

		$json = json_decode($jsonString);
		if(!$json) {
			$ok = false;
			$info[JV_FIELD_ERROR] = '[{'.json_last_error().'}] {'.json_last_error_msg().'}';
		} else {
			$ok = $this->validateType($json, '/', $this->_root[JV_FIELD_TYPE], $info[JV_FIELD_ERRORS]);

			if(count($info[JV_FIELD_ERRORS])) {
				$info[JV_FIELD_ERROR] = $info[JV_FIELD_ERRORS][0];
			}
		}

		return $ok;
	}
	/**
	 * @todo doc
	 *
	 * @param string $path @todo doc
	 * @param mixed[string] $info @todo doc
	 * @return boolean @todo doc
	 * @throws \JSONValidatorException
	 */
	public function validatePath($path, &$info = false) {
		$ok = false;

		if(!is_file($path)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$path}' is not a file.");
		} elseif(!is_readable($path)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$path}' is not readable.");
		} else {
			$ok = $this->validate(file_get_contents($path), $info);
		}

		return $ok;
	}
	//
	// Protected mehtods.
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
				throw new JSONValidatorException(__CLASS__.": Type '{$typeName}' is not well defined.");
			}

			$out[JV_FIELD_TYPE] = $reqMatch['name'];
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
	protected function load() {
		//
		// Loading path.
		if(!is_file($this->_specsPath)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$this->_specsPath}' is not a file.");
		} elseif(!is_readable($this->_specsPath)) {
			throw new JSONValidatorException(__CLASS__.": Path '{$this->_specsPath}' is not readable.");
		} else {
			$this->_specs = json_decode(file_get_contents($this->_specsPath));
			if(!$this->_specs) {
				throw new JSONValidatorException(__CLASS__.": Path '{$this->_specsPath}' is not a valid JSON file. [".json_last_error().'] '.json_last_error_msg());
			}
		}

		foreach($this->_specs->types as $typeName => $typeConf) {
			$aux = [];
			//
			// If the configuration is an object, this type will be an
			// object with a specific list of fields.
			// If it's an array, it will be a list of possibles types.
			// If it's a string it may be something else.
			if(is_object($typeConf)) {
				$aux[JV_FIELD_STYPE] = JV_STYPE_STRUCTURE;
				$aux[JV_FIELD_FIELDS] = [];
				foreach($typeConf as $fieldName => $fieldType) {
					if(!is_string($fieldType)) {
						throw new JSONValidatorException(__CLASS__.": Type '{$typeName}' is not well defined.");
					}

					$expansion = $this->expandType($typeName, $fieldType);
					$aux[JV_FIELD_FIELDS][$fieldName] = $expansion;
					$this->_usedTypes[] = $expansion[JV_FIELD_TYPE];
				}
			} elseif(is_array($typeConf)) {
				$aux[JV_FIELD_STYPE] = JV_STYPE_TYPES_LIST;
				$aux[JV_FIELD_TYPES] = [];
				foreach($typeConf as $type) {
					$aux[JV_FIELD_TYPES][] = $type;
					$this->_usedTypes[] = $fieldType;
				}
			} elseif(is_string($typeConf)) {
				$aux[JV_FIELD_STYPE] = JV_STYPE_ALIAS;
				$expansion = $this->expandType($typeName, $typeConf, false);
				$aux[JV_FIELD_TYPE] = $expansion;
				$this->_usedTypes[] = $expansion[JV_FIELD_TYPE];
			} else {
				throw new JSONValidatorException(__CLASS__.": Type '{$typeName}' is not well defined.");
			}

			$this->_types[$typeName] = $aux;
		}
		//
		// Loading root type.
		if(is_string($this->_specs->root)) {
			$this->_root = $this->expandType('ROOT', $this->_specs->root);
			$this->_usedTypes[] = $this->_specs->root;
		} else {
			throw new JSONValidatorException(__CLASS__.": Root type is not well defined.");
		}
		//
		// Clearing used types list.
		$this->_usedTypes = array_values(array_diff(array_unique($this->_usedTypes), self::$_PrimitiveTypes));
		//
		// Validating known types.
		$this->validateUsedTypes();
	}
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
	protected function validatePrimitive($field, $type) {
		$ok = false;

		switch($type) {
			case JV_PRIMITIVE_TYPE_ARRAY:
				$ok = is_array($field);
				break;
			case JV_PRIMITIVE_TYPE_BOOLEAN:
				$ok = is_bool($field);
				break;
			case JV_PRIMITIVE_TYPE_FLOAT:
				$ok = is_float($field);
				break;
			case JV_PRIMITIVE_TYPE_INT:
				$ok = is_int($field);
				break;
			case JV_PRIMITIVE_TYPE_MIXED:
				$ok = true;
				break;
			case JV_PRIMITIVE_TYPE_OBJECT:
				$ok = is_object($field);
				break;
			case JV_PRIMITIVE_TYPE_STRING:
				$ok = is_string($field);
				break;
		}

		return $ok;
	}
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
				case JV_STYPE_ALIAS:
					if($typeSpec[JV_FIELD_TYPE][JV_FIELD_CONTAINER]) {
						$ok = $this->validateContainer($json, $path, $typeSpec, $errors);
					} else {
						$this->validateTypeAlias($json, $path, $typeSpec, $errors);
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
	protected function validateTypeAlias($json, $path, $typeSpec, &$errors) {
		return $this->validateType($json, $path, $typeSpec[JV_FIELD_TYPE], $errors);
	}
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
				JV_FIELD_MESSAGE => "Wrong type at '{$path}' (allowed types '".implode("', '", $typeSpec[JV_FIELD_TYPES])."')."
			];
		}

		return $ok;
	}
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
	public static function GetValidator($path) {
		static $knwonValidators = [];

		if(!isset($knwonValidators[$path])) {
			$knwonValidators[$path] = new self($path);
		}

		return $knwonValidators[$path];
	}
}
