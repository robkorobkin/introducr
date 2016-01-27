#!/usr/bin/env php
<?php


// LOAD CONFIG
require_once("model/introducr-config.php");

// LOAD FRAMEWORK
require_once('model/framework/rkdatabase.php');
require_once('model/framework/websockets.php');

// LOAD DATA MODEL
require_once("model/introducr_model.php");




class IntroducrSocketServer extends WebSocketServer {
	protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.


	protected function process ($sender, $message) {
		$request = json_decode($message, true);
		extract($request);
		
		$this -> goAhead = true;
		
		// check user for all actions other than registration
		if(!isset($verb)) $this -> log_error($sender, "no action requested");
		if(!isset($uid) && $verb != "register") $this -> log_error($sender, "no sender id");

/* -- 	- ToDo: VERIFY THAT USER IS WHO THEY SAY THEY ARE
		- CAN'T READ FROM ONE SERVER TO THE OTHER, MAYBE GO THROUGH MYSQL? POSTPONE
*/

		if(!$this -> goAhead) return;

		// update user hash
		$this -> uidHash[$uid] = $sender;

		// run request		
		if(isset($verb) && $verb != "register"){

			// check to make sure mysql connection is still alive, and if it's dead, restart it
			if (!$this -> model -> db -> conn ->ping()) {
				$this -> model -> db  = new RK_mysql($this -> config['database']);
			}


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
		// ToDo: remove user from uidHash
		// Do nothing: This is where cleanup would go, in case the user had any sort of
		// open files or other objects associated with them.	This runs after the socket 
		// has been closed, so there is no need to clean up the socket itself here.
	}

	function markChatAsRead($sender, $request){
		$this -> model -> markChatAsRead($request['relationship']);
	}

	function postMessage($sender, $request){

		
			// open the payload
			$message = $request['message'];
			$fromDB = $this -> model -> postMessage($message);

			
			// if message is successful
			if($fromDB["status"] == "success"){

				$messageInDB = $fromDB["message"];
				$targetId = $message['targetId'];


				// SEND MESSAGE TO USER
				if(isset($this -> uidHash[$targetId])) {
					$isOnline = true;
					$notification = array(
						"status" => "success",
						"subject" => "new message",
						"body" => array(
							"message" => $messageInDB,
							"relationship" => $this -> model -> getRelationship($targetId, $message['senderId'])
						)
					);
					$this -> send($this -> uidHash[$targetId], json_encode($notification));
				}
				else {
					$isOnline = false;
				}

				// CONFIRM MESSAGE TO SENDER
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

$socketServer 			= new IntroducrSocketServer($path,$port);
$socketServer -> config = $introducr_config;
$socketServer -> model 	= new IntroducrModel($introducr_config);
//$socketServer -> db -> debug = true;

try {
	$socketServer -> run();
}
catch (Exception $e) {
	$socketServer -> stdout($e->getMessage());
}

