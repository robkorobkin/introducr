<?php
	
	
	require_once("model/introducr-config.php");
	require_once("model/framework/rkdatabase.php");
	require_once("model/introducr_model.php");



		






///////////////////////////////////
	
	$introducr = new IntroducrModel($introducr_config);

	// LOAD STATIC ASSETS
	if(isset($_GET['lib'])) {
	
		switch($_GET['lib']) {
			
			case "js" : 
				$introducr -> printJS();
			break;
			
			case "css" : 
				$introducr -> printCSS();
			break;

		}

		exit();
	}


	// run api
	$request = $_POST;
	if(!isset($request['verb'])){
		exit("How did you get here?  No api method requested.  If you're a hackr bot, you can just fuck the fuck off.");
	}
	$introducr -> request = $request;
	$verb = $request['verb'];
	

	// validate user
	$requiresLogin = ($verb != 'loginUser' && $verb != 'logoutUser');	
	if( $requiresLogin ){
		$introducr -> validateUser();
	}
	
	
	$response = $introducr -> $verb();
	echo json_encode($response);





