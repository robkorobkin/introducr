var app = angular.module('PlatonikApp', ['ui.bootstrap']);

app.controller('PlatonikCtrl', ['$scope', '$http', '$sce', '$rootScope', '$window', '$modal',
	function($scope, $http, $sce, $rootScope, $window, $modal){
		$scope.init = function(){
			$scope.appName = 'Platonik';
			$scope.stage = 'login';
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
		}
		$scope.init();
		
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
	}
	
]);

app.controller('CheckinCtrl', ['$scope', '$http', '$sce', '$rootScope', '$window', '$modal',
	function($scope, $http, $sce, $rootScope, $window, $modal){
	}
]);