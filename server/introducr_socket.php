#!/usr/bin/env php
<?php

fclose(STDOUT);
fclose(STDERR);
$STDOUT = fopen('logs/socket_runtime.log', 'a+');
$STDERR = fopen('logs/error.log', 'a+');
date_default_timezone_set('America/New_York');
ini_set("display_errors", "stderr");
ini_set("error_log", 'logs/error.log');


// LOAD FRAMEWORK
require_once('model/framework/rkdatabase.php');
require_once('model/framework/websockets.php');

// LOAD DATA MODEL
require_once("model/introducr-config.php");
require_once("model/introducr_model.php");


//print_r($STDOUT);

echo "test"


class introducrSocketServer extends WebSocketServer {
	protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.


	protected function process ($sender, $message) {
		$request = json_decode($message, true);
		extract($request);
		
		$this -> goAhead = true;
		
		// check user for all actions other than registration
		if(!isset($verb)) $this -> log_error($sender, "no action requested");
		if(!isset($uid) && $verb != "register") $this -> log_error($sender, "no sender id");

		if($verb == "register") {
			$this -> stdout(date('l - F j, Y - g:i:s A') . "\tUSER LOGIN\t" . $name . "\n");
		}


/* -- CAN'T READ FROM ONE SERVER TO THE OTHER, MAYBE GO THROUGH MYSQL? POSTPONE
		session_start();
		if(!isset($_SESSION['uid'])) $this -> log_error($sender, print_r($_SESSION, 1));
		if(!$_SESSION['uid'] != $uid) $this -> log_error($sender, "you are not logged in correctly");
*/

		if(!$this -> goAhead) return;

		// update user hash
		$this -> uidHash[$uid] = $sender;

		// run request		
		if(isset($verb) && $verb != "register"){
			 $this -> $verb($sender, $request);
		}
	}
	
	protected function log_error($sender, $message){
		if(!$this -> goAhead) return;
		$response = array("error" => $message);
		$this -> send($sender, json_encode($response));
		$this -> goAhead = false;
	}
	
	
	
	
	
	
	protected function connected ($user) {
		// do nothing when user connects
	}

	protected function selectUser ($user) {
		foreach($this -> users as $id => $u) if($user -> id != $id) return $u;
	}

	
	protected function closed ($user) {
		// Do nothing: This is where cleanup would go, in case the user had any sort of
		// open files or other objects associated with them.	This runs after the socket 
		// has been closed, so there is no need to clean up the socket itself here.
	}


	function postMessage($sender, $request){

		
			// open the payload
			$message = $request['message'];
			$fromDB = $this -> api -> postMessage($message);

			
			// if message is successful
			if($fromDB["status"] == "success"){

				$messageInDB = $fromDB["message"];
				$targetId = $message['targetId'];

				if(isset($this -> uidHash[$targetId])) {
					$isOnline = true;
					$notification = array(
						"status" => "success",
						"subject" => "new message",
						"body" => array(
							"message" => $messageInDB
						)
					);
					$this -> send($this -> uidHash[$targetId], json_encode($notification));
				}
				else {
					$isOnline = false;
				}


				$response = array(
					"status" => "success",
					"subject" => "message sent",
					"body" => array(
						"message" => $messageInDB,
						"isOnline" => $isOnline
					)
				);

			} 

			// if it fails to post to db
			else {
				$response["status"] = "error";
				if(isset($fromDB["error_message"])) $response["body"] = $fromDB["error_message"];
			}
		
			
			// send response
			$this -> send($sender, json_encode($response));

		
		}

}

extract($introducr_config['client']['socket']);

$socketServer 			= new introducrSocketServer($path,$port);
$socketServer -> api 	= new introducrAPI($introducr_config);
$socketServer -> db 	= new RK_mysql($introducr_config['database']);

try {
	$socketServer -> run();
}
catch (Exception $e) {
	$socketServer -> stdout($e->getMessage());
}

