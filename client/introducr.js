
// SAFE LOGGER
function logger(message){

	// $('#output').append('<br />');
	// $('#output').append(angular.toJson(message, true));



	if('console' in window && 'log' in console){
		console.log(message)		
	}
}





/*************************************************************
// THE APP!
*************************************************************/

var app = angular.module('introducrApp', ['LocalStorageModule']);

app.controller('introducrCtrl', ['$scope', '$http', '$sce', '$rootScope', '$window',  'localStorageService',
	function($scope, $http, $sce, $rootScope, $window, localStorageService){
		
		

		$scope.init = function(){

			// load settings
			$scope.dictionary = dictionary;
			$scope.fb_config = introducr_settings.facebook;
			$scope.appName = 'introducr';
			

			// default map to Franklin & Congress
			$scope.defaultLocation = {
				lat : 43.661471,
				lon : -70.255326
			};


			// initialize view state
			$scope.loaded = false;
			$scope.view = 'loading';
			$scope.header = {
				show : false
			}
			$scope.footer = {
				show : false
			}
			

			//  read state from cookie
			$scope.cookieMonster.load();


			// init ssocket controller
			$scope.socketController.init(introducr_settings.socket);
			$scope.feedController.init();
			
			// bring up selected person
			$scope.currentPerson = {
				pics : {}
			}
			
			

			var tmp = window.location.href.split('code=');
			var fb_code =  (tmp.length > 1) ? tmp[1] : false;
			

			// and launch app
			if($scope.sessionInCookie) {
				$scope.loadInitialView();
				$scope.apiClient.loginUser(); // runs in the background
			}
			else if(fb_code){
				$scope.user = {
			 		fb_code : fb_code			 	
			 	}				
			 	$scope.apiClient.loginUser();
			}
			else {
				$scope.loadView('login');
			}



			
		}
		
		
		
		// API CLIENT OBJECT
		$scope.apiClient = {
			
			postData : function(request, f){
				if("user" in $scope) request.uid = $scope.user.uid;
				$.post('server/introducr_api.php', request, function(response){
					if('error' in response && response.error == "logged out"){
						 // $scope.apiClient.logoutUser();
					}
					else if(f) f(response);
					$scope.$digest();
				}, 'json');
			},

			loginUser : function(){

				if(!("user" in $scope)){
					logger("tried to login to app without a user in the cookie");
					return;
				}

				if(!("search" in $scope)) {
					logger("tried to login before feed controller was initialized");
					return;
				}				

				//  now that we have an access token, renew server session (in the background)
				var request = {
					search_params: $scope.search,
					verb: "loginUser"
				}


				if(("fbAccessToken" in $scope.user)) {
					request.access_token = $scope.user.fbAccessToken;
				}
				else if("fb_code" in $scope.user){
					request.fb_code = $scope.user.fb_code;
				}
				else {
					logger("tried to login before user was logged into facebook");
					return;
				}


				$scope.apiClient.postData(request, function(response){

					// if their token doesn't validate on our server, log them out
					if("error" in response){ 
						$scope.cookieMonster.clear();
						$scope.loadView('login');
					}

					// else update session
					else {
						$scope.user = response.user;
						
						// ToDo: Load feed from login response
						if($scope.view == 'feed') $scope.feedController.loadFeed(response.feedList.checkins);
						if(parseInt($scope.user.isNew)) $scope.loaded = false;
						
						// if you're just logging in...
						if(!$scope.loaded){
							$scope.loadInitialView();
						}

						$scope.cookieMonster.save();
						$scope.socketController.register();	
					}

					$scope.$digest();
					
				});
			},
		
			logoutUser : function(){

				if($scope.view != 'login'){
					$scope.loadView('loading');	
				}

				var logout_url = 'https://www.facebook.com/logout.php?next=' + encodeURIComponent(introducr_settings.base_url) + '&access_token=' + $scope.user.fbAccessToken;

				$scope.user = {};
				$scope.loaded = false;
				$scope.sessionInCookie = false;

				$scope.acctController.loadLoginScreen();

				$scope.cookieMonster.clear();	
				$scope.loadView('login');
				
				
				console.log(logout_url);
				window.location = logout_url;
			}

		}
		
		

		// VIEW MANAGER / ROUTER
		$scope.loadView = function(view, screen){

			//logger("trying to load: " + view + ' - ' + screen)
			
			if(view == "loading" || view == "login") $scope.footer.show = false;
			
			// if the user is in the initial account set-up phase, don't show the bottom navigation
			else if('isNew' in $scope.user && parseInt($scope.user.isNew) == 1) $scope.footer.show = false;

			else $scope.footer.show = true;
			

			$scope.view = view;
			if(screen) $scope.screen = screen;

			// update footer view
			$scope.currentComponent = view;
			
		}



		// LOAD APP
		$scope.loadInitialView = function(){
			

			$scope.loaded = true;
			

			// load view - if new, load account interface, otherwise load feed
			// ToDo: If they'd been in a chat, load to that chat					
			if(parseInt($scope.user.isNew)){
				$scope.loadView('account', 1);
			}
			else {
				var feedList = localStorageService.get('feedList');
				$scope.feedController.loadFeed(feedList);
			}
			
			
			
		}



		// COOKIE MONSTER - MAINTAIN STATE IN BETWEEN SESSIONS
		$scope.cookieMonster = {

			load : function(){

				// load user
				var user = localStorageService.get('user');		
				$scope.sessionInCookie = (user && 'isNew' in user);
				$scope.user = (user) ? user : {};

				// load feed
				var feedList = localStorageService.get('feedList');
				$scope.feedList = (feedList) ? feedList : [];

				// load geolocation
				var here = localStorageService.get('here');
				here = (here) ? here : $scope.defaultLocation;
				here.hasLocation = false;
				$scope.here = here;
				
			},

			save : function(){

				if('isNew' in $scope.user) $scope.user.isNew = parseInt($scope.user.isNew);
				localStorageService.set('user', $scope.user);

				var feedList = $scope.feedController.feedList;
				localStorageService.set('feedList', feedList);

				localStorageService.set('here', $scope.here);	
			},

			clear : function(){
				localStorageService.set('user', {});
			}
		}
		
		
		// ACCOUNT CONTROLLER
		$scope.acctController = {
			
			needs : {
				first_name : false,
				last_name : false,
				email : false,
				bio : false
			},

			sending: false,

			loadLoginScreen : function(){
				$scope.header.show = false;
				$scope.footer.show = false;
				$scope.loadView('login');				
			},
			
			loadScreen : function(screenNumber){
				$scope.loadView('account', screenNumber)
			},

			saveAndProgress : function(){
				if(this.sending) return;

				$scope.screen = parseInt($scope.screen);

				// validate
				var validate = {
					1 : ['first_name', 'last_name', 'email', 'city'],
					2 : ['bio']
				}
				var fields = validate[$scope.screen];
				var goAhead = true;
				$.each(fields, function(fIndex, field_name){
					if($scope.user[field_name] == '') {
						$scope.acctController.needs[field_name] = true;
						goAhead = false;
					}
					else $scope.acctController.needs[field_name] = false;
				});
				if(!goAhead) return;

				
				// save updated user
				this.sending = true;
				var request = {
					user: $scope.user,
					verb: 'updateUser'
				}				
				$scope.apiClient.postData(request, function(user){
					$scope.user = user;
					$scope.cookieMonster.save();

					// iterate to next screen
					$scope.acctController.sending = false;
					if($scope.isNew){
						$scope.screen++;
						if($scope.screen == 3) {
							$scope.feedController.loadFeed();
						}						
					}
					else {
						$scope.loadView('account', 'menu');
					}
				});
				
						
			}
		
		}
		
		
		// CHECKIN CREATOR
		$scope.checkinController = {
			
			open : function(){
				this.status = "open";
			
				this.newCheckin = {
					location : ""
				}
				$scope.loadView("checkIn");
			},
			
			submit : function(){
				if(this.status == "sending") return;

				$scope.loadView("loading");

				
				// APPEND GEOLOCATION - ToDo: clarify if postion from default or locator
				this.newCheckin.lat = $scope.here.lat;
				this.newCheckin.lon = $scope.here.lon;
			

				var request = {
					verb 	 : "postCheckin",
					newCheckin : this.newCheckin,
					search_params : $scope.search
				}
			
				$scope.apiClient.postData(request, function(response){
					$scope.feedController.mode = "feed";
					$scope.prev = "feed";
					$scope.feedController.loadFeed(response.checkins);
					$scope.loadView("feed", "feed");
					$scope.$digest();
				});			
			
			}
		}
		
		
		// FEED MANAGER
		$scope.feedController = {

			active : false,

			init : function(){
				

				// GEOLOCATOR - Initialized when cookie loaded
				// ToDo: Default by City and store in user table
				// ToDo: Implement geolocation options - https://developer.mozilla.org/en-US/docs/Web/API/PositionOptions

				$scope.hereMapImg = Utilities.getMapForPoint($scope.here);

				if (navigator.geolocation) {
					var options = {};
					navigator.geolocation.getCurrentPosition(this.updateLocation, this.locationFailure);
					navigator.geolocation.watchPosition(this.updateLocation, this.locationFailure);
				}


				$scope.search = {
					search_str : "",
					proximity : "5",
					gender: "",
					age: "",
					sort_order: "time",
					justFriends: false,
					here: $scope.here
				}

				this.feedList = [];
				
				this.request = {
					verb : "listCheckins"
				};
			
				this.setMode("feed");
			},

			updateLocation : function(position){
				$scope.here = {
					hasLocation : true,
					lat : position.coords.latitude,
					lon : position.coords.longitude
				}
				$scope.hereMapImg = Utilities.getMapForPoint($scope.here);
				$scope.search.here = $scope.here;
				$scope.cookieMonster.save();
			},


			locationFailure : function(error) { 
				logger("failure to geo-position");
				logger(error);	
			},

			search : function(){
				$scope.loadView('loading');
				this.feedList = [];
				this.updateFeed('feed');
			},
			
			setMode : function(mode){

				this.status = "loading";

				switch(mode){

					case "feed" :
						this.mode = "feed";
						$scope.prev = "feed";
						this.request.search_params = $scope.search;
					break;

					case "recents" :
						this.mode = "recents";
						$scope.prev = "recents";
						this.request.search_params = { recents : true };
					break;

				}
			},
			
			updateFeed : function(mode){
				this.setMode(mode);
				$scope.loadView("loading");
				$scope.apiClient.postData(this.request, function(response){
					$scope.feedController.loadFeed(response.checkins);
					$scope.$digest();
				});
			},

			loadFeed : function(checkins){

				this.feedList = []; // ToDo: handle concatenating and redundancy
				var checkins = (checkins) ? checkins : [];
				var self = this;
				
				$.each(checkins, function(cIndex, checkin){
					
					// search results or recents can include people who've never checked in
					if(checkin.time){
						var t = checkin.time.split(/[- :]/);
						checkin.dateObj = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
					}

					if(checkin.lastMessageDate && checkin.lastMessageDate != '0000-00-00 00:00:00'){
						var t = checkin.lastMessageDate.split(/[- :]/);
						checkin.dateRecentObj = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
					}

					checkin.lastCheckinMap = Utilities.getMapForPoint(checkin);
					
					self.feedList.push(checkin);
				});

				//logger(this.feedList);

				if(this.feedList.length == 0) {
					$scope.loadView("feed", "empty");
				}
				else {
					$scope.loadView("feed", "feed");	
				}

				if($scope.loaded){
					$scope.cookieMonster.save();
				}
			}

			
		}
		
		
		// PERSON MANAGER
		$scope.chatController = {

			chats : {},

			openFromCheckin : function(checkin){
				$scope.selected_person = checkin;
				$scope.loadView('person');

				// if no chat, show profile
				if(!checkin.lastMessageDate || checkin.lastMessageDate == '0000-00-00 00:00:00'){
					$scope.screen = 'profile';
					$scope.prev = "profile";
				}
				else {
					this.openChat(); 
				}
				
			},
		
			openChat : function(){
				$scope.screen = 'chat';
				this.status = "loading";
				
				// can we get this from cache?  close and open app?
				this.currentText = {
					content : ''
				};
				$scope.footer.show = false;
				$scope.header.show = true;

				var request = {
					"verb" : "loadChat",
					"chat_request" : {
						"partner" : $scope.selected_person.uid
					}
				}
				$scope.apiClient.postData(request, function(response){
					$scope.chatController.chats[$scope.selected_person.uid] = {
						"conversation" : response.conversation,
						"meta" : {}
					};
					$scope.currentConversation = response.conversation;
					$scope.$digest();
					$(".appFrame").scrollTop($(".appFrame").height());

				});		

			},
			
			sendChat : function(){
				
				// send message
				var request = {
					verb : "postMessage",
					message : {	
						senderId : $scope.user.uid,
						targetId : $scope.selected_person.uid,
						content : this.currentText.content
					}
				} 
				$scope.socketController.send(request);
	
			
				// update view
				// ...
			},

			confirmTransmission : function(transmission){
				
				var inConversation = false;


				switch(transmission.subject){

					case "new message" :
						var message = transmission.body.message;
						var senderId = message.senderId;
						if(senderId in this.chats){
							this.chats[message.senderId].conversation.push(message);	
							if($scope.selected_person.uid == message.senderId){
								inConversation = true;
							}
						}
						else {
							this.youveGotMail(message);
						}
					break;

					case "message sent" :
						var message = transmission.body.message;
						var isOnline = transmission.isOnline;
						if(message.senderId == message.targetId) break;
						this.chats[message.targetId].conversation.push(message);
						if($scope.selected_person.uid == message.targetId){
							this.currentText.content = '';
							inConversation = true;
						}

					break;

				}

			
				$scope.$digest();

				if(inConversation){
					  $(".appFrame").animate({ scrollTop: $(".appFrame").height() }, "slow");
				}

			},

			youveGotMail : function(message){
				alert("you've got mail from user #" + message.senderId);
			},

			goBack : function(){
				if($scope.prev == "profile") $scope.loadView('person', 'profile');
				else {
					$scope.screen = '';
					if($scope.prev == "feed") {
						$scope.feedController.updateFeed('feed');
						$scope.loadView('feed', 'feed')
					}
					if($scope.prev == "recents") {
						$scope.feedController.updateFeed('recents');
						$scope.loadView('feed', 'recents')
					}
				}

			},

			loadProfile : function(){
				$scope.loadView('person', 'profile');
				$scope.prev = "profile";
			}

		}
		
		
		///////////////////////////////////////////////////////////////////////////////////
		// SOCKET STUFF

		$scope.socketController = {
			
			isOpen : false,
		
			init : function(socket_params) {
				var host = 'ws://' + socket_params.path + ':' + socket_params.port; 
				try {
					this.socket = new WebSocket(host);
					this.socket.self = this;
					
					this.socket.onopen = function(msg) { 
						$scope.socketController.isOpen = true;
					};
							   
					this.socket.onmessage = function(envelope) { 
						
						var transmission = angular.fromJson(envelope.data);

						if("status" in transmission && transmission.status == "success"){
							$scope.chatController.confirmTransmission(transmission);	
						}
						else {
							console.log("socket spew:")
							console.log(envelope);
							console.log(transmission);
						}
						
					
					};
							   
					this.socket.onclose   = function(msg) { 
						// can we disconnect the user from the server-side hash?
					};
					
				}
				catch(ex){ 
					logger(ex); 
				}
			},

			register : function(){
				if(this.isOpen) {
					var req = {
						verb : "register",
						uid : $scope.user.uid,
						name : $scope.user.first_name + ' ' + $scope.user.last_name
					}
					this.send(req);
				}
			},
			
			send : function(req){
				try { 
					if("user" in $scope) req.uid = $scope.user.uid;
					var message = angular.toJson(req);
					this.socket.send(message); 
				} catch(ex) { 
					logger(ex); 
				}
			},
			
			quit : function(){
				if (this.socket != null) {
					this.socket.close();
					this.socket=null;
				}
			},

			reconnect : function() {
				this.quit();
				this.init();
			}
		}
		
		
	
		
		
		$scope.init();

	}
	
]);



var Utilities = {
	
	getMapForPoint : function(point){
	
		// validate
		if(typeof point != 'object' || !('lat' in point) || !('lon' in point)){
			logger("invalid input"); console.log(point);
		}

		var map_url =
			"http://maps.googleapis.com/maps/api/staticmap?center=" 
			+ point.lat + ',' + point.lon + 
			"&zoom=14&size=300x200&sensor=false" +
			"&markers=color:blue|" + point.lat + "," + point.lon;
		return map_url;
	}
}
