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
 */
class JSONValidator {
	//
	// Protected class properties.
	protected static $_ContainerTypes = [
		'array',
		'object'
	];
	protected static $_PrimitiveTypes = [
		'array',
		'boolean',
		'float',
		'int',
		'mixed',
		'object',
		'string'
	];
	protected static $_TypeDefPatter = '/^(?P<required>[+-]?)(?P<type>([a-zA-Z0-9]+))((\\[(?P<subtype>.*)\\])?)$/';
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

		$info = [];

		$json = json_decode($jsonString);
		if(!$json) {
			$ok = false;
			$info['error'] = '[{'.json_last_error().'}] {'.json_last_error_msg().'}';
		} else {
			$ok = $this->validateJSON($json, $this->_rootFields, '/', $info);
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
		if(preg_match(self::$_TypeDefPatter, $typeString, $match)) {
			$out['required'] = $match['required'] == '+';
			$out['type'] = $match['type'];
			$out['subtype'] = isset($match['subtype']) ? $match['subtype'] : false;

			$out['primitive'] = !$out['subtype'] && in_array($out['type'], self::$_PrimitiveTypes);
			$out['container'] = $out['subtype'] && in_array($out['type'], self::$_ContainerTypes);
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
	public function validateJSON($json, $fields, $path, &$info) {
		$ok = true;

		foreach($fields as $name => $conf) {
			if(isset($json->{$name})) {
				if($conf['primitive']) {
					$ok = $this->validatePrimitive($json->{$name}, $conf['type']);

					if(!$ok) {
						$info['error'] = "Field at '{$path}{$name}' is not of type '{$conf['type']}'.";
						$info['field-conf'] = $conf;
					}
				} else {
					$type = $conf['container'] ? $conf['subtype'] : $conf['type'];
					$subPath = '';
					$lastField = false;
					if($conf['container']) {
						foreach($json->{$name} as $pos => $subJson) {
							$subPath = "{$path}{$name}[{$pos}]/";
							if(in_array($type, self::$_PrimitiveTypes)) {
								$ok = $this->validatePrimitive($subJson, $type);
							} else {
								$ok = $this->validateJSON($subJson, $this->_types[$type]['fields'], $subPath, $info);
							}
							$lastField = $subJson;

							if(!$ok) {
								break;
							}
						}
					} else {
						$subPath = "{$path}{$name}/";
						if(in_array($type, self::$_PrimitiveTypes)) {
							$ok = $this->validatePrimitive($json->{$name}, $type);
						} else {
							$ok = $this->validateJSON($json->{$name}, $this->_types[$type]['fields'], $subPath, $info);
						}
						$lastField = $json->{$name};
					}
					if(!$ok) {
						$info['error'] = "Field at '{$subPath}' is not of type '{$type}'.";
						$info['field-conf'] = $conf;
						$info['field'] = $lastField;
						break;
					}
				}
			} else {
				if($conf['required']) {
					$ok = false;
					$info['error'] = "Required field at '{$path}{$name}' is not present.";
					$info['field-conf'] = $conf;
					$info['field'] = $json;
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
			if(!$conf['primitive']) {
				$type = $conf['container'] ? $conf['subtype'] : $conf['type'];
				if(!in_array($type, self::$_PrimitiveTypes) && !isset($this->_types[$type])) {
					throw new JSONValidatorException(__CLASS__.": Field '{$name}' ({$at}) uses an undefiend type called '{$type}'.");
				}
			}
		}
	}
	//
	// Public class mehtods.
}
