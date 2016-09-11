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
		'array',
		'object'
	];
	/**
	 * @var string[] List of all types known as primitive, these don't
	 * require complex checks.
	 */
	protected static $_PrimitiveTypes = [
		'array',
		'boolean',
		'float',
		'int',
		'mixed',
		'object',
		'string'
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
			'error' => false,
			'errors' => [],
		];

		$json = json_decode($jsonString);
		if(!$json) {
			$ok = false;
			$info['error'] = '[{'.json_last_error().'}] {'.json_last_error_msg().'}';
		} else {
			$ok = $this->validateJSON($json, '/', $this->_rootFields, $info);

			if(count($info['errors'])) {
				$info['error'] = $info['errors'][0];
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
			$out['required'] = $match['required'] == '+';
			$out['types-string'] = $match['types'];
			$out['types'] = explode(',', $match['types']);

			foreach($out['types'] as $key => $strSpec) {
				if(preg_match(self::$_TypesDefType, $strSpec, $match)) {
					$aux = [];
					$aux['type'] = $match['type'];
					$aux['subtype'] = isset($match['subtype']) ? $match['subtype'] : false;

					$aux['primitive'] = !$aux['subtype'] && in_array($aux['type'], self::$_PrimitiveTypes);
					$aux['container'] = $aux['subtype'] && in_array($aux['type'], self::$_ContainerTypes);

					$out['types'][$key] = $aux;
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

			$aux['fields'] = [];
			foreach($conf as $field => $fieldConf) {
				$aux['fields'][$field] = $this->expandType($fieldConf);
			}

			$this->_types[$type] = $aux;
		}
		//
		// Validating types.
		$this->validateTypes($this->_rootFields, 'root field');
		foreach($this->_types as $type => $conf) {
			$this->validateTypes($conf['fields'], "type '{$type}' fields");
		}
	}
	protected function validateFieldType($json, $jsonPath, $fieldName, $typeConf, &$info) {
		$matches = true;

		if($typeConf['primitive']) {
			$matches = $this->validatePrimitive($json->{$fieldName}, $typeConf['type']);
		} else {
			$type = $typeConf['container'] ? $typeConf['subtype'] : $typeConf['type'];
			$subPath = '';
			$lastField = false;
			if($typeConf['container']) {
				foreach($json->{$fieldName} as $pos => $subJson) {
					$subPath = "{$jsonPath}{$fieldName}[{$pos}]/";
					if(in_array($type, self::$_PrimitiveTypes)) {
						$matches = $this->validatePrimitive($subJson, $type);
					} else {
						$matches = $this->validateJSON($subJson, $subPath, $this->_types[$type]['fields'], $info);
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
					$matches = $this->validateJSON($json->{$fieldName}, $subPath, $this->_types[$type]['fields'], $info);
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
				foreach($conf['types'] as $typeConf) {
					$matches = $this->validateFieldType($json, $path, $name, $typeConf, $info);
					if($matches) {
						break;
					}
				}
				//
				// Checking if no type matched.
				if(!$matches) {
					$ok = false;
					$info['errors'][] = [
						'message' => "Field at '{$path}{$name}' has a wrong type (allowed types '{$conf['types-string']}').",
						'field-conf' => $conf,
						'field' => $json
					];
					break;
				}
			} else {
				if($conf['required']) {
					$ok = false;
					$info['errors'][] = [
						'message' => "Required field at '{$path}{$name}' is not present.",
						'field-conf' => $conf,
						'field' => $json
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
			case 'array':
				$ok = is_array($field);
				break;
			case 'boolean':
				$ok = is_bool($field);
				break;
			case 'float':
				$ok = is_float($field);
				break;
			case 'int':
				$ok = is_int($field);
				break;
			case 'mixed':
				$ok = true;
				break;
			case 'object':
				$ok = is_object($field);
				break;
			case 'string':
				$ok = is_string($field);
				break;
		}

		return $ok;
	}
	protected function validateTypes($fields, $at) {
		foreach($fields as $name => $conf) {
			foreach($conf['types'] as $typeConf) {
				if(!$typeConf['primitive']) {
					$type = $typeConf['container'] ? $typeConf['subtype'] : $typeConf['type'];
					if(!in_array($type, self::$_PrimitiveTypes) && !isset($this->_types[$type])) {
						throw new JSONValidatorException(__CLASS__.": Field '{$name}' ({$at}) uses an undefiend type called '{$type}'.");
					}
				}
			}
		}
	}
}
