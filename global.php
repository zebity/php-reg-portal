<?php
//
// File: global.php
//
// Description: This file is part of PHP RegPortal, which is derived
//               from Deadlock PHP User Management System.
//               The changes made to this include revisions to support using
//               PDO for database operations and support for Apache httpd.conf
//               based configuration and having authenticaiton via
//               mod_dbd/mod_authn_dbd, rather than .htaccess and .htpasswd.
//               This has resulted in significant rewrite of base code, hence
//               new project, which is not backward compatible with Deadlock
//
// Note: All original Deadlock notices remain intact and this code is in
//         turn provided under GNU General Public Licence V3 as per
//         Deadlock code.
//         The Tux Logo has been removed and replaceid with MySQL & PostgreSQL
//         logo's as key aim was to make this version RDBMS agnostic via
//         PHPs PDO functions.
//         The new RegPortal logo includes original Deadlock logo (in miniture)
//         in recognition of the fact that this is derived work.
//         The Apache logo is included in the new logo, as this program
//         generates Apache specific outputs... and assumes Apache is the
//         underlying Web Server. However this code is not ensured
//         or otherwise related to Apache Software Foundation.
//
/******************************************************************************
* This file is part of the Deadlock PHP User Management System.               *
*                                                                             *
* File Description: Show information for a specific user                      *
*                                                                             *
* Deadlock is free software; you can redistribute it and/or modify            *
* it under the terms of the GNU General Public License as published by        *
* the Free Software Foundation; either version 2 of the License, or           *
* (at your option) any later version.                                         *
*                                                                             *
* Deadlock is distributed in the hope that it will be useful,                 *
* but WITHOUT ANY WARRANTY; without even the implied warranty of              *
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               *
* GNU General Public License for more details.                                *
*                                                                             *
* You should have received a copy of the GNU General Public License           *
* along with Deadlock; if not, write to the Free Software                     *
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA  *
******************************************************************************/

// make sure we are not in an install script
if(!strstr($_SERVER['PHP_SELF'],'install')){
	// make sure deadlock is installed, if not, send the user to the install page
	if(!defined("DEADLOCK_INSTALLED")){
		redirect('../install/install.php?db_config_path=' . $db_config_path);
	}

	// make sure the install directory does not exist
	// if(file_exists('../install/')){
 	//	die('Please remove the install directory before continuing.');
	//}
}

// if the user stops the script, it will continue to run. this is needed especially to generate a large htpasswd or to send bulk mail
ignore_user_abort(true);
// this is to prevent the script from timing out for the same reasons as above
@set_time_limit(0);

// str_ireplace() for php 4
require_once('func/str_ireplace.php');

// get the software version
require_once('version.php');

//
// check if need .htXXX writing stuff
//
if ($pdodb['mechanism'] == 'htaccess') {
	include('global-ht.php');
}

if ($pdodb['provider'] == 'mysql') {
	include('global-mysql.php');
}
elseif ($pdodb['provider'] == 'pgsql') {
	include('global-pgsql.php');

}
else {
	die("RegPortal unable to continue, due to unsupported RDBMS provider: " . $pdodb['provider']);
}

// if magic quotes gpc is enabled, this will removed the slashes from certain variables
if (get_magic_quotes_gpc()) {
	$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST, &$_FILES);
	while (list($key, $val) = each($process)) {
		foreach ($val as $k => $v) {
			unset($process[$key][$k]);
			if (is_array($v)) {
				$process[$key][($key < 5 ? $k : stripslashes($k))] = $v;
				$process[] =& $process[$key][($key < 5 ? $k : stripslashes($k))];
			} else {
				$process[$key][stripslashes($k)] = stripslashes($v);
			}
		}
	}
}


// Turn debugging on or off
function debug_mode($setting){
	if($setting){
		ini_set('display_errors','On');
	} else {
		ini_set('display_errors','Off');
	}
}


// Encrypt a password for a .htpasswd file.
function enc_pass($pass,$digest=false,$digestuser=null,$digestrealm=null)
{
	if($digest){
		$pass = md5($digestuser.':'.$digestrealm.':'.$pass);
		return $pass;
	} else {
		if (CRYPT_STD_DES == 1) {
			$pass = crypt(trim($pass), random_string(2,1,0,0));
			return $pass;
		}
	}
}

// This function generates a menu of countries and allows you to have one selected.
function country_menu ($selected){
	$countries = array("Not Selected",
	"Afghanistan",
	"Albania",
	"Algeria",
	"American Samoa",
	"Andorra",
	"Angola",
	"Anguilla",
	"Antarctica",
	"Antigua and Barbuda",
	"Argentina",
	"Armenia",
	"Aruba",
	"Australia",
	"Austria",
	"Azerbaijan",
	"Bahamas",
	"Bahrain",
	"Bangladesh",
	"Barbados",
	"Belarus",
	"Belgium",
	"Belize",
	"Benin",
	"Bermuda",
	"Bhutan",
	"Bolivia",
	"Bosnia and Herzegovina",
	"Botswana",
	"Bouvet Island",
	"Brazil",
	"British Indian Ocean Terr.",
	"Brunei Darussalam",
	"Bulgaria",
	"Burkina Faso",
	"Burundi",
	"Cambodia",
	"Cameroon",
	"Canada",
	"Cape Verde",
	"Cayman Islands",
	"Central African Republic",
	"Chad",
	"Chile",
	"China",
	"Christmas Island",
	"Cocos (Keeling) Islands",
	"Colombia",
	"Comoros",
	"Congo",
	"Cook Islands",
	"Costa Rica",
	"Cote d'Ivoire",
	"Croatia (Hrvatska)",
	"Cuba",
	"Cyprus",
	"Czech Republic",
	"Denmark",
	"Djibouti",
	"Dominica",
	"Dominican Republic",
	"East Timor",
	"Ecuador",
	"Egypt",
	"El Salvador",
	"Equatorial Guinea",
	"Eritrea",
	"Estonia",
	"Ethiopia",
	"Falkland Islands/Malvinas",
	"Faroe Islands",
	"Fiji",
	"Finland",
	"France",
	"France, Metropolitan",
	"French Guiana",
	"French Polynesia",
	"French Southern Terr.",
	"Gabon",
	"Gambia",
	"Georgia",
	"Germany",
	"Ghana",
	"Gibraltar",
	"Greece",
	"Greenland",
	"Grenada",
	"Guadeloupe",
	"Guam",
	"Guatemala",
	"Guinea",
	"Guinea-Bissau",
	"Guyana",
	"Haiti",
	"Heard & McDonald Is.",
	"Honduras",
	"Hong Kong",
	"Hungary",
	"Iceland",
	"India",
	"Indonesia",
	"Iran",
	"Iraq",
	"Ireland",
	"Israel",
	"Italy",
	"Jamaica",
	"Japan",
	"Jordan",
	"Kazakhstan",
	"Kenya",
	"Kiribati",
	"Korea, North",
	"Korea, South",
	"Kuwait",
	"Kyrgyzstan",
	"Lao People's Dem. Rep.",
	"Latvia",
	"Lebanon",
	"Lesotho",
	"Liberia",
	"Libyan Arab Jamahiriya",
	"Liechtenstein",
	"Lithuania",
	"Luxembourg",
	"Macau",
	"Macedonia",
	"Madagascar",
	"Malawi",
	"Malaysia",
	"Maldives",
	"Mali",
	"Malta",
	"Marshall Islands",
	"Martinique",
	"Mauritania",
	"Mauritius",
	"Mayotte",
	"Mexico",
	"Micronesia",
	"Moldova",
	"Monaco",
	"Mongolia",
	"Montserrat",
	"Morocco",
	"Mozambique",
	"Myanmar",
	"Namibia",
	"Nauru",
	"Nepal",
	"Netherlands",
	"Netherlands Antilles",
	"New Caledonia",
	"New Zealand",
	"Nicaragua",
	"Niger",
	"Nigeria",
	"Niue",
	"Norfolk Island",
	"Northern Mariana Is.",
	"Norway",
	"Oman",
	"Pakistan",
	"Palau",
	"Panama",
	"Papua New Guinea",
	"Paraguay",
	"Peru",
	"Philippines",
	"Pitcairn",
	"Poland",
	"Portugal",
	"Puerto Rico",
	"Qatar",
	"Reunion",
	"Romania",
	"Russian Federation",
	"Rwanda",
	"S.Georgia & S.Sandwich Is.",
	"Saint Kitts and Nevis",
	"Saint Lucia",
	"Samoa",
	"San Marino",
	"Sao Tome & Principe",
	"Saudi Arabia",
	"Senegal",
	"Seychelles",
	"Sierra Leone",
	"Singapore",
	"Slovakia (Slovak Republic)",
	"Slovenia",
	"Solomon Islands",
	"Somalia",
	"South Africa",
	"Spain",
	"Sri Lanka",
	"St. Helena",
	"St. Pierre & Miquelon",
	"St. Vincent & Grenadines",
	"Sudan",
	"Suriname",
	"Svalbard & Jan Mayen Is.",
	"Swaziland",
	"Sweden",
	"Switzerland",
	"Syrian Arab Republic",
	"Taiwan",
	"Tajikistan",
	"Tanzania",
	"Thailand",
	"Togo",
	"Tokelau",
	"Tonga",
	"Trinidad and Tobago",
	"Tunisia",
	"Turkey",
	"Turkmenistan",
	"Turks & Caicos Islands",
	"Tuvalu",
	"U.S. Minor Outlying Is.",
	"Uganda",
	"Ukraine",
	"United Arab Emirates",
	"United Kingdom",
	"United States",
	"Uruguay",
	"Uzbekistan",
	"Vanuatu",
	"Vatican (Holy See)",
	"Venezuela",
	"Vietnam",
	"Virgin Islands (British)",
	"Virgin Islands (U.S.)",
	"Wallis & Futuna Is.",
	"Western Sahara",
	"Yemen",
	"Yugoslavia",
	"Zaire",
	"Zambia",
	"Zimbabwe");

	$menu_code = '<select name="country">'."\n";
	foreach ($countries as $country){
		if($selected == $country) $select_text = ' selected="selected"'; else $select_text = NULL;
		$menu_code .= '<option value="'.$country.'"'.$select_text.'>'.htmlentities($country).'</option>'."\n";
	}
	$menu_code .= '</select>';
	return $menu_code;
}

// A mail function which makes it easier to provide a from address.
function sendmail ($to,$from,$subject,$message,$html=false){
	$headers = "From: {$from}\r\n";
	if($html){
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	}
	if(mail($to,$subject,$message,$headers)){
		return true;
	} else {
		return false;
	}
}


// random string generator
function random_string($len,$lett=1,$num=1,$cap=1) {
	srand(date("s"));
	$possible="";
	if($lett){
		$possible.="abcdefghijklmnopqrstuvwxyz";
		if($cap){
			$possible.="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		}
	}
	if($num){
		$possible.="1234567890";
	}
	$str="";
	while(strlen($str)<$len) {
		$str.=substr($possible,(rand()%(strlen($possible))),1);
	}
	return($str);
}

// This generates a string of x number of *s
function password_filler($password){
	$len = strlen($password);
	$string = '';
	for($i=0; $i<$len; $i++){
		$string .= '*';
	}
	return $string;
}

// Check to see if a user exists in the database
// Return true if the user exists
function check_user_exists($dbh,$username,$prefix){
	$result = $dbh->query('SELECT id FROM '.$prefix.'users WHERE username=' . $dbh->quote($username));
	if($result->rowCount() == 0){
		return false;
	} else {
		return true;
	}
}

// Check to see if a verification code is correct for a certain user
// Return true if it is correct
function check_verification_code($dbh,$username,$code,$prefix){
	$result = $dbh->query('SELECT id FROM '.$prefix.'users WHERE username=' . $dbh->quote($username) . ' and email_verify_code='.PDO::quote($code));
	if($result->rowCount() == 0){
		return false;
	} else {
		return true;
	}
}

// this is to authenticate a user. this checks to see if a username and password combo exist in the database.
// returns true if the combo exists.
function check_login_info($dbh,$username,$password,$prefix){
	$result = $dbh->query('SELECT id FROM '.$prefix.'users WHERE username='.$dbh->quote($username).' and password='.PDO::quote($password));
	if($result->rowCount() == 0){
		return false;
	} else {
		return true;
	}
}

// Update a user's status
function UpdateUserStatus($dbh,$username,$newstatus,$prefix){
	$sql = 'UPDATE '.$prefix.'users SET status='.$newstatus.' WHERE username='.$dbh->quote($username);
	if($dbh->query($sql)){
		return true;
	} else {
		db_failure($dbh, $dbh->errorInfo);
	}
}

// get a user's current status
function GetCurrentStatus($dbh,$username,$prefix){
	$sql = 'SELECT status FROM '.$prefix.'users WHERE username='.$dbh->quote($username);
	if(!$result=$dbh->query($sql)){
		db_failure($dbh, $dbh->errorInfo);
	}
	$row = $result->fetch(PDO::FETCH_ASSOC);
	return $row[0];
}

// Check to see if an email address exists in the database
// Return true if the user exists
// the $username argument is for the configuration page. if it is set, if the user that has the email specified is $username, the script will return false
function check_email_exists($dbh,$email,$prefix,$username=''){
	if(empty($username)){
		$result = $dbh->query('SELECT id FROM '.$prefix.'users WHERE email='.$dbh->quote($email));
	} else {
		$result = $dbh->query('SELECT id FROM '.$prefix.'users WHERE email='.$dbh->quote($email).' and username !='.PDO::quote($username));
	}
	if($result->rowCount() == 0){
		return false;
	} else {
		return true;
	}
}

// Count the number of users in the database
function count_users ($dbh,$prefix){
	$result = $dbh->query('SELECT id FROM '.$prefix.'users WHERE status=2');
	return $result->rowCount();
}

// count the number of users who have no verified their email
function count_inactive_users ($dbh,$prefix){
	$result = $dbh->query('SELECT id FROM '.$prefix.'users WHERE status=0');
	return $result->rowCount();
}

function UpdateUserField($dbh,$username,$field,$value,$prefix){
	$sql = 'UPDATE '.$prefix.'users SET '.$field.' = '.$dbh->quote($value).' WHERE username = '.$dbh->quote($username);
	if(!$dbh->query($sql)){
		db_failure($dbh, $dbh->errorInfo, $sql);
	}
}

function FormatPhoneNumber($phone){
	if(strlen($phone) != 10){
		return($phone);
	}
	$area = substr($phone,0,3);
	$prefix = substr($phone,3,3);
	$number = substr($phone,6,4);
	$phone = "(".$area.") ".$prefix." ".$number;
	return($phone);
}

function count_pending_users($dbh,$prefix){
	$result = $dbh->query('SELECT id FROM '.$prefix.'users WHERE status=1');
	return $result->rowCount();
}

// This function gets the email body and subject from the text files in /emails
function get_email_subject ($dbh,$prefix,$email_name){
	$sql = 'SELECT subject FROM '.$prefix.'emails WHERE name='.$dbh->quote($email_name);
	if(!$result = $dbh->query($sql)){
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
	$row = $result->fetch(PDO::FETCH_ASSOC);
	$output = $row['subject'];
	return $output;
}

function get_email_body ($dbh,$firstname,$lastname,$email,$username,$password,$login_url,
			$deadlock_url,$admin_email,$prefix,$email_name){
	$sql = 'SELECT body FROM '.$prefix.'emails WHERE name='.$dbh->quote($email_name);
	if(!$result = $dbh->query($sql)){
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
	$row = $result->fetch(PDO::FETCH_ASSOC);
	$output = $row['body'];
	$output = str_ireplace(
	array('<%FirstName%>','<%LastName%>','<%Email%>','<%Username%>','<%Password%>','<%LoginURL%>','<%AdminEmail%>','<%RegPortalURL%>'),
	array($firstname,$lastname,$email,$username,$password,$login_url,$admin_email,$deadlock_url), $output);
	return $output;
}

// gets the body of an email, and replaces codes with values from the database
// if the send veriable is set to true, not only will the script genertate the email, but it will send it
function get_email_body_sql($dbh,$emailname,$username,$prefix,$send=false){
	$sql = 'SELECT body FROM '.$prefix.'emails WHERE name='.$dbh->quote($emailname);
	if(!$result = $dbh->query($sql)){
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
	$row = $result->fetch(PDO::FETCH_ASSOC);
	$output = stripslashes($row['body']);

	$config = get_config($prefix);

	$sql = 'SELECT * FROM '.$prefix.'users WHERE username='.$dbh->quote($username);
	if($result = $dbh->query($sql)){
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$output = str_ireplace(
			array('<%FirstName%>','<%LastName%>','<%Email%>','<%Username%>','<%Password%>','<%LoginURL%>','<%AdminEmail%>','<%RegPortalURL%>'),
			array($row['firstname'],$row['lastname'],$row['email'],$row['username'],$row['password'],$config['protected_area_url'],$config['admin_email'],$config['deadlock_url']), $output);
			if($send){
				sendmail($row['email'],$config['admin_email'],get_email_subject($prefix,$emailname),$output);
			}
		}
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
	return $output;
}

// this function adds the email verification code to the database
function AddEmailVerifyCode($dbh,$username,$code,$prefix){
	$sql = 'UPDATE '.$prefix.'users SET email_verify_code = '.$dbh->quote($code).' WHERE username = '.PDO::quote($username).' LIMIT 1;';
	if(!$dbh->query($sql)){
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
}

// This function removes a user from the mysql database
// $username- the user to remove,  $prefix- the prefix of the users table
function remove_user ($dbh,$username,$table_prefix){
	$sql = 'DELETE FROM '.$table_prefix.'users WHERE username = '.$dbh->quote($username);
	if($dbh->query($sql)){
		return true;
	} else {
		return false;
	}
}

// Starts session
function admin_sessions ($expire,$param){
	// set the session name so it does not conflict
	session_name('admin_sid');
	// Start the session
	session_start();

	// check to see if the current session has expired
	if(isset($_SESSION['start_time'])){
		if((time() - $_SESSION['start_time']) > $expire){
			session_destroy();
			redirect('./login.php' . $param);
		}
	}

	// if session has not expired, set session start time
	$_SESSION['start_time'] = time();
}

// This will generate an html dropdown menu of all users in the database.
// This is used on the email page to generate the menu.
function generate_user_menu ($dbh,$prefix,$selected){
	$sql ='SELECT * FROM '.$prefix.'users WHERE status=2 ORDER BY username';
	if($result = $dbh->query($sql)){
		if($result->rowCount() > 0){
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$username[] = stripslashes($row['username']);
				$id[] = stripslashes($row['id']);
				$name[] = stripslashes($row['firstname']).' '.stripslashes($row['lastname']);
			}
			$code = null;
			for($i=0;$i<$result->rowCount();$i++){
				if($selected == $username[$i]) $select = ' selected="selected"'; else $select=null;
				$code .= '<option value="'.$id[$i].'"'.$select.'>'.$name[$i]. ' - ' .$username[$i].'</option>'."\n";
			}
			return $code;
		} else {
			return null;
		}
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
}

// generate request list rows
function generate_request_list($dbh,$prefix){
	$sql = 'SELECT * FROM '.$prefix.'users WHERE status=1 ORDER BY lastname';
	if($result = $dbh->query($sql)){
		if($result->rowCount() > 0){
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$username[] = stripslashes($row['username']);
				$email[] = stripslashes($row['email']);
				$name[] = stripslashes($row['lastname']).', '.stripslashes($row['firstname']);
			}
			$code = "";
			for($i=0;$i < $result->rowCount();$i++){
				$code .= '<tr class="style2"><td>'.$name[$i].'</td><td>'.$username[$i].'</td><td>'.$email[$i].'</td><td><a href="./userinfo.php?user='.$username[$i].'&ref=request"><img src="../images/info15px.gif" alt="Info" border="0" title="More Information" /></a> <a href="#" onclick="denyuser(\''.$username[$i].'\')"><img src="../images/delete15px.gif" alt="Deny" border="0" title="Deny" /></a> <a href="#" onclick="acceptuser(\''.$username[$i].'\')"><img src="../images/accept15px.gif" alt="Accept" border="0" title="Accept" /></a></tr>'."\n";
			}
			return $code;
		} else {
			return '<tr><td colspan="4"><span class="style11">There are currently no users pending approval.</span></td></tr>';
		}
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
}

// this function is used on the configuration page to check whether or not a checkbox field should be checked by default
function ConfigCheckboxCheck($Submitted,$PostField,$ConfigOption){
	if(isset($Submitted)){
		if(isset($PostField)){
			return 'checked="checked" ';
		} else {
			return '';
		}
	} else {
		if($ConfigOption == 'true'){
			return 'checked="checked" ';
		} else {
			return '';
		}
	}
}

// this function is used on the configuration page to print out the date format selection menu
function ConfigDateSelects($PostField,$ConfigOption){
	$date_formats = array('D d M, Y','D d M, Y g:i a','D d M, Y H:i','D M d, Y','D M d, Y g:i a','D M d, Y H:i','jS F Y','jS F Y, g:i a','jS F Y, H:i','F jS Y','F jS Y, g:i a','F jS Y, H:i','j/n/Y','j/n/Y, g:i a','j/n/Y, H:i','n/j/Y','n/j/Y, g:i a','n/j/Y, H:i','Y-m-d','Y-m-d, g:i a','Y-m-d, H:i');
	$current_time = time();
	$buffer = '';
	if(empty($PostField)){
		$selected_format = $ConfigOption;
	} else {
		$selected_format = $PostField;
	}
	foreach ($date_formats as $format) {
		if($format == $selected_format) $selected = ' selected="selected" '; else $selected=NULL;
		$buffer .= '<option value="'.$format.'"'.$selected.'>'.htmlentities(date($format,$current_time)).'</option>'."\n";
	}
	return $buffer;
}

//
// Function: ConfigMechanismSelect
//
// Description: Set the protection mechanism options.
//
function ConfigMechanismSelects($PostField,$ConfigOption){
	$options = array("htaccess", "mod_dbd");
	$buffer = '';
	if(empty($PostField)){
		$current = $ConfigOption;
	} else {
		$current = $PostField;
	}
	foreach ($options as $opt) {
		if($opt == $current)
			 $selected = ' selected="selected" '; else $selected=NULL;
		$buffer .= '<option value="' . $opt . '"' . $selected . '>' . $opt . '</option>' . "\n";
	}
	return $buffer;
}

// this function is used on the configuration page to print out the verification type selection menu
function ConfigVerificationSelects($PostField,$ConfigOptionVerifyEmail,$ConfigOptionRequireAdminAccept){
	$options = Array('None'=>'0','Email Confirmation'=>'1','Admin Approval'=>'2','Email and Admin'=>'3');
	$buffer = '';
	if(empty($PostField)){
		if($ConfigOptionVerifyEmail=='true' && $ConfigOptionRequireAdminAccept=='true'){
			$selected_validation = '3';
		} elseif($ConfigOptionVerifyEmail=='true' && $ConfigOptionRequireAdminAccept!='true') {
			$selected_validation = '1';
		} elseif($ConfigOptionVerifyEmail!='true' && $ConfigOptionRequireAdminAccept=='true'){
			$selected_validation = '2';
		} elseif($ConfigOptionVerifyEmail!='true' && $ConfigOptionRequireAdminAccept!='true'){
			$selected_validation = '0';
		}
	} else {
		$selected_validation = $PostField;
	}
	foreach ($options as $text => $value){
		if($selected_validation == $value) $selected = ' selected="selected" '; else $selected = NULL;
		$buffer .= '<option value="'.$value.'"'.$selected.'>'.$text.'</option>';
	}
	return $buffer;
}

// this function checks whther or not a radio button should be selected. this function is for the configuration page.
function ConfigRadioCheck($PostField,$ConfigOption,$Button){
	// which button are we checking, on or off?
	if($Button=='off'){
		if(!isset($PostField)){
			if($ConfigOption!='true'){
				return ' checked="checked"';
			}
		} elseif($PostField!='true') {
			return ' checked="checked"';
		}
	} else {
		if(!isset($PostField)){
			if($ConfigOption=='true'){
				return ' checked="checked"';
			}
		} elseif($PostField=='true') {
			return ' checked="checked"';
		}
	}
}

// this function gives text fields a default value. this function is for the configuration page.
function ConfigTextField($PostField,$ConfigOption){
	if(isset($PostField)){
		return $PostField;
	} else {
		return $ConfigOption;
	}
}

function ConfigAuthTypeSelects($PostField,$ConfigOption){
	if(!empty($PostField)){
		$selected = $PostField;
	} else {
		$selected = $ConfigOption;
	}
	$options = array('Basic'=>'false','Digest'=>'true');
	$buffer = '';
	foreach($options as $name => $value){
		if($value == $selected) $isselected = ' selected="selected"'; else $isselected = null;
		$buffer .= '<option value="'.$value.'"'.$isselected.'>'.$name.'</option>';
	}
	return $buffer;
}

// this function is to approve a user account
function accept_user_request($dbh,$username,$prefix){
	$sql = 'UPDATE `'.$prefix.'users` SET `status` = \'2\' WHERE `username`=\''.$username.'\'';
	if($dbh->query($sql)){
		return true;
	} else {
		return false;
	}
}

// Take config options from database and put them in an array
function get_config($dbh, $prefix){
	if($stmt = $dbh->query('SELECT * FROM '.$prefix.'config')){
		while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
			$config[$row->option_name] = $row->value;
		}
		return $config;
	} else {
		db_failure($dbh, $dbh->errorInfo());
	}
}

// Update an option on the configuration page
function ConfigUpdateOption($dbh,$Option,$OptionDisplayName,$Value,$prefix){
	$sql = 'UPDATE '.$prefix.'config SET value = \''.$Value.'\' WHERE option_name = \''.$Option.'\'';
	if($dbh->query($sql)){
		return true;
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
}

// this option updates any pending user's status to verified
function ConfigUpdateApprovalStatus($dbh,$prefix){
	$sql = 'UPDATE '.$prefix.'users SET status = \'2\' WHERE status = \'1\'';
	if($dbh->query($sql)){
		return true;
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
}

// same as above function except updates users who have not validated their email
function ConfigUpdateInactiveStatus($dbh,$prefix,$newstatus){
	$sql = 'UPDATE '.$prefix.'users SET status = \''.$newstatus.'\' WHERE status = \'0\'';
	if($dbh->query($sql)){
		return true;
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
}

// Function: validate_password
//
// Description: Check to make sure a password meets password requirements.
//               This check against length and regex based contraints.
//               Returns true if the password is good, false if it is not
//
function validate_password ($password,$minlength,$maxlength,$patt_1, $patt_2){
	/* echo 'validate_password> ' . $password . ', ' . $minlength . ', ' . $maxlength . ', "' .
		$patt_1 . '", "' . $patt_2 . '"!'; */
	if(strlen($password) >= $minlength && strlen($password) <= $maxlength){
		if(preg_match($patt_1 , $password)
			&& preg_match($patt_2, $password) ){
			return true;
		} else {
			/* echo 'validate_password> pattern check failed!'; */
			return false;
		}
	} else {
		/* echo 'validate_password> lengh check failed!'; */
		return false;
	}
}

// This will simply make sure usernames are the correct length and are alphanumeric
// Returns true if the username is valid.
function validate_username ($username,$minlength=5,$maxlength=15){
	if(strlen($username) >= $minlength && strlen($username) <= $maxlength){
		if(ctype_alnum($username)){
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// This will simply make sure names are the correct length and are alphanumeric
// Returns true if the username is valid.
function validate_name ($name,$minlength=1,$maxlength=15){
	if(strlen($name) >= $minlength && strlen($name) <= $maxlength){
		if(ctype_alnum(str_replace(array('-',' '),null,$name))){
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// Function to validate an email address. This will return true if the email address is valid.
function validate_email_address($email) {
	// First, we check that there's one @ symbol, and that the lengths are right
	if (!preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email)) {
		// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
		return false;
	}
	// Split it into sections to make life easier
	$email_array = explode("@", $email);
	$local_array = explode(".", $email_array[0]);
	for ($i = 0; $i < sizeof($local_array); $i++) {
		if (!preg_match("/^(([A-Za-z0-9!#$%&'*+\/=?^_`{|}~-][A-Za-z0-9!#$%&'*+\/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $local_array[$i])) {
			return false;
		}
	}
	if (!preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
		$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2) {
			return false; // Not enough parts to domain
		}
		for ($i = 0; $i < sizeof($domain_array); $i++) {
			if (!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i])) {
				return false;
			}
		}
	}
	return true;
}
function show_footer($version) {
	print 'Powered by <a href="http://code.google.com/p/php-reg-portal/">PHP RegPortal</a>';
}
function show_user_footer($version) {
	return 'Powered by <a href="http://code.google.com/p/php-reg-portal/">PHP RegPortal</a>';
}
function show_bottom_nav ($admin_path){
	print '<a href="'.$admin_path.'index.php">Home</a> |
	User List | 
	Requests | 
	New User | 
	Email | 
	Stats | 
	Config | 
	<a href="'.$admin_path.'login.php?cmd=logout">Logout</a>';
}

// This is a function to use when checking fields that can be set to optional.
function validate_optional_fields($string,$setting){
	if($setting=="true"){
		if(empty($string)){
			return false;
		} else {
			return true;
		}
	} else {
		return true;
	}
}

// If the string equals the value then returns true
// This is for a field that can be set to optional.
function match_string($string,$value,$option=1){
	if($option=="true"){
		if($string == $value){
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// this checks to see if the phone number is numeric,
// but also allows you to disable the check
function validate_phone($phone,$required_digits,$option){
	if($option=="true" || !empty($phone)){
		if(is_numeric($phone) && strlen($phone) >= $required_digits){
			return true;
		} else {
			return false;
		}
	} else {
		return true;
	}
}

/**
  * Chop a string into a smaller string.
  *
  * @author      Aidan Lister <aidan@php.net>
  * @version     1.1.0
  * @link        http://aidanlister.com/repos/v/function.str_chop.php
  * @param       mixed  $string   The string you want to shorten
  * @param       int    $length   The length you want to shorten the string to
  * @param       bool   $center   If true, chop in the middle of the string
  * @param       mixed  $append   String appended if it is shortened
  */
function str_chop($string, $length = 60, $center = false, $append = null)
{
	// Set the default append string
	if ($append === null) {
		$append = ($center === true) ? '..' : '..';
	}

	// Get some measurements
	$len_string = strlen($string);
	$len_append = strlen($append);

	// If the string is longer than the maximum length, we need to chop it
	if ($len_string > $length) {
		// Check if we want to chop it in half
		if ($center === true) {
			// Get the lengths of each segment
			$len_start = $length / 2;
			$len_end = $len_start - $len_append;

			// Get each segment
			$seg_start = substr($string, 0, $len_start);
			$seg_end = substr($string, $len_string - $len_end, $len_end);

			// Stick them together
			$string = $seg_start . $append . $seg_end;
		} else {
			// Otherwise, just chop the end off
			$string = substr($string, 0, $length - $len_append) . $append;
		}
	}

	return $string;
}

// Redirect function. This must be used before any data is sent to the browser.
function redirect ($location){
	header('Location: '.$location);
	exit;
}

// check to see if admin is logged in, if not, redirect them to the login page
function admin_auth_check ($session_var, $location){
	if(!isset($session_var)){
		redirect($location);
	}
}
?>
