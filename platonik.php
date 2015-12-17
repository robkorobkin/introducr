<?php
	require_once("platonik-config.php");
	require_once("server/rkdatabase.php");
	require_once("server/platonik_api.php");



		






///////////////////////////////////
	
	$api = new PlatonikAPI($platonik_config);

	$api -> postMessage();

	// LOAD STATIC ASSETS
	if(isset($_GET['lib'])) {
	
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	
		switch($_GET['lib']) {
			
			case "js" : 
				$api -> printJS();
			break;
			
			case "css" : 
				$api -> printCSS();
			break;

		}

		exit();
	}



	$api -> request = $_POST;
	$verb = $api -> request['verb'];
	
	$requiresLogin = ($verb != 'loginUser');
	
	
	$isLoggedIn = (isset($_SESSION['uid']) && $_SESSION['uid'] == $api -> request['uid']);
	
	if( $requiresLogin && !$isLoggedIn){
		$response['error'] = "logged out";
		echo json_encode($response);
		exit();
	}
	
	$api -> uid = $_SESSION['uid'];
	$response = $api -> $verb();
	echo json_encode($response);
