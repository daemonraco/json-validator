<?php

/**
 * @file define.php
 * @author Alejandro Dario Simi
 */
//
// Generic field names @{
define('JV_FIELD_CONTAINER', 'container');
define('JV_FIELD_ERROR', 'error');
define('JV_FIELD_ERRORS', 'errors');
define('JV_FIELD_FIELD', 'field');
define('JV_FIELD_FIELDS', 'fields');
define('JV_FIELD_FIELD_CONF', 'field-conf');
define('JV_FIELD_MESSAGE', 'message');
define('JV_FIELD_MODS', 'mods');
define('JV_FIELD_PRIMITIVE', 'primitive');
define('JV_FIELD_REGEXP', 'regexp');
define('JV_FIELD_REQUIRED', 'required');
define('JV_FIELD_STYPE', 'spec-type');
define('JV_FIELD_SUBTYPE', 'subtype');
define('JV_FIELD_TYPE', 'type');
define('JV_FIELD_TYPES', 'types');
define('JV_FIELD_TYPES_STRING', 'types-string');
//@}
//
// Primitive types @{
define('JV_PRIMITIVE_TYPE_ARRAY', 'array');
define('JV_PRIMITIVE_TYPE_BOOLEAN', 'boolean');
define('JV_PRIMITIVE_TYPE_FLOAT', 'float');
define('JV_PRIMITIVE_TYPE_INT', 'int');
define('JV_PRIMITIVE_TYPE_MIXED', 'mixed');
define('JV_PRIMITIVE_TYPE_OBJECT', 'object');
define('JV_PRIMITIVE_TYPE_REGEXP', 'regexp');
define('JV_PRIMITIVE_TYPE_STRING', 'string');
//@}
//
// Container types @{
define('JV_CONTAINER_TYPE_ARRAY', JV_PRIMITIVE_TYPE_ARRAY);
define('JV_CONTAINER_TYPE_OBJECT', JV_PRIMITIVE_TYPE_OBJECT);
//@}
//
// Types classes @{
define('JV_STYPE_ALIAS', 'alias');
define('JV_STYPE_TYPES_LIST', 'list');
define('JV_STYPE_REGEXP', 'regexp');
define('JV_STYPE_STRUCTURE', 'structure');
//@}
//
// Policies constants @{
define('JV_POLICY_EXCEPT', 'except');
define('JV_POLICY_MAX', 'max');
define('JV_POLICY_MIN', 'min');
define('JV_POLICY_ONLY', 'only');
define('JV_POLICY_STRICT', 'strict');
//@}
//
// Other constants @{
define('JV_TYPES_SEPARATOR', ',');
define('JV_SUBTYPES_SEPARATOR', ':');
//@}
