<?php
	require_once("platonik-config.php");
	require_once("server/rkdatabase.php");
	require_once("server/platonik_api.php");	
	session_start();

	
	$api = new PlatonikAPI($platonik_config);	
	$api -> request = $_POST;
	$verb = $api -> request['verb'];
	$response = $api -> $verb();
	echo json_encode($response);
