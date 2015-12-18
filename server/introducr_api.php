<?php

	Class introducrAPI {
		
		function __construct($config){
			$this -> config = $config;
			$this -> db = new RK_mysql($config['database']);
		}
	
		function printJS(){
			header('Content-type: text/javascript');
			echo 'var dictionary=' . json_encode($this -> config['dictionary']) . ';';
			echo 'var fb_config=' . json_encode($this -> config['facebook']) . ';';
			echo 'var socket_path="' . $this -> config['socketPath'] . '";';
			echo file_get_contents('client/introducr.js');
		}
		
		function printCSS(){
			header('Content-type: text/css');
			echo file_get_contents('client/introducr.css');
		}
		
		



		/************************************************************************************************
		*	API
		*	- loginUser
		*	- updateUser
		*	- postCheckin
		*	- listCheckins
		*
		************************************************************************************************/
	
		function loginUser(){
			extract($this -> request);
			
			// if it's a facebook user, get uid
			$uid = $this -> getUserFromFacebook($access_token);
			
			// PUT OTHER LOGIN MODES HERE
			
			// else - update date accessed
			$update['dateAccessed'] = date("Y-m-d H:i:s");
			$where['uid'] = $uid;
			$this -> db -> update($update, "users", $where);
			
			// store uid to session
			$_SESSION['uid'] = $uid;

			// return user record from database
			return array(
				"user" => $this -> _getUserByUid($uid)
			);
			
		}
	
		function updateUser(){
			extract($this -> request);
			extract($user["birthday"]);
			$user['dateModified'] = date("Y-m-d H:i:s");
			$user['birthday'] = date("Y-m-d H:i:s", strtotime($year . '-' . $month . '-' . $day));
			$where = array('uid' => (int) $user['uid']);
			// $this -> db -> debugMode = true;
			$this -> db -> update($user, "users", $where);
			return $this ->_getUserByUid($user['uid']);
		}
	
		function postCheckin(){
			extract($this -> request);
			$newCheckin["time"] = date("Y-m-d H:i:s");
			$newCheckin["uid"] = $this -> uid;
			$checkinid = $this -> db -> insert($newCheckin, "checkins");
		}

		function listCheckins(){
			extract($this -> request);
			
			$checkins = $this -> _getCheckins($search_params);
			
			return array(
				"checkins" => $checkins
			);
		}

		function loadChat(){
			extract($this -> request);
			$me = (int) $this -> uid;
			$you = (int) $chat_request['partner'];

			$sql = "SELECT * FROM messages where (senderId=$me and targetId=$you) or (targetId=$me and senderId=$you) order by messageDate ASC";
			$conversation = $this -> db -> get_results($sql);


			$youFull = $this -> _getUserByUid($you);

			// GET RELATIONSHIP? (SKIP FOR NOW)
			//$sql = "SELECT * FROM relationships where "
			//$relationship 

			return array(
				"conversation" => $conversation,
				"partner" => $you
			);


		}


		/************************************************************************************************
		*	DATA PROCESSORS
		*	_getUserByFbId
		*	_getUserByUid
		*	_loadUser
		*	_getCheckins
		************************************************************************************************/
	
		function _getUserByFbId($fbid){
			$sql = 'SELECT * FROM users where fbid=' . (int) $fbid;
			$userFromDB = $this -> db -> get_row($sql);
			return ($userFromDB) ? $this -> _loadUser($userFromDB) : false;
		}

		function _getUserByUid($uid){
			$sql = 'SELECT * FROM users where uid=' . (int) $uid;
			$userFromDB = $this -> db -> get_row($sql);
			return ($userFromDB) ? $this -> _loadUser($userFromDB) : false;
		}
	
		function _loadUser($user){
		
			$birthdayUnix = strtotime($user['birthday']);
		
			if($birthdayUnix != 0){
				$tmp = explode(' ', $user['birthday']);
				$tmp = $tmp[0];
				$tmp = explode('-', $tmp);
				$user['birthday'] = array(
					"year" => (int) $tmp[0],
					"month" => (int) $tmp[1],
					"day" => (int) $tmp[2],
					"string" => date("M d, Y", $birthdayUnix)
				);
			}
			else {
				$user['birthday'] = array(
					"year" => 0,
					"month" => 0,
					"day" => 0,
					"string" => ""
				);
			}

			return $user;
		}

		function _getCheckins($search_params){
			$sql = 'SELECT c.*, u.uid, u.fbid, u.isActive, u.first_name, u.last_name 
					FROM checkins c, users u
					WHERE c.uid = u.uid
					ORDER BY time desc';
			return $this -> db -> get_results($sql);		
		}
	
		/************************************************************************************************
		*	FACEBOOK
		*	- getUserFromFacebook($access_token)
		*
		************************************************************************************************/
	
		function getUserFromFacebook($access_token){
		
			// look user up server-side
			$url = 	'https://graph.facebook.com/me?access_token=' . $access_token . 
					"&fields=first_name,email,last_name,middle_name,location,birthday,gender";
			$userFromFacebook = json_decode(file_get_contents($url), true);
			
			// handle bad token
			if(!isset($userFromFacebook['id'])){
				 return array(
				 	"status" => "Failure",
				 	"message" => "Access Token Not Valid",
				 );
			}
			 

			// is user in database?
			$fbid = $userFromFacebook['id'];
			$userFromDB = $this -> _getUserByFbId($fbid);
			
			// if so, return the uid
			if($userFromDB) return $userFromDB['uid'];
			
			// if not - add user to database
			$newUser = $userFromFacebook;
			
			// - CREATE NEW USER OBJECT FROM FACEBOOK RETRIEVAL
			if(isset($newUser['birthday'])) {
				$newUser["birthday"] = date("Y-m-d H:i:s", strtotime($newUser["birthday"]));
			}
		
			if(isset($newUser['location'])) {
				$location = explode(',', $newUser['location']['name'] );
				$newUser['city'] = $location[0];
				$newUser['state'] = trim(strtoupper($location[1]));
				unset($newUser['location']);
			}
			
			if(isset($newUser['gender'])) {
				$newUser['gender'] = strtoupper($newUser['gender']);
			}

			$newUser['fbid'] = $newUser['id'];
			unset($newUser['id']);
			$newUser['dateCreated'] = date("Y-m-d H:i:s");
		
			$uid = $this -> db -> insert($newUser, "users");
			return $uid;		
		
		}
	
	
		/************************************************************************************************
		*	SOCKET STUFF
		*	- postMessage($message)
		*
		************************************************************************************************/


		function postMessage($message){

		
			// open the payload
			extract($message); 

			$now = date("Y-m-d H:i:s");

		
			// get target's relationship with sender
			$rToSender = array(
				"selfId" => $targetId,
				"otherId" => $senderId
			);		
			$rToSenderRow = $this -> db -> getOrCreate($rToSender, "relationships");


			// bail if person has been blocked		
			if($rToSenderRow['hasBlocked']) {
				return array(
					"status" => "error",
					"error_message" => "target has blocked you"
				);
			}
		
			// record message
			$insert_id = $this -> db -> insert($message, "messages");
			$message = $this -> db -> get_rowFromObj(array("messageId" => $insert_id), "messages");
		
		
			// update relationship: target -> sender
			$update = array(
				"numUnread" => ($rToSenderRow['numUnread'] + 1),
				'lastMessageDate' => $now,
				"status" => "active"
			);
			$this -> db -> update($update, "relationships", $rToSender);
		

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

			return array(
				"status" => "success",
				"message" => $message
			);

		}
		
	
	
	
	}