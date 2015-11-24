<?php

	$config = array();
	if(strpos(getCwd(), "machigonne") !== false) $environment = "dev";
	else $environment = "local";
	
	switch($environment) {
		case "dev" : 
			$config["database"] = array(
				"servername" => "localhost"
			);
			$config["appId"] = '1079145595453451';
		break;

		case "local" : 
			$config["database"] = array(
				"servername" => "localhost", 
				"username" => "root", 
				"password" => "root",
				"database" => "platonik"
			);
			$config["appId"] = '1079145595453451';
		break;
	}
	
	$platonik_config = $config;