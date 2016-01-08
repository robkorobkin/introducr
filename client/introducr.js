
// SAFE LOGGER
function logger(message){
	if('console' in window && 'log' in console){
		console.log(message);
	}
}


/*************************************************************
// GET GEOPOSITION
// USUALLY WORKS ON MOBILE - INCONSISTENT AT BEST OVER WI-FI
*************************************************************/		
var here = {
	hasLocation : false,
	active : true
}
function updateLocation(position){

	if(!here.active){
		logger("has position again");
		logger(position);	
		here.active = true;
	}


	here.hasLocation = true;
	here.lat = position.coords.latitude;
	here.lon = position.coords.longitude;
	if(appHandleUpdatedLocation) appHandleUpdatedLocation();
}


// IF WE GET A FAILURE, DEFAULT THEM TO FRANKLIN AND CONGRESS
// ToDo: GeoCode by City and store in user table
function locationFailure(error) { 

	if(here.active){
		logger("failure to geo-position");
		logger(error);	
		here.active = false;
	}

	here.hasLocation = true;
	here.lat = 43.661471;
	here.lon = -70.255326;
	if(appHandleUpdatedLocation) appHandleUpdatedLocation();
}

if (navigator.geolocation) {

	// not currently using options, but they're there
	// https://developer.mozilla.org/en-US/docs/Web/API/PositionOptions
	var options = {};

	navigator.geolocation.getCurrentPosition(updateLocation, locationFailure);
	navigator.geolocation.watchPosition(updateLocation, locationFailure);
} else {
	locationFailure("no browser support");
}








/*************************************************************
// THE APP!
*************************************************************/

var app = angular.module('introducrApp', ['LocalStorageModule']);

app.controller('introducrCtrl', ['$scope', '$http', '$sce', '$rootScope', '$window',  'localStorageService',
	function($scope, $http, $sce, $rootScope, $window, localStorageService){
		
		

		$scope.init = function(){

			$scope.dictionary = dictionary;
			$scope.fb_config = fb_config;
			$scope.here = here;

			$scope.loaded = false;
			$scope.sessionEstablished = false;
			$scope.loggedIntoFacebook = false;
			
			$scope.appName = 'introducr';
			$scope.view = 'loading';
			$scope.header = {
				show : false
			}
			$scope.footer = {
				show : false
			}
			$scope.user = {}; // placeholder - should load out of cookie

			// init socket controller
			$scope.socketController.init(socket_params);
			$scope.feedController.init();
			
			// bring up selected person
			$scope.currentPerson = {
				pics : {}
			}
			
			//  load state from cookie
			$scope.cookieMonster.load();

			// and launch app
			if($scope.sessionInCookie) {
				$scope.search.here = $scope.user.here;
				$scope.hereMapImg = $scope.getMapForPoint($scope.user.here);
				$scope.loadInitialView();
				$scope.apiClient.loginUser(); // runs in the background
			}
			else {
				$scope.cookieMonster.clear();
			}




			// now that we know we have a good access token, load facebook sdk
			$scope.loadFB();
			
		}
		
		
		
		// API CLIENT OBJECT
		$scope.apiClient = {
			
			postData : function(request, f){
				if("user" in $scope) request.uid = $scope.user.uid;
				$.post('introducr.php', request, function(response){
					if('error' in response && response.error == "logged out"){
						 $scope.cookieMonster.clear();
					}
					else if(f) f(response);
				}, 'json');
			},

			loginUser : function(){

				if(!("user" in $scope) || !("fbAccessToken" in $scope.user)) {
					logger("tried to login to app without a user in the cookie");
					return;
				}

				if(!("search" in $scope)) {
					logger("tried to before feed controller was initialized");
					return;
				}				

				//  now that we have an access token, renew server session (in the background)
				var request = {
					access_token: $scope.user.fbAccessToken,
					search_params: $scope.search,
					verb: "loginUser"
				}
				$scope.apiClient.postData(request, function(response){

					// if their token doesn't validate on our server, log them out
					if("error" in response){ 
						$scope.cookieMonster.clear();
					}

					// else update session
					else {
						$scope.user = response.user;
						
						// ToDo: Load feed from login response
						$scope.feedController.loadFeed(response.feedList.checkins);
						
						// if you're just logging in...
						if(!$scope.loaded){
							$scope.loadInitialView();
						}

						$scope.cookieMonster.save();
						$scope.socketController.register();	
					}

					$scope.$digest();
					
				});


			}
		
		}
		
		

		// VIEW MANAGER / ROUTER
		$scope.loadView = function(view, screen){

			logger("trying to load: " + view)
			
			if(view == "loading" || view == "login") $scope.footer.show = false;
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
			save : function(){
				if(here.hasLocation) $scope.user.here = here;
				localStorageService.set('user', $scope.user);

				var feedList = $scope.feedController.feedList;
				localStorageService.set('feedList', feedList);
			},
			load : function(){

				var user = localStorageService.get('user');
				var feedList = localStorageService.get('feedList');

				$scope.sessionInCookie = (user && 'isNew' in user);
				$scope.user = (user) ? user : {};
				$scope.feedList = (feedList) ? feedList : [];

			},
			clear : function(){
				localStorageService.set('user', {});
				$scope.user = {};
				$scope.loaded = false;
				$scope.sessionInCookie = false;

				$scope.acctController.loadLoginScreen();
				if($scope.loggedIntoFacebook) FB.logout();
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
				logger("trying to load login screen");
				$scope.loadView('login');
				
			},
			
			saveAndProgress : function(){
				if(this.sending) return;

				$scope.screen = parseInt($scope.screen);

				// validate
				var validate = {
					1 : ['first_name', 'last_name', 'email'],
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
					$scope.screen++;
					if($scope.screen == 3) {
						$scope.feedController.open();
					}
					$scope.$digest();
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

				this.status = "sending";

				if($scope.here.hasLocation){
					this.newCheckin.lat = $scope.here.lat;
					this.newCheckin.lon = $scope.here.lon;
				}

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
				
				$scope.search = {
					search_str : "",
					proximity : "1",
					gender: "",
					age: "",
					sort_order: "time",
					justFriends: false
				}

				this.feedList = [];
				
				this.request = {
					verb : "listCheckins"
				};
			
				this.setMode("feed");
			},

			search : function(){
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

					checkin.lastCheckinMap = $scope.getMapForPoint(checkin);
					
					self.feedList.push(checkin);
				});

				logger(this.feedList);

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
				$scope.selected_person.lastCheckin = checkin;
				$scope.loadView('person');

				// if no chat, show profile
				if(checkin.lastMessageDate == '0000-00-00 00:00:00'){
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

			confirmTransmission : function(message){
				
				var inConversation = false;

				// you just sent it
				if(message.senderId == $scope.user.uid){
					this.chats[message.targetId].conversation.push(message);
					if($scope.selected_person.uid == message.targetId){
						this.currentText.content = '';
						inConversation = true;
					}
				}

				// you just received it
				else if(message.targetId == $scope.user.uid){
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
					if($scope.prev == "feed") $scope.feedController.open();
					if($scope.prev == "recents") $scope.feedController.loadRecents();
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
						if("user" in $scope) $scope.socketController.register();
					};
							   
					this.socket.onmessage = function(envelope) { 
						
						var message = angular.fromJson(envelope.data);

						logger("new envelope in the mailbox");
						logger(message);

							
					
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
						uid : $scope.user.uid
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
		
		
		
		///////////////////////////////////////////////////////////////////////////////////
		// DATA MODEL STUFF
		
		$scope.parsePerson = function(person){
	
		}
		
		///////////////////////////////////////////////////////////////////////////////////
		// FACEBOOK CONNECTOR STUFF

		$scope.loadFB = function(){
		
			$window.fbAsyncInit = function() {
				FB.init({ 
					appId: $scope.fb_config.appId, 
					cookie: true,
					xfbml: true,
				    version: 'v2.5'
				});

				
				// on load / post-login, if you're logged in, update friends list
				FB.Event.subscribe('auth.authResponseChange', function(res) {

					if (res.status === 'connected') {
						$scope.loggedIntoFacebook = true;


						logger("connected to facebook")

						// if they're session is fresh
						if(!$scope.sessionInCookie) {
							logger("no cookie in session")
							$scope.loadView("loading");
							$scope.user.fbAccessToken = res.authResponse.accessToken;
						}

						// $scope.cookieMonster.save();						
					}


				});
			};


			// load the Facebook javascript SDK
			 (function(d, s, id){
			 var js, fjs = d.getElementsByTagName(s)[0];
			 if (d.getElementById(id)) {return;}
			 js = d.createElement(s); js.id = id;
			 js.src = "//connect.facebook.net/en_US/sdk.js";
			 fjs.parentNode.insertBefore(js, fjs);
		   }(document, 'script', 'facebook-jssdk'));
	
		}


		



		$scope.login = function() {	

			$scope.loadView("loading");
			
			// triggers authResponseChange
			FB.login(function(res){
				$scope.user = {
					fbAccessToken : res.authResponse.accessToken
				}				
				$scope.apiClient.loginUser();

			}, {scope: 'email,user_friends,user_about_me,user_location, user_birthday'});	
		}
	
		
	
		///////////////////////////////////////////////////////////////////////////////////
		// MAP STUFF
		$scope.getMapForPoint = function(point){


			var map_url =
				"http://maps.googleapis.com/maps/api/staticmap?center=" 
				+ point.lat + ',' + point.lon + 
				"&zoom=14&size=300x200&sensor=false" +
				"&markers=color:blue|" + point.lat + "," + point.lon;
			return map_url;
		}

		// update stored user information when geoposition updates (polls)
		appHandleUpdatedLocation = function(){

			//logger("handle updated location")


			// if we're logged in - update user w. new location
			if(('user' in $scope) && ("fbAccessToken" in $scope.user)) $scope.cookieMonster.save();
			$scope.hereMapImg = $scope.getMapForPoint(here);
			$scope.search.here = here;
		}
		
		
		
		$scope.init();

	}
	
]);

