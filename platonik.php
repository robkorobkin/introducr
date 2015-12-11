<?php
	require_once("platonik-config.php");
	require_once("server/rkdatabase.php");
	require_once("server/platonik_api.php");	
	session_start();

	
	$api = new PlatonikAPI($platonik_config);

	if(isset($_GET['lib']) && $_GET['lib'] == 'app'){
		$api -> printJS();
		exit();
	}



	$api -> request = $_POST;
	$verb = $api -> request['verb'];
	
	$requiresLogin = ($verb != 'loginUser');
	
	
	$isLoggedIn = (isset($_SESSION['uid']) && $_SESSION['uid'] == $api -> request['uid']);
	
	if($requiresLogin && !$isLoggedIn){
		echo "You are making a request that you don't have permissions for.";
		exit();
	}
	
	$api -> uid = $_SESSION['uid'];
	$response = $api -> $verb();
	echo json_encode($response);
