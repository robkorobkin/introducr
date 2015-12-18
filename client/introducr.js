
// GET GEOPOSITION
var here = {
	hasLocation : false
}
function updateLocation(position){				
	here.hasLocation = true;
	here.lat = position.coords.latitude;
	here.lon = position.coords.longitude;
	here.img_url = 	
		"http://maps.googleapis.com/maps/api/staticmap?center=" + 
		here.lat + ',' + here.lon + 
		"&zoom=14&size=300x200&sensor=false" +
		"&markers=color:blue|" + here.lat + "," + here.lon;
}
if (navigator.geolocation) {
	navigator.geolocation.getCurrentPosition(updateLocation);
	navigator.geolocation.watchPosition(updateLocation);
} else {
	// browser does not support geo-positon
}




var app = angular.module('introducrApp', ['LocalStorageModule']);

app.controller('introducrCtrl', ['$scope', '$http', '$sce', '$rootScope', '$window',  'localStorageService',
	function($scope, $http, $sce, $rootScope, $window, localStorageService){
		

		$scope.init = function(){

			$scope.dictionary = dictionary;
			$scope.fb_config = fb_config;
			$scope.here = here;
			$scope.loaded = false;
			$scope.appName = 'introducr';
			$scope.view = 'loading';
			$scope.header = {
				show : false
			}
			$scope.footer = {
				show : false
			}

			// init socket controller
			$scope.socketController.init(socket_path);
			
			//  load state from cookie
			$scope.cookieMonster.load();
			
			// bring up selected person
			$scope.currentPerson = {
				pics : {}
			}
			
			// load user authenticator
			$scope.fbData = {};
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
			}
		
		}
		
		
		// COOKIE MONSTER - MAINTAIN STATE IN BETWEEN SESSIONS
		$scope.cookieMonster = {
			save : function(){
				localStorageService.set('user', $scope.user);
				$scope.socketController.register();
			},
			load : function(){
				var user = localStorageService.get('user');
				if(user) {
					$scope.user = user;
					$scope.feedController.open();
					$scope.loaded = true;
					$scope.socketController.register();					
				}
			},
			clear : function(){
				localStorageService.set('user', false);
				$scope.user = false;
				$scope.acctController.loadLoginScreen();
			}
		}
		

		// VIEW MANAGER / ROUTER
		$scope.loadView = function(view, screen){
			
			if(view == "loading" || view == "login") $scope.footer.show = false;
			else $scope.footer.show = true;
			
			$scope.view = view;
			if(screen) $scope.screen = screen;

			// update footer view
			$scope.currentComponent = view;
			
		}

		
		// ACCOUNT CONTROLLER
		$scope.acctController = {
			
			step : 1,
			
			charsRemaining : 300,
			
			loadLoginScreen : function(){
				$scope.header.show = false;
				$scope.loadView('login');
				$scope.$digest();
			},
			
			saveAndProgress : function(){

				// save updated user
				var request = {
					user: $scope.user,
					verb: 'updateUser'
				}				
				$scope.apiClient.postData(request, function(user){
					$scope.user = user;
					$scope.cookieMonster.save();
				});
				
				// iterate to next screen
				this.step++;
				
				if(this.step == 3) {
					$scope.loadView('browse');
					this.step = 0;
				}			
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
				this.status = "sending";

				if($scope.here.hasLocation){
					this.newCheckin.lat = $scope.here.lat;
					this.newCheckin.lon = $scope.here.lon;
				}

				var request = {
					verb 	 : "postCheckin",
					newCheckin : this.newCheckin
				}
			
				$scope.apiClient.postData(request, function(response){
					$scope.user = response.user;
					this.status = "sent";
				});			
			
			}
		}
		
		
		// FEED MANAGER
		$scope.feedController = {
			
			open : function(){

				this.status = "loading";
				$scope.loadView("feed");
			
				var request = {
					verb 	 : "listCheckins",
				}
			
				$scope.apiClient.postData(request, function(response){
					$scope.feedController.feedList = [];
					
					$.each(response.checkins, function(index, checkin){
						var t = checkin.time.split(/[- :]/);
						checkin.dateObj = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);

						$scope.feedController.feedList.push(checkin);
					});

					this.status = "loaded";
					$scope.$digest();
				});			

			},
			
			submit : function(){
				this.status = "sending";

				if($scope.here.hasLocation){
					this.newCheckin.lat = $scope.here.lat;
					this.newCheckin.lon = $scope.here.lon;
				}

				var request = {
					verb 	 : "postCheckin",
					newCheckin : this.newCheckin
				}
			
				$scope.apiClient.postData(request, function(response){
					$scope.user = response.user;
					this.status = "sent";
				});			
			
			}
		}
		
		
		// PERSON MANAGER
		$scope.chatController = {

			chats : {},

			openFromCheckin : function(checkin){
				$scope.screen = 'profile'; // if chat already going, go straight to chat
				$scope.selected_person = checkin;
				$scope.selected_person.lastCheckin = checkin;
				$scope.loadView('person');
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
			}

		}
		
		
		///////////////////////////////////////////////////////////////////////////////////
		// SOCKET STUFF

		$scope.socketController = {
			
			isOpen : false,
		
			init : function(socketPath) {
				var host = socketPath; 
				try {
					this.socket = new WebSocket(host);
					this.socket.self = this;
					
					this.socket.onopen = function(msg) { 
						$scope.socketController.isOpen = true;
						if("user" in $scope) $scope.socketController.register();
					};
							   
					this.socket.onmessage = function(envelope) { 
						var message = angular.fromJson(envelope.data);

						console.log("new envelope in the mailbox");
						console.log(message);

						if(message.status == "success"){
							switch(message.subject){
								case "message sent" : case "new message" :
									$scope.chatController.confirmTransmission(message.body.message);
								break;


							}
						}
					
					};
							   
					this.socket.onclose   = function(msg) { 
						// can we disconnect the user from the server-side hash?
					};
					
				}
				catch(ex){ 
					console.log(ex); 
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
					console.log(ex); 
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
					xfbml: true,
				    version: 'v2.5'
				});
				FB.getLoginStatus(function(response) {
					if(response.status == 'connected'){
					 	$scope.fbData.status = 'loggedIn';
					 }
					else {
						$scope.resetFb();
					}
				});
			
				FB.Event.subscribe('auth.authResponseChange', function(res) {
				
					if (res.status === 'connected') {
				
						$scope.fbData.access_token = res.authResponse.accessToken;
						$scope.fbData.status = 'loggedIn';
						FB.api('/me', function(user) {
		
							var request = {
								access_token: $scope.fbData.access_token,
								verb: "loginUser"
							}
							
							$scope.apiClient.postData(request, function(response){
								$scope.user = response.user;
								$scope.cookieMonster.save();
							
								
								if(!$scope.loaded){							
									if($scope.user.isNew){
										$scope.loadView('account');
									}
									else {
										$scope.loadView('feed');
									}
									$scope.$digest();
								}	
							});
						
							FB.api(user.id + '/friends', function(response) {
								$scope.fbData.friends = response;
							});
						});
					}
					else {
						$scope.resetFb();
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
			
			// triggers authResponseChange
			FB.login(function(){
			}, {scope: 'email,user_friends,user_about_me,user_location, user_birthday'});	
		}
	
		$scope.logout = function(){
			FB.logout();	// triggers authResponseChange
			$scope.cookieMonster.clear();
		 }
	
		// reset user obj, called on auth change to neg
		$scope.resetFb = function(){
			$scope.fbData = {
				status: 'unconnected'
			}
			$scope.cookieMonster.clear();
		}

		///////////////////////////////////////////////////////////////////////////////////
		
		
		
		
		$scope.init();

	}
	
]);

