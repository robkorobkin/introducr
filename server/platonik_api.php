<?php

	Class PlatonikAPI {
		
		function __construct($config){
			$this -> db = new RK_mysql($config['database']);
		}
	
		function loginUser(){
			extract($this -> request);
			$userFromInput = $user;
		
			// verify access token
			$userFromFacebook = json_decode(file_get_contents('https://graph.facebook.com/me?access_token=' . $access_token));		
			if($userFromFacebook -> id != $userFromInput -> fbid){
				echo "something is weird, requested user doesn't match access token";
				exit;
			}

			// if it's good, store fbid in the session - use to authenticate future requests
			$_SESSION['fbid'] = $userFromFacebook -> id;

			// is user in database?
			$sql = 'SELECT * FROM users where fbid=' . $userFromInput['fbid'];
			$userFromDB = $this -> db -> get_row($sql);
			
			// if not
			if(!$userFromDB){
				$this -> db -> insert($userFromInput, "users");
				$userFromInput['new'] = true;
				return $userFromInput;
			}
			
			// else - update date accessed
			$update['dateAccessed'] = date("Y-m-d H:i:s");
			$where['fbid'] = $userFromInput['fbid'];
			$this -> update($update, "users", $where);
			
			// return user record from database
			return $userFromDB;
			
		}
	
	
	
	
	
	}