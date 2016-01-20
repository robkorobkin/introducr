<?php

	Class IntroducrModel {
		
		function __construct($config){
			$this -> config = $config;
			$this -> db = new RK_mysql($config['database']);
		}
	
		function printJS(){
			header('Content-type: text/javascript');
			echo 'var dictionary=' . json_encode($this -> config['dictionary']) . ';';
			echo 'var introducr_settings = ' . json_encode($this -> config['client']) . ';';
			echo file_get_contents('../client/introducr.js');
		}
		
		function printCSS(){
			header('Content-type: text/css');
			echo file_get_contents('../client/introducr.css');
		}
		
		function handleError($message){
			$response['error'] = $message;
			exit(json_encode($response));
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

			if(isset($fb_code)){
				$access_token = $this -> getAccessTokenFromCode($fb_code);
			}


			if(!isset($access_token)) $this -> handleError("missing access token");
			
			// if it's a facebook user, get uid
			$uid = $this -> getUserFromFacebook($access_token);
			
			// PUT OTHER LOGIN MODES HERE

			// store uid to session
			$_SESSION['uid'] = $uid;
			
			// else - update date accessed
			$update['dateAccessed'] = date("Y-m-d H:i:s");
			$update['fbAccessToken'] = $access_token; // get updated access_token from Facebook
			
			$where['uid'] = $uid;
			$this -> db -> update($update, "users", $where);
			
			$user = $this -> _getUserByUid($uid);
			$user['unreadChatsCount'] = $this -> _getUnreadChatsCount($uid);

			// return user record from database
			return array(
				"user" => $user,
				"feedList" => $this -> listCheckins()
			);
			
		}
	
		function updateUser(){
			extract($this -> request);
			extract($user["birthday"]);
			$user['dateModified'] = date("Y-m-d H:i:s");
			$user['birthday'] = date("Y-m-d H:i:s", strtotime($year . '-' . $month . '-' . $day));

			// resolve discrepencies between client-side and server-side user models
			unset($user['here']);  // ToDo: store user's last location?
			unset($user['friendsList']);
			unset($user['unreadChatsCount']);
			

			if($user['bio'] != ''){
				$user['isActive'] = 1;
				$user['isNew'] = 0;
			}

			$where = array('uid' => (int) $user['uid']);
			$this -> db -> update($user, "users", $where);
			$user = $this ->_getUserByUid($user['uid']); 
			$user['unreadChatsCount'] = $this -> _getUnreadChatsCount($uid);

			return $user;
		}
	
		function postCheckin(){
			extract($this -> request);

			// post checkin
			$newCheckin["time"] = date("Y-m-d H:i:s");
			$newCheckin["uid"] = $this -> uid;
			$checkinId = $this -> db -> insert($newCheckin, "checkins");
			
			// update users
			$update['lastCheckinId'] = $checkinId;
			$where['uid'] = $this -> uid;
			$this -> db -> update($update, "users", $where);
			
			return $this -> listCheckins();
		}

		function listCheckins(){
			extract($this -> request);
			$checkins = array();

			if(isset($search_params)) {
				$checkins = $this -> _getCheckins($search_params);
			}
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

			// update chats count
			$update = array('numUnread' => 0);
			$where = array('selfId' => $this -> uid, 'otherId' => $you);
			$this -> db -> update($update, 'relationships', $where);

			$youFull = $this -> _getUserByUid($you);

			// GET RELATIONSHIP? (SKIP FOR NOW)
			//$sql = "SELECT * FROM relationships where "
			//$relationship 

			return array(
				"conversation" => $conversation,
				"partner" => $you
			);

		}

		function getRelationship($selfId, $otherId){
			$sql = 'SELECT * from relationships where selfId=' . (int) $selfId . ' and otherId=' . (int) $otherId;
			return $this -> db -> get_row($sql);
		}

		function markChatAsRead($relationship){
			$update = array('numUnread' => 0);
			$this -> db -> update($update, "relationships", $relationship);
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

		function _getUnreadChatsCount($uid){
			$sql = 'SELECT COUNT(relationshipId) as unreadChats from relationships where numUnread <> 0 and selfId=' . $uid;
			$count = $this -> db -> get_row($sql);
			return $count['unreadChats']; 
		}


		
		function _getCheckins($search_params){

			extract($search_params);
			$where_strs = [];
			$orderBy = 'c.time desc';

			// handle sort order - postpone for now
			if(isset($sortOrder)) {
				switch($sortOrder) {
					case "abc" : 
						$orderBy = 'u.last_name asc';
					break;
				}
			}
			else {
				$orderBy = 'c.time desc';
			}

			if(isset($recents)){
				$where_strs[] = "r.lastMessageDate != '0000-00-00 00:00:00'";
				$orderBy = "r.lastMessageDate DESC";
			}
			else {



				if(isset($search_str) && $search_str != "") {
					$where_strs[] = "(u.first_name LIKE '%$search_str%' OR u.last_name LIKE '%$search_str%')";
				}

				if(isset($proximity) && $proximity != '' && isset($here)) {
					extract($here);

					// http://geography.about.com/library/faq/blqzdistancedegree.htm
					// Each degree of latitude is approximately 69 miles apart.
					// At 40° north or south (Portland is at 43 N), the distance between a degree of longitude is 53 miles.
					// So, LAT = +/- (.015 * R); LON = +/- (.02 * R)

					// if browser sends us geocoordinates - if not, maybe get from tracing IP?
					if(isset($lat) && isset($lon)) {
						$p = (int) $proximity;
						$where_strs[] = "c.lat >= " . (float) ($lat - ($p * .015));
						$where_strs[] = "c.lat <= " . (float) ($lat + ($p * .015));
						$where_strs[] = "c.lon >= " . (float) ($lon - ($p * .02));
						$where_strs[] = "c.lon <= " . (float) ($lon + ($p * .02));
					}
				}	

				if(isset($gender) && $gender != '') {
					$opts = array('male', 'female', 'other');
					if(in_array($gender, $opts)) $where_strs[] = "u.gender='$gender'";
				}		

				if(isset($age) && $age != '') {

					$year = (int) date("Y");

					$ageRange = explode('_', $age);

					
					if($ageRange[0] != ''){
						$ageMin = (int) $ageRange[0];
						$birthdayMax = ($year - $ageMin) . date("-m-d");
						$where_strs[] = "u.birthday <= '$birthdayMax'";	
					} 

					if($ageRange[1] != ''){
						$ageMax = (int) $ageRange[1];
						$birthdayMin = ($year - $ageMax) . date("-m-d");
						$where_strs[] = "u.birthday >= '$birthdayMin'";	
					}


				}		

				if(isset($justFriends) && $justFriends == 'true') {
					$where_strs[] = "r.isFriend=1";
				}

			}



			$whereString = implode(' AND ', $where_strs);
			if($whereString != '') $whereString = 'WHERE ' . $whereString;

			$sql = 'SELECT c.*, u.uid, u.fbid, u.first_name, u.last_name, u.bio, u.birthday, r.numUnread, r.lastMessageDate, r.hasBlocked
					FROM users u
					LEFT JOIN checkins c ON c.checkinid = u.lastCheckinId 
					LEFT JOIN relationships r ON r.selfId = ' . $_SESSION['uid'] . ' and r.otherId = u.uid ' . 
					$whereString .
					' ORDER BY ' . $orderBy . ' LIMIT 40';
			
			//echo "\n\n $sql \n\n";

			$results = $this -> db -> get_results($sql);
			

			// filter out anybody who's been blocked - if matches, less than 40 rows will be returned, but who cares.
			$response = [];
			foreach($results as $row){
				if($row['hasBlocked'] != 1) $response[] = $row;
			}


			return $response;
		}
	
		/************************************************************************************************
		*	FACEBOOK
		*	- getUserFromFacebook($access_token)
		*
		************************************************************************************************/
	
		function _logOutFromFacebook($fb_access_token){
			extract($this -> config);
			
			$api_response = file_get_contents($url);

			if(strpos($api_response, '<!DOCTYPE html>') == 0){
				$response['status'] = 'success';
			}
			else $response['error'] = "FAILURE TO LOG OUT \n\n" . $api_response;

			return $response;
		}


		function getAccessTokenFromCode($fb_code){
			extract($this -> config);

		
			$url = 	'https://graph.facebook.com/v2.5/oauth/access_token?' . 
					'client_id=' . $client['facebook']['appId'] .
					'&redirect_uri=' . urlencode($client['base_url']) . 
					'&client_secret=' . $facebook_secret . 
					'&code=' . $fb_code;

			$response = file_get_contents($url);

			if(!$response || strpos($response, 'access_token') === false) {

				$error = 	"Failed to convert code into access token: \n\n" .
							$url . "\n\n" .
							$response;

				$this -> handleError($error);

			}


			$access_token_data = json_decode($response);

			$access_token = $access_token_data -> access_token;


			// else, get an extended access token
			$url = 	'https://graph.facebook.com' . 
					'/oauth/access_token?grant_type=fb_exchange_token' .
					'&client_id=' . $this -> config['client']["facebook"]["appId"] .
					'&client_secret=' . $this -> config["facebook_secret"] . 
					'&fb_exchange_token=' . $access_token;

			$response = file_get_contents($url);

			if(!$response || strpos($response, 'error') !== false) {
				$error = 	"Unable to get extended access token. \n\n" .
							$url . "\n\n" .
							$response;

				$this -> handleError($error);
			}


			$tmp = explode('&', $response);
			$tmp = $tmp[0];
			$tmp = explode('=', $tmp);
			$longevity_token = $tmp[1];

			return $longevity_token;
		}


		function getUserFromFacebook($access_token){
		
			// look user up server-side
			$url = 	'https://graph.facebook.com/me?access_token=' . $access_token . 
					"&fields=first_name,email,last_name,middle_name,location,birthday,gender";

			$response = file_get_contents($url);

			// handle bad token
			if(!$response || strpos($response, 'error') !== false) {
				$this -> handleError("Bad access token.");
			}			
			
			$userFromFacebook = json_decode($response, true);

			 

			// is user in database?
			$fbid = $userFromFacebook['id'];
			
			
			// Are they already in the database?
			$userFromDB = $this -> _getUserByFbId($fbid);


			// update friends list
			// ToDo: Do this on client to reduce server load, moved onto server to avoid client-side loading issues

			$url = 'https://graph.facebook.com/me/friends?limit=5000&access_token=' . $access_token;
			$response = file_get_contents($url);
			if(!$response || strpos($response, 'error') !== false) {
				$this -> handleError("Couldn't get friends list.", true);
			}
			$friendsResponse = json_decode($response, true);
			$friendsList = array();
			foreach($friendsResponse['data'] as $friend){
				$friendsList[] = $friend['id'];
			}
			$this -> recordFriends($friendsList);





			// if user in database, return the uid
			if($userFromDB) return $userFromDB['uid'];

			

			
			// if not - add user to database
			$newUser = $userFromFacebook;

			$newUser['fbAccessToken'] = $access_token;
			
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

		function recordFriends($friendsList){

			$userId = $_SESSION['uid'];
			foreach($friendsList as $friendFbId){

				$friend = $this -> _getUserByFbId($friendFbId);

				$friendId = (int) $friend['uid']; 
				$update = array("isFriend" => 1);
				
				$where = array(
					"selfId" => $userId,
					"otherId" => $friendId
				);
				$this -> db -> updateOrCreate($update, "relationships", $where);

				$where = array(
					"selfId" => $friendId,
					"otherId" => $userId
				);

				$this ->  db -> updateOrCreate($update, "relationships", $where);
			}

			return array(
				"message" => "Updated relationships for " . count($friendsList) . " people."
			);
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