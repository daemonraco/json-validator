<?php

//
// Constants.
define('JV_DIRECTORY_TESTS', __DIR__);
define('JV_DIRECTORY_ASSETS', JV_DIRECTORY_TESTS.'/assets');
define('JV_DIRECTORY_INCLUDES', JV_DIRECTORY_ASSETS.'/includes');
//
// Inclutions
require_once JV_DIRECTORY_INCLUDES.'/functions.php';
require_once JV_DIRECTORY_INCLUDES.'/JSONValidatorScaffold.php';
//
// Loading the library.
require_once JV_DIRECTORY_TESTS.'/../json-validator.php';
