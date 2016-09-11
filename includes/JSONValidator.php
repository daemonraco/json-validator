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
	protected static $_TypesDefRequired = '/^(?P<required>[+-]?)(?P<types>[a-zA-Z0-9\\[\\],]+)$/';
	/**
	 * @var string @todo doc
	 */
	protected static $_TypesDefType = '/^(?P<type>[^\\[]+)((\\[(?P<subtype>.*)\\])?)$/';
	//
	// Protected properties.
	/**
	 * @var mixed[string] @todo doc
	 */
	protected $_rootFields = [];
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
			$ok = $this->validateJSON($json, '/', $this->_rootFields, $info);

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
	/**
	 * @todo doc
	 *
	 * @param string $typeString @todo doc
	 * @return mixed[string] @todo doc
	 * @throws \JSONValidatorException
	 */
	protected function expandType($typeString) {
		$out = [];

		$match = false;
		if(preg_match(self::$_TypesDefRequired, $typeString, $match)) {
			$out[JV_FIELD_REQUIRED] = $match[JV_FIELD_REQUIRED] == '+';
			$out[JV_FIELD_TYPES_STRING] = $match[JV_FIELD_TYPES];
			$out[JV_FIELD_TYPES] = explode(JV_TYPES_SEPARATOR, $match[JV_FIELD_TYPES]);

			foreach($out[JV_FIELD_TYPES] as $key => $strSpec) {
				if(preg_match(self::$_TypesDefType, $strSpec, $match)) {
					$aux = [];
					$aux[JV_FIELD_TYPE] = $match[JV_FIELD_TYPE];
					$aux[JV_FIELD_SUBTYPE] = isset($match[JV_FIELD_SUBTYPE]) ? $match[JV_FIELD_SUBTYPE] : false;

					$aux[JV_FIELD_PRIMITIVE] = !$aux[JV_FIELD_SUBTYPE] && in_array($aux[JV_FIELD_TYPE], self::$_PrimitiveTypes);
					$aux[JV_FIELD_CONTAINER] = $aux[JV_FIELD_SUBTYPE] && in_array($aux[JV_FIELD_TYPE], self::$_ContainerTypes);

					$out[JV_FIELD_TYPES][$key] = $aux;
				} else {
					throw new JSONValidatorException(__CLASS__.": '{$strSpec}' is a wrong type specification.");
				}
			}
		} else {
			throw new JSONValidatorException(__CLASS__.": '{$typeString}' is a wrong type specification.");
		}

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
		//
		// Load fields.
		foreach($this->_specs->fields as $name => $fieldConf) {
			$this->_rootFields[$name] = $this->expandType($fieldConf);
		}
		//
		// Load types.
		foreach($this->_specs->types as $type => $conf) {
			$aux = [];

			if(!is_object($conf) || !count(get_object_vars($conf))) {
				throw new JSONValidatorException(__CLASS__.": Type '{$type}' has a wrong fields specification.");
			}

			$aux[JV_FIELD_FIELDS] = [];
			foreach($conf as $field => $fieldConf) {
				$aux[JV_FIELD_FIELDS][$field] = $this->expandType($fieldConf);
			}

			$this->_types[$type] = $aux;
		}
		//
		// Validating types.
		$this->validateTypes($this->_rootFields, 'root field');
		foreach($this->_types as $type => $conf) {
			$this->validateTypes($conf[JV_FIELD_FIELDS], "type '{$type}' fields");
		}
	}
	protected function validateFieldType($json, $jsonPath, $fieldName, $typeConf, &$info) {
		$matches = true;

		if($typeConf[JV_FIELD_PRIMITIVE]) {
			$matches = $this->validatePrimitive($json->{$fieldName}, $typeConf[JV_FIELD_TYPE]);
		} else {
			$type = $typeConf[JV_FIELD_CONTAINER] ? $typeConf[JV_FIELD_SUBTYPE] : $typeConf[JV_FIELD_TYPE];
			$subPath = '';
			$lastField = false;
			if($typeConf[JV_FIELD_CONTAINER]) {
				foreach($json->{$fieldName} as $pos => $subJson) {
					$subPath = "{$jsonPath}{$fieldName}[{$pos}]/";
					if(in_array($type, self::$_PrimitiveTypes)) {
						$matches = $this->validatePrimitive($subJson, $type);
					} else {
						$matches = $this->validateJSON($subJson, $subPath, $this->_types[$type][JV_FIELD_FIELDS], $info);
					}
					$lastField = $subJson;

					if(!$matches) {
						break;
					}
				}
			} else {
				$subPath = "{$jsonPath}{$fieldName}/";
				if(in_array($type, self::$_PrimitiveTypes)) {
					$matches = $this->validatePrimitive($json->{$fieldName}, $type);
				} else {
					$matches = $this->validateJSON($json->{$fieldName}, $subPath, $this->_types[$type][JV_FIELD_FIELDS], $info);
				}
				$lastField = $json->{$fieldName};
			}
		}

		return $matches;
	}
	protected function validateJSON($json, $path, $fields, &$info) {
		$ok = true;

		foreach($fields as $name => $conf) {
			if(isset($json->{$name})) {
				$matches = false;
				//
				// Checking each possible type on this field.
				foreach($conf[JV_FIELD_TYPES] as $typeConf) {
					$matches = $this->validateFieldType($json, $path, $name, $typeConf, $info);
					if($matches) {
						break;
					}
				}
				//
				// Checking if no type matched.
				if(!$matches) {
					$ok = false;
					$typesStr = "'".implode("', '", explode(JV_TYPES_SEPARATOR, $conf[JV_FIELD_TYPES_STRING]))."'";
					$info[JV_FIELD_ERRORS][] = [
						JV_FIELD_MESSAGE => "Field at '{$path}{$name}' has a wrong type (allowed types: {$typesStr}).",
						JV_FIELD_FIELD_CONF => $conf,
						JV_FIELD_FIELD => $json
					];
					break;
				}
			} else {
				if($conf[JV_FIELD_REQUIRED]) {
					$ok = false;
					$info[JV_FIELD_ERRORS][] = [
						JV_FIELD_MESSAGE => "Required field at '{$path}{$name}' is not present.",
						JV_FIELD_FIELD_CONF => $conf,
						JV_FIELD_FIELD => $json
					];
					break;
				}
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
	protected function validateTypes($fields, $at) {
		foreach($fields as $name => $conf) {
			foreach($conf[JV_FIELD_TYPES] as $typeConf) {
				if(!$typeConf[JV_FIELD_PRIMITIVE]) {
					$type = $typeConf[JV_FIELD_CONTAINER] ? $typeConf[JV_FIELD_SUBTYPE] : $typeConf[JV_FIELD_TYPE];
					if(!in_array($type, self::$_PrimitiveTypes) && !isset($this->_types[$type])) {
						throw new JSONValidatorException(__CLASS__.": Field '{$name}' ({$at}) uses an undefiend type called '{$type}'.");
					}
				}
			}
		}
	}
}
