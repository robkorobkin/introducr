<?php
	
	
	require_once("introducr-config.php");
	require_once("server/rkdatabase.php");
	require_once("server/introducr_api.php");



		






///////////////////////////////////
	
	$api = new introducrAPI($introducr_config);

	// LOAD STATIC ASSETS
	if(isset($_GET['lib'])) {
	
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


	$request = $_POST;
	$api -> request = $request;
	$verb = $request['verb'];
	
	$requiresLogin = ($verb != 'loginUser');
	
	
	$isLoggedIn = (isset($_SESSION['uid']) && isset($request['uid']) && $_SESSION['uid'] == $request['uid']);
	
	if( $requiresLogin && !$isLoggedIn){
		$response['error'] = "logged out";
		echo json_encode($response);
		exit();
	}
	
	$api -> uid = $_SESSION['uid'];
	$response = $api -> $verb();
	echo json_encode($response);
