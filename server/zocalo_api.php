<?php
	
	
	require_once("model/zocalo-config.php");
	require_once("model/framework/rkdatabase.php");
	require_once("model/zocalo_model.php");



		






///////////////////////////////////
	
	$zocalo = new zocaloModel($zocalo_config);

	// LOAD STATIC ASSETS
	if(isset($_GET['lib'])) {
	
		switch($_GET['lib']) {
			
			case "js" : 
				$zocalo -> printJS();
			break;
			
			case "css" : 
				$zocalo -> printCSS();
			break;

		}

		exit();
	}


	// run api
	$request = $_POST;
	if(!isset($request['verb'])){
		exit("How did you get here?  No api method requested.  If you're a hackr bot, you can just fuck the fuck off.");
	}
	$zocalo -> request = $request;
	$verb = $request['verb'];
	

	// validate user
	$requiresLogin = ($verb != 'loginUser' && $verb != 'logoutUser');	
	if( $requiresLogin ){
		$zocalo -> validateUser();
	}
	
	
	$response = $zocalo -> $verb();
	echo json_encode($response);





