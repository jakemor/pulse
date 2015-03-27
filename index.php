<?php
	
	// check for debuging mode

	require 'settings.php'; 
	if ($error_reporting) {
		ini_set('display_errors',1);  error_reporting(E_ALL);
	}

	header('Content-Type: application/json');

	// only experts edit below

	require 'app/models.php';
	require 'app/controllers.php';
	// require 'app/helpers.php';

	date_default_timezone_set($timezone); 

	if (isset($_GET['args']) && $_GET['args'] != "") {
		$args = explode( '/', $_GET['args']);
		if (function_exists($args[0]) && "_" != substr($args[0], 0,1)) {
			$controller = $args[0]; 
			unset($args[0]);
			if (sizeof($args) > 0) {
				if ($args[sizeof($args)] == "") {
					unset($args[sizeof($args)]);
				}
			}
			$params = array_values($args);

			$_GET = []; 

			foreach ($params as $value) {
				$pair = explode("=", $value); 
				$_GET[$pair[0]] = $pair[1]; 
			}
			$controller();
		} else {
			$notfound_view_controller(); 
		}
	} else {
		$default_view_controller(); 
	}
	exit;
?>