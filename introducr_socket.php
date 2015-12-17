#!/usr/bin/env php
<?php

require_once('server/sockets/websockets.php');
require_once("platonik-config.php");
require_once('server/rkdatabase.php');



class introducrSocketServer extends WebSocketServer {
	protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.


	protected function process ($sender, $message) {
		$request = json_decode($message, true);
		extract($request);
		
		$this -> goAhead = true;
		
		// check user for all actions other than registration
		if(!isset($verb)) $this -> log_error($sender, "no action requested");
		if(!isset($uid) && $verb != "register") $this -> log_error($sender, "no sender id");

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
	
	
	protected function postMessage ($sender, $request) {
		
		extract($request);
		
		$senderUid = (int) $uid;
		$targetUid = (int) $message['targetId'];
		$now = date("Y-m-d H:i:s");

		
		// get target's relationship with sender
		$rToSender = array(
			"selfId" => $targetUid,
			"otherId" => $senderUid
		);		
		$rToSender = $this -> db -> getOrCreate($rToSender, "relationships");


		// bail if person has been blocked		
		if($rToSender['hasBlocked']) $this -> log_error($sender, "target has blocked you");
		
		// record message
		$insert_id = $this -> db -> insert($message, "messages");
		$message = $this -> db -> get_rowFromObj(array("messageId" => $insert_id), "messages");
		
		
		// update relationship: target -> sender
		$update = array(
			"numUnread" => ($rToSender['numUnread'] + 1),
			'lastMessageDate' => $now
		);
		$this -> db -> update($update, "relationships", $rToSender);
		
		
		
		// notify other user
		if(isset($this -> uidHash[$targetUid])) {
			$isOnline = true;
			$notification = array(
				"subject" => "new message",
				"body" => $message
			);
			$this -> send($this -> uidHash[$targetUid], json_encode($notification));
		}
		else {
			$isOnline = false;
		}


		// create / update relationship: sender -> target
		$rFromSender = array(
			"selfId" => $senderId,
			"otherId" => $targetId
		);
		$update = array(
			"status" => "active",
			"lastMessageDate" => $now,
			"lastCheckedDate" => $now
		);
		$this -> db -> updateOrCreate($update, "relationships", $rFromSender);


		// push confirmations
		$notification = array(
			"subject" => "message sent",
			"message" => $message
		);
		$this -> send($this -> uidHash[$targetUid], json_encode($notification));

	}
	
	
	
	
	
	protected function connected ($user) {
		$this -> stdout("ABOUT TO PRINT OUT USERS");
		$this -> stdout(print_r($this -> users, true));
		$this -> stdout("===============================");		
	}

	protected function selectUser ($user) {
		foreach($this -> users as $id => $u) if($user -> id != $id) return $u;
	}

	
	protected function closed ($user) {
		// Do nothing: This is where cleanup would go, in case the user had any sort of
		// open files or other objects associated with them.	This runs after the socket 
		// has been closed, so there is no need to clean up the socket itself here.
	}
}

$socketServer = new introducrSocketServer("127.0.0.1","9000");

$socketServer -> db = new RK_mysql($platonik_config['database']);

try {
	$socketServer -> run();
}
catch (Exception $e) {
	$socketServer -> stdout($e->getMessage());
}

