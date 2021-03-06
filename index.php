<?php

// I HATE HAVING SERVER-SIDE CODE IN MY CLIENT
// BUT THIS IS THE ONLY WAY TO AVOID CACHING

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="format-detection" content="telephone=yes"><!-- maybe have phone links? -->
		

		<!-- JQUERY \ ANGULAR -->	
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0-alpha1/jquery.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular.min.js"></script>
		<script src="client/third_party/angular-local-storage.min.js"></script>


		<!-- BOOTSTRAP -->
		<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.5/js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" />


		<!-- MATERIAL DESIGN -->
		<script type="text/javascript" src="client/third_party/b-md/arrive.min.js"></script>
		<script type="text/javascript" src="client/third_party/b-md/material.min.js"></script>
		<link rel="stylesheet" href="client/third_party/b-md/bootstrap-material-design.min.css" />
		<script type="text/javascript" src="client/third_party/b-md/ripples.min.js"></script>
		<link rel="stylesheet" href="client/third_party/b-md/ripples.min.css" />
		<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
		<script>$.material.init();</script>

	
		<!-- FONTS -->
		<link href="client/third_party/fonts/infiniti/stylesheet.css" rel="stylesheet" type="text/css" />		
		<link href='http://fonts.googleapis.com/css?family=Roboto:400,500' rel='stylesheet' type='text/css'>
		<link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>

		
		<!-- IOS STUFF -->
		<script src="client/third_party/inobounce.min.js"></script>

		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-title" content="zocalo">
		<link rel="shortcut icon" sizes="16x16" href="client/third_party/cubiq/images/icon-16x16.png">
		<link rel="shortcut icon" sizes="196x196" href="client/third_party/cubiq/images/icon-196x196.png">
		<link rel="apple-touch-icon-precomposed" href="client/third_party/cubiq/images/icon-152x152.png">
		<!--link rel="apple-touch-icon-precomposed" sizes="152x152" href="icon-152x152.png">
		<link rel="apple-touch-icon-precomposed" sizes="144x144" href="icon-144x144.png">
		<link rel="apple-touch-icon-precomposed" sizes="120x120" href="icon-120x120.png">
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="icon-114x114.png">
		<link rel="apple-touch-icon-precomposed" sizes="76x76" href="icon-76x76.png">
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="icon-72x72.png"-->
		<link rel="stylesheet" type="text/css" href="client/third_party/cubiq/addtohomescreen.css">
		<script src="client/third_party/cubiq/addtohomescreen.min.js"></script>
		<script>addToHomescreen({detectHomescreen: true});</script>


		<!-- APP -->
		<script src="server/zocalo_api.php?lib=js"></script>
		<link href="server/zocalo_api.php?lib=css" rel="stylesheet" type="text/css" />

	</head>
	
	<body  ng-app="zocaloApp" ng-controller="zocaloCtrl">


		<!-- CHAT CHROME -->
		<div class="chatHeader" ng-if="screen == 'chat'">
			<div class="left col-xs-2 col-sm-2">
				<a ng-click="chatController.goBack()" class="btn btn-default btn-fab btn-fab-micro">
					<i class="material-icons">keyboard_backspace</i>
				</a>
			</div>		
			<div class="center col-xs-8 col-sm-8">
				<div class="chatPersonName">{{selected_person.first_name}} {{selected_person.last_name}}</div>
			</div>
			<div class="right col-xs-2 col-sm-2" style="padding-right: 0">
				<img class="personIcon" ng-src="//graph.facebook.com/{{selected_person.fbid}}/picture" 
					ng-click="chatController.loadProfile()" />
			</div>
		</div>
		
		
		<!-- INTERNAL APP FRAME -->
		<div class="appFrame scrollable" >
		
			
			<!-- MAIN CONTENT -->
			<div class="mainContent">

				<!-- LOADING INTERFACE -->
				<div class="loadingFrame" ng-if="view=='loading'" >
					<div class="loginMain">
						<p>Loading...</p>
						<img src="client/images/loading.gif" />
					</div>
				</div>
		
				<!-- LOGIN INTERFACE -->
				<div class="loginFrame" ng-if="view=='login'" >
					<div class="loginTop">
						<div class="title">What is {{appName}}?</div>
					</div>
					<div class="loginMain">
						<p>Click below to start using {{appName}}</p>
						<div class="btnFrame">
							<a ng-click="apiClient.logIntoFacebook()">
								<img src="client/images/fbcnct.png" />
							</a>
						</div>
					</div>
				</div>
				
				<!-- MAKE CHECK-IN INTERFACE -->
				<div ng-if="view == 'checkIn'" class="primaryFrame checkinFrame" style="padding-top: 16px">
					<h2>Check In</h2>
					<div class="checkin_form">

						<div ng-if="user.hasSpots">
							<div class="form-group" style="margin: 0" ng-class="{'has-error': checkinController.needs.location}">
								<label class="control-label">Where are you?</label>
								<div class="btn-group-vertical">
									<a class="btn btn-raised" ng-repeat=""></a>
									
								</div>
							</div>
						</div>


						<div ng-if="!user.hasSpots">
							<div class="form-group" style="margin: 0" ng-class="{'has-error': checkinController.needs.location}">
								<label class="control-label" for="locationName">Where are you?</label>
								<input class="form-control" id="locationName" type="text" ng-model="checkinController.newCheckin.location" >
							</div>
							
							<div ng-if="here.hasLocation">
								<img ng-src="{{hereMapImg}}" class="checkinImage"/>
							</div>
						</div>

						<div class="form-group" ng-class="{'has-error': checkinController.needs.message}" style="margin-top: 8px">
							<label for="newcheckin_message" class="control-label">What's up?</label>
							<textarea 	class="form-control" id="newcheckin_message" ng-model="checkinController.newCheckin.message" maxlength="140"></textarea>
							<div style="height: 15px;">
								<span class="help-block">{{(140 - checkinController.newCheckin.message.length)}} chars remaining</span>
							</div>
						</div>
						<div style="margin: 5px; text-align: center;">
							<a class="btn btn-raised btn-success" ng-click="checkinController.submit()">Introduce Yourself</a>
						</div>
					</div>
				</div>
				
				<!-- ACCOUNT MANAGEMENT INTERFACE -->
				<div ng-if="view == 'account'" class=" primaryFrame" style="padding: 16px">
					<div ng-if="screen == 1">
						<div class="form-group" style="margin: 0" ng-class="{'has-error': acctController.needs.first_name}">
							<label class="control-label" for="user_first_name" >First Name</label>
							<input class="form-control" id="user_first_name" type="text" ng-model="user.first_name" ng-required>
						</div>
						<div class="form-group" style="margin: 0" ng-class="{'has-error': acctController.needs.last_name}">						 
							<label class="control-label" for="user_last_name">Last Name</label>
							<input class="form-control" id="user_last_name" type="text" ng-model="user.last_name">
						</div>

						<div class="form-group" style="margin: 0" ng-class="{'has-error': acctController.needs.email}">						 
							<label class="control-label" for="user_email">Email</label>
							<input class="form-control" id="user_email" type="text" ng-model="user.email">
						</div>

						<div class="row">
							<div class="col-sm-8 col-xs-8">
								<div class="form-group" style="margin: 0" ng-class="{'has-error': acctController.needs.city}">						 
									<label class="control-label" for="user_city">City</label>
									<input class="form-control" id="user_city" type="text" ng-model="user.city">
								</div>
							</div>
							<div class="col-sm-4 col-xs-4 form-group" style="padding-left: 0px">
								<label class="control-label" for="user_state">state</label>
								<select class="form-control" id="user_state" ng-model="user.state">
									<option ng-repeat="(abbrev, state) in dictionary.us_state_abbrevs_names" 	
											ng-value="state" ng-selected="state == user.state">{{abbrev}}</option>
								</select>
							</div>					
						</div>
						
						      
						<div class="row">
							<div class="col-sm-6 col-xs-6 form-group">
								<label for="user_gender" class="control-label">Gender?</label>
								<select id="user_gender" class="form-control" ng-model="user.gender" >
									<option value="MALE">Male</option>
									<option value="FEMALE">Female</option>
									<option value="OTHER">Other</option>
								</select>
							</div>
						</div>

						<div class="row">
							<div class="col-sm-4 col-xs-4 form-group" style="padding-right: 0">
								<label for="user_birthday_month" class="control-label">Month</label>
								<select id="user_birthday_month" class="form-control" ng-model="user.birthday.month" >
									<option ng-repeat="(monthNum, monthName) in dictionary.months"
											ng-value="monthNum" ng-selected="monthNum == user.birthday.month">{{monthName}}</option>
								</select>
							</div>
							<div class="col-sm-4 col-xs-4 form-group">									
								<label for="user_birthday_day" class="control-label">Day</label>
								<select id="user_birthday_day" class="form-control" ng-model="user.birthday.day" >
									<option ng-repeat="dayNum in dictionary.days"
											ng-value="dayNum" ng-selected="dayNum == user.birthday.day">{{dayNum}}</option>
								</select>
							</div>
							<div class="col-sm-4 col-xs-4 form-group" style="padding-left: 0">	
								<label for="user_birthday_year" class="control-label">Year</label>
								<select id="user_birthday_year" class="form-control" ng-model="user.birthday.year" >
									<option ng-repeat="year in dictionary.years"
											ng-value="year" ng-selected="year == user.birthday.year">{{year}}</option>
								</select>																								
							</div>					
						</div>
						<div style="margin: 5px; text-align: center;">
							<a class="btn btn-raised btn-success" ng-click="acctController.saveAndProgress()" ng-if="user.isNew">Next</a>
							<a class="btn btn-raised btn-success" ng-click="acctController.saveAndProgress()" ng-if="!user.isNew">Save</a>
						</div>
					</div>
					
					<div ng-if="screen == 2"> 
						<div class="form-group" ng-class="{'has-error': acctController.needs.bio}">
							<label for="user_bio" class="control-label">Who are you?</label>
							<textarea class="form-control" id="user_bio" ng-model="user.bio" maxlength="300"></textarea>
							<div style="height: 15px;">
								<span class="help-block">{{(300 - user.bio.length)}} chars remaining</span>
							</div>
						</div>
						<div style="margin: 5px; text-align: center;">
							<a class="btn btn-raised btn-success" ng-click="acctController.saveAndProgress()" ng-if="user.isNew">Start Meeting People</a>
							<a class="btn btn-raised btn-success" ng-click="acctController.saveAndProgress()" ng-if="!user.isNew">Save</a>
						</div>
					</div>

					<div ng-if="screen == 'menu'"> 
						<h2 style="text-align: center">My Account</h2>
						<div style="margin: 5px; text-align: center;">
							<a class="btn btn-raised btn-success" ng-click="acctController.loadScreen(1)">Edit Profile</a>
							<br /><br />
							<a class="btn btn-raised btn-success" ng-click="acctController.loadScreen(2)">Edit Bio</a>
							<br /><br />
							<a class="btn btn-raised btn-success" ng-click="apiClient.logoutUser()">Log Out</a>
						</div>
					</div>					
				</div>
				
				<!-- FEED INTERFACE -->
				<div ng-if="view == 'feed'">
					<div class="checkinList primaryFrame panel panel-default">

						<div ng-if="screen == 'feed'">
							<div class="checkin clearfix" ng-repeat="checkin in feedController.feedList"
								ng-click="chatController.openFromCheckin(checkin)">
								<div class="icon">
									<img ng-src="//graph.facebook.com/{{checkin.fbid}}/picture" />
								</div>
								<div class="text">
									<div class="name" ng-class="{unreadPerson : checkin.numUnread != 0}">
										{{checkin.first_name}} {{checkin.last_name}}
									</div>
									<div ng-if="feedController.mode=='feed'">
										<div ng-if="checkin.time">
											<div class="info">
												{{ checkin.dateObj | date:'MMM. d - h:mma'}} - {{checkin.location}}
											</div>
											<div class="message">{{checkin.message}}</div>
										</div>
									</div>
									<div ng-if="feedController.mode=='recents'">
										<div class="message">{{ checkin.dateRecentObj | date:'MMM. d - h:mma'}}</div>
									</div>
								</div>
							</div>
						</div>

						<div ng-if="screen == 'empty'" style="margin-top: 15px; padding: 10px 10px 5px;">
							<p>There are no checkins that meet your criteria.  Please revise the parameters of your search.</p>
						</div>

					</div>
				</div>  

				<!-- SEARCH INTERFACE -->
				<div ng-if="view == 'search'" class="primaryFrame" style="padding-top: 16px">
					<h2>Search</h2>

					<div class="form-group" style="margin: 0">						 
						<label class="control-label" for="search_str">Search by name?</label>
						<input class="form-control" id="search_str" type="text" ng-model="search.search_str">
					</div>
					
					<div class="form-group">
						<label for="search_proximity" class="control-label">Proximity?</label>
						<select id="search_proximity" class="form-control" ng-model="search.proximity" >
							<option value="1">1 Mile</option>
							<option value="5">5 Miles</option>
							<option value="20">20 Miles</option>
							<option value="">All</option>
						</select>
					</div>

					<div class="form-group">
						<label for="search_gender" class="control-label">Gender?</label>
						<select id="search_gender" class="form-control" ng-model="search.gender" >
							<option value="">All Genders</option>
							<option value="male">Male</option>
							<option value="female">Female</option>
							<option value="other">Other</option>
						</select>
					</div>

					<div class="form-group">
						<label for="search_age" class="control-label">Age?</label>
						<select id="search_age" class="form-control" ng-model="search.age" >
							<option value="">All Ages</option>
							<option value="_20">Under 20</option>
							<option value="20_30">20 to 30</option>
							<option value="30_40">30 to 40</option>
							<option value="40_">Over 40</option>
						</select>
					</div>

					<!-- CONTROL SORT ORDER
						<div class="form-group">
							<label for="search_sort_order" class="control-label">Sort Order?</label>
							<select id="search_sort_order" class="form-control" ng-model="search.sort_order" >
								<option value="time">Last Posted</option>
								<option value="abc">Alphabetical</option>
							</select>
						</div>
					-->
					
					<div class="form-group togglebutton" style="margin-top: 0;">
						<label ng-class="{toggledOn : search.justFriends}">
							<input checked="" type="checkbox" ng-model="search.justFriends"> Just My Friends
						</label>
					</div>
					
					<div style="margin: 5px; text-align: center;">
						<a class="btn btn-raised btn-success" ng-click="feedController.search()">Search</a>
					</div>

				</div>
				
				<!-- PERSON INTERFACE -->
				<div ng-if="view == 'person'" class="personView">
					<div ng-if="screen == 'profile'" class="primaryFrame">
						<h3>{{selected_person.first_name}} {{selected_person.last_name}}</h3>
						<img class="featureProfilePic" 
							ng-src="//graph.facebook.com/{{selected_person.fbid}}/picture?type=large" />
						<div >
							<a class="btn btn-raised btn-success messageBtn" ng-click="chatController.openChat()">Message</a>
						</div>
						<div class="profile">{{selected_person.bio}}</div>
						<div class="checkinInfo" ng-if="selected_person.time">
							<div>...</div>
							<b>Last Check-In:</b>
							{{selected_person.location}}	
							<img ng-src="{{selected_person.lastCheckinMap}}" class="checkinImage"/>	
							<div class="checkin_caption">
								{{selected_person.lastCheckin.dateObj | date:'MMM. d - h:ma'}}
								<div ng-if="selected_person.message != ''">{{selected_person.message}}</div>
							</div>
						</div>
						<div class="bottomLinks">
							<div>...</div>
							<a ng-click="chatController.blockUser()">Block</a>
							<a ng-click="chatController.reportUser()">Report</a>
						</div>
					</div>
					<div ng-if="screen == 'chat'" >
						<div class="dialogueFrame clearfix">
							<div class="speech" ng-repeat="message in currentConversation"
								ng-class="{
									me : message.senderId == user.uid,
									them : message.senderId != user.uid
								}">
								{{message.content}}
							</div>
						</div>
					</div>
				</div>
				
				
			</div>

		</div>

		<!-- FOOTER: MENU -->
		<div class="footer panel panel-default" ng-if="footer.show">
			<div class="footer_nav">
				<div class="link checkIn" ng-click="checkinController.open()"
					ng-class="{active : view == 'checkIn'}">
					<i class="glyphicon glyphicon-edit"></i>
					<div>Check-In</div>
				</div>
				<div class="link search" ng-click="loadView('search')"
					ng-class="{active : currentComponent == 'search'}">
					<i class="glyphicon glyphicon-search"></i>
					<div>Search</div>
				</div>
				<div class="link feed" ng-click="feedController.updateFeed('feed')"
					ng-class="{active : view == 'feed' && feedController.mode == 'feed' && (screen == 'feed' || screen == 'empty')}">
					<i class="glyphicon glyphicon-globe"></i>
					<div>Feed</div>
				</div>
				<div class="link recents" ng-click="feedController.updateFeed('recents')"
					ng-class="{active : currentComponent == 'feed' && feedController.mode == 'recents' && (screen == 'feed' || screen == 'empty')}">
					<div class="unreadChatsCount" ng-if="user.unreadChatsCount != 0">{{user.unreadChatsCount}}</div>
					<i class="glyphicon glyphicon-time"></i>
					<div>Recents</div>
				</div>
				<div class="link account" ng-click="loadView('account', 'menu')"
					ng-class="{active : currentComponent == 'account'}">
					<i class="glyphicon glyphicon-user"></i>
					<div>Account</div>
				</div>
			</div>
		</div>	
		
		<!-- FOOTER: CHAT INPUT -->		
		<div class="chatInput" ng-if="screen == 'chat'">
			<div class="col-sm-4 col-sm-offset-4 col-xs-12">
				<div class="col-sm-10 col-xs-10" style="padding-left: 0;">
					<textarea ng-model="chatController.currentText.content"></textarea>
				</div>
				<div class="col-sm-2 col-xs-2" style="padding: 0;">
					<a class="btn btn-success" ng-click="chatController.sendChat()">Send</a> 
				</div>
			</div>
		</div>

		
		<!-- MODAL TEMPLATES -->
		<div style="display: none;">

			
			
		</div>		

	</body>
</html>