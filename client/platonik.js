
var platonik_config = {
	appId : '926145584145218'
}


var app = angular.module('PlatonikApp', ['ui.bootstrap']);

app.controller('PlatonikCtrl', ['$scope', '$http', '$sce', '$rootScope', '$window', '$modal',
	function($scope, $http, $sce, $rootScope, $window, $modal){
		$scope.init = function(){
			$scope.appName = 'Platonik';
			$scope.stage = 'loading';
			$scope.view = 'account';		
			$scope.header = {
				show : {
					atAll : true,
					left: true,
					center: true,
					right: false
				},
				
				// these should actually be $sce escaped
				text : {
					left : '&lt;',
					center: 'SIGN UP',
					right: ''
				}
			}
			$scope.footer = {
				show : {
					atAll : true
				}
			}
			
			$scope.currentPerson = {
				pics : {}
			}
			$scope.loadPerson();
			
			// load user authenticator
			$scope.fbData = {};
			$scope.loadFB();
			
		}
		
		$scope.acctMgr = {
			
			step : 1,
			
			charsRemaining : 300,
			
			loadAcct2 : function(){
				
				// save updated user
				
				// iterate to next screen
				this.step = 2;
			
			}
		
		
		}
		
		$scope.connect = function(){
			// put fb auth stuff here
			
			$scope.stage = 'app';
			$scope.loadView('account');
		}
		
		$scope.loadView = function(view, screen){
			$scope.view = view;
			if(screen) $scope.screen = screen;
		}
		
		$scope.checkinCreator = {
			
			open : function(){
				$modal.open({
					template: $('#modal_checkin').html(),
					controller: 'CheckinCtrl',
				});	
			}
			
		}
		
		
		$scope.loadPerson = function(){
			$scope.loadView('person', 'main');
			$scope.currentPerson.status = 'loading';
			$scope.currentPerson.id = 123;
			$scope.currentPerson.name = 'Rob Korobkin';
			$scope.currentPerson.bio = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam a massa suscipit, dapibus sapien at, condimentum dui. In hendrerit feugiat ullamcorper. Nunc tincidunt sed ex at vehicula. In eget enim nec quam pharetra tristique. Morbi eleifend quam at erat consectetur mattis. Mauris commodo lacus eu lorem accumsan auctor. Cras ut enim nulla. Integer ac rhoncus nibh, eget egestas risus. ';
			$scope.currentPerson.pics.big = 'https://scontent-lga3-1.xx.fbcdn.net/hphotos-xpf1/v/t1.0-9/11828667_717837171466_4063263757195261897_n.jpg?oh=7f99e3654cfb3a420042dec0301f6c89&oe=56B56344';
			
			
			
			// fetch data
		
		
		}
		
		
		// FACEBOOK CONNECTOR STUFF

		$scope.loadFB = function(){
		
			$window.fbAsyncInit = function() {
				FB.init({ 
					appId: platonik_config.appId, 
					xfbml: true,
				    version    : 'v2.5'
				});
				FB.getLoginStatus(function(response) {
					if(response.status == 'connected'){
					 	$scope.fbData.status = 'loggedIn';
					 }
					else {
						$scope.resetFb();
					}
					$scope.$digest();
				});
			
				FB.Event.subscribe('auth.authResponseChange', function(res) {
				
					if (res.status === 'connected') {
				
						$scope.fbData.access_token = res.authResponse.accessToken;
						console.log($scope.fbData.access_token);
						$scope.fbData.status = 'loggedIn';
						FB.api('/me?fields=first_name,email,last_name,middle_name,location', function(user) {
		
							if('location' in user) user.location = user.location.name;
							user.fbid = user.id;
							delete user.id;
							user.year = 0;
							user.track = 0;
							user.session = 0;				
							$scope.fbData.user = user;
						
							var request = {
								user: user,
								access_token: $scope.fbData.access_token,
								verb: "loginUser"
							}
							$.post('platonik.php', request, function(response){
								$scope.stage = 'app';
								$scope.view = 'account';
								$scope.$digest();
							}, 'json');
						
							//$scope.fetchUser(user.id, true)

							FB.api(user.id + '/friends', function(response) {
								$scope.fbData.friends = response;
							});
						});
					}
					else {
						$scope.resetFb();
					}

					$scope.$digest();
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
			}, {scope: 'email,user_friends,user_about_me,user_location,user_photos'});	
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
		
		
		
		
		
		$scope.init();

	}
	
]);

app.controller('CheckinCtrl', ['$scope', '$http', '$sce', '$rootScope', '$window', '$modal',
	function($scope, $http, $sce, $rootScope, $window, $modal){
	}
]);