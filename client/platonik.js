
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




var app = angular.module('PlatonikApp', ['LocalStorageModule']);

app.controller('PlatonikCtrl', ['$scope', '$http', '$sce', '$rootScope', '$window',  'localStorageService',
	function($scope, $http, $sce, $rootScope, $window, localStorageService){
		

		$scope.init = function(){

			$scope.dictionary = dictionary;
			$scope.fb_config = fb_config;
			$scope.here = here;
			$scope.loaded = false;
			$scope.appName = 'Platonik';
			$scope.view = 'loading';
			$scope.header = {
				show : false
			}
			$scope.footer = {
				show : true
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
				request.uid = $scope.user.uid;
				$.post('platonik.php', request, function(response){
					if(f) f(response);
				}, 'json');
			}
		
		}
		
		
		// COOKIE MONSTER - MAINTAIN STATE IN BETWEEN SESSIONS
		$scope.cookieMonster = {
			save : function(){
				localStorageService.set('user', $scope.user);
			},
			load : function(){
				var user = localStorageService.get('user');
				console.log(user);
				if(user) {
					$scope.user = user;
					$scope.feedController.open();
					$scope.loaded = true;
				}
			}
		}
		

		// VIEW MANAGER / ROUTER
		$scope.loadView = function(view, screen){
				
			$scope.view = view;
			if(screen) $scope.screen = screen;

			// update footer view
			$('.footer .link').removeClass('active');
			$('.footer .' + view).addClass('active');	
			
		}

		
		// ACCOUNT CONTROLLER
		$scope.acctController = {
			
			step : 1,
			
			charsRemaining : 300,
			
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
		$scope.personController = {

			openFromCheckin : function(checkin){
				$scope.screen = 'profile'; // if chat already going, go straight to chat
				$scope.selected_person = checkin;
				$scope.selected_person.lastCheckin = checkin;
				$scope.loadView('person');
			},
		
			openChat : function(){
				$scope.screen = 'chat';
				$scope.footer.show = false;
				$scope.header.show = true;
			}
		
		
		}
		
		
		///////////////////////////////////////////////////////////////////////////////////
		// SOCKET STUFF

		$scope.socketController = {
		
			init : function(socketPath) {
				var host = socketPath; // SET THIS TO YOUR SERVER
				try {
					this.socket = new WebSocket(host);
					
					this.socket.onopen = function(msg) { 
					};
							   
					this.socket.onmessage = function(msg) { 
					};
							   
					this.socket.onclose   = function(msg) { 
					};
					
				}
				catch(ex){ 
					console.log(ex); 
				}
			},

			send : function(){
				try { 
					this.socket.send(msg); 
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
								if($scope.user.isNew && !$scope.loaded){
									$scope.loadView('account');
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
			}, {scope: 'email,user_friends,user_about_me,user_location,user_photos, user_birthday'});	
		}
	
		$scope.logout = function(){
			FB.logout();	// triggers authResponseChange
			$scope.loadInterface('good_bye');
			$scope.user = {};
		 }
	
		// reset user obj, called on auth change to neg
		$scope.resetFb = function(){
			$scope.fbData = {
				status: 'unconnected'
			}
			$scope.stage = 'login';
			$scope.$digest();

		}

		///////////////////////////////////////////////////////////////////////////////////
		
		
		
		
		$scope.init();

	}
	
]);

