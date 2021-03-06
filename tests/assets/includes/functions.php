<?php

/**
 * Prompt an object in a pretty way and adding some useful info like where it is
 * being called from.
 * 
 * @param type $data Object to be prompt.
 * @param type $final When true, abort the execution after prompting.
 * @param type $specific Uses 'var_dump()' instead of 'print_r()'.
 * @param type $name Adds a title to the seccion where the object is prompted.
 * @param type $showTrace Adds callback trace.
 */
function debugit($data, $final = false, $specific = false, $name = null, $showTrace = false) {
	//
	// Storing data displayed in a buffer for post processing.
	ob_start();
	//
	// When it is specific, it shoud use 'var_dump()'.
	if($specific) {
		var_dump($data);
	} else {
		if(is_bool($data)) {
			// 
			// When it's boolean, should say true or false.
			echo (boolval($data) ? 'true' : 'false')."\n";
		} elseif(is_null($data)) {
			// 
			// When it's null, should NULL.
			echo "NULL\n";
		} elseif(is_object($data) || is_array($data)) {
			// 
			// When it's an object, should 'NULL'print_r()'.
			print_r($data);
		} else {
			//
			// Otherwise, it goes directly.
			echo "{$data}\n";
		}
	}
	//
	// Obtaining caller information.
	$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	$callingLine = array_shift($trace);
	$callerLine = array_shift($trace);
	//
	// When it's requested, a back trace should be promptted.
	if($showTrace) {
		echo "\n";
		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	}
	//
	// Printing information about the location where this function was called.
	echo "\n";
	echo 'At: '.(isset($callerLine['class']) ? "{$callerLine['class']}::" : '')."{$callerLine['function']}() [{$callingLine['file']}:{$callingLine['line']}]\n";
	//
	// Obtaining information from the buffer and closing it.
	$out = ob_get_contents();
	ob_end_clean();

	$out = explode("\n", $out);
	array_walk($out, function(&$item) {
		$item = "| {$item}";
	});
	$out = implode("\n", $out);

	$delim = "------------------------------------------------------";
	if($name) {
		$aux = "+-< {$name} >{$delim}";
		echo substr($aux, 0, strlen($delim) + 1)."\n";
	} else {
		echo "+{$delim}\n";
	}
	echo "{$out}\n";
	echo "+{$delim}\n";
	//
	// If it's final, it show abort after showing the debug information.
	if($final) {
		die;
	}
}
