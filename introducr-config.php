<?php

	// SET COOKIES TO PERSIST FOR A WEEK (UNLESS MANUALLY LOGGED OUT)
	// VITAL FOR MOBILE - OTHERWISE PERSON GETS LOGGED OUT EVERY TIME THEY CLOSE THE APP
	ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);  // 7 day cookie lifetime
	session_start();

	


	$config = array();
	if(strpos(getCwd(), "var") !== false) $environment = "live";
	else $environment = "local";
	
	switch($environment) {
	
		// localhost configuration
		case "local" : 
			$config["database"] = array(
				"servername" => 'localhost', 
				"username" => "root", 
				"password" => "root",
				"database" => "introducr"
			);
			$config['socket'] = array(
				"path" => "127.0.0.1",
				'port' => "9000"
			);
			$config["facebook"]["appId"] = '926145584145218';
			$config["facebook_secret"] = '69228433742c2423c28cdc924216ecd2';
		break;
	
		// development environment configuration
		case "live" : 
			$config["database"] = array(
				"servername" => "localhost",
				"username" => "root", 
				"password" => "7DP7BEDrsu",
				"database" => "introducr_live"
			);
			$config["facebook"]["appId"] = '545140225659094';
			$config['socket'] = array(
				"path" => "wells.machigonne.org",
				'port' => "9000"
			);
		break;

		
	}
		
	$config['dictionary']['us_state_abbrevs_names'] = array(
		'AL'=>'ALABAMA',
		'AK'=>'ALASKA',
		'AZ'=>'ARIZONA',
		'AR'=>'ARKANSAS',
		'CA'=>'CALIFORNIA',
		'CO'=>'COLORADO',
		'CT'=>'CONNECTICUT',
		'DE'=>'DELAWARE',
		'DC'=>'DISTRICT OF COLUMBIA',
		'FL'=>'FLORIDA',
		'GA'=>'GEORGIA',
		'HI'=>'HAWAII',
		'ID'=>'IDAHO',
		'IL'=>'ILLINOIS',
		'IN'=>'INDIANA',
		'IA'=>'IOWA',
		'KS'=>'KANSAS',
		'KY'=>'KENTUCKY',
		'LA'=>'LOUISIANA',
		'ME'=>'MAINE',
		'MD'=>'MARYLAND',
		'MA'=>'MASSACHUSETTS',
		'MI'=>'MICHIGAN',
		'MN'=>'MINNESOTA',
		'MS'=>'MISSISSIPPI',
		'MO'=>'MISSOURI',
		'MT'=>'MONTANA',
		'NE'=>'NEBRASKA',
		'NV'=>'NEVADA',
		'NH'=>'NEW HAMPSHIRE',
		'NJ'=>'NEW JERSEY',
		'NM'=>'NEW MEXICO',
		'NY'=>'NEW YORK',
		'NC'=>'NORTH CAROLINA',
		'ND'=>'NORTH DAKOTA',
		'OH'=>'OHIO',
		'OK'=>'OKLAHOMA',
		'OR'=>'OREGON',
		'PA'=>'PENNSYLVANIA',
		'PR'=>'PUERTO RICO',
		'RI'=>'RHODE ISLAND',
		'SC'=>'SOUTH CAROLINA',
		'SD'=>'SOUTH DAKOTA',
		'TN'=>'TENNESSEE',
		'TX'=>'TEXAS',
		'UT'=>'UTAH',
		'VT'=>'VERMONT',
		'VI'=>'VIRGIN ISLANDS',
		'VA'=>'VIRGINIA',
		'WA'=>'WASHINGTON',
		'WV'=>'WEST VIRGINIA',
		'WI'=>'WISCONSIN',
		'WY'=>'WYOMING'
	);

	$config['dictionary']['months'] = array(
		'1' => 'JAN',
		'2' => 'FEB',
		'3' => 'MAR',
		'4' => 'APR',
		'5' => 'MAY',
		'6' => 'JUN',
		'7' => 'JUL',
		'8' => 'AUG',
		'9' => 'SEP',
		'10' => 'OCT',
		'11' => 'NOV',
		'12' => 'DEC'
	);

	for($day = 1; $day <= 31; $day++) 	$config['dictionary']['days'][] = $day;
	
	for($year = 2015; $year >= 1915; $year--) $config['dictionary']['years'][] = $year;
	
	global $introducr_config;
	$introducr_config = $config;
