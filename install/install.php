<?php
//
// File: install.php
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
//         PHP's PDO functions.
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

if (! isset($_SERVER['PHP_AUTH_USER'])) {
	die('RegPortal is not configured securely, please secure the install directory!');
}

if (empty($_GET) || (! isset($_GET['db_config_path']))) {
	die("RegPortal appears to be have invoked without specifing the db_config.php location.");
}

$db_config_path = $_GET['db_config_path'];

require($db_config_path);
include('../pdo/db-funcs.php');

// functions
function generate_htpasswd ($htpasswd,$htaccess){
	global $_POST;

	$htaccess_path = $_SERVER['DOCUMENT_ROOT'].$htaccess;
	
	if(isset($_POST['htpasswd_relative'])){
		$htpasswd_path = $_SERVER['DOCUMENT_ROOT'].$htpasswd;
	} else {
		$htpasswd_path = $htpasswd;
	}

	$handle = fopen($htpasswd_path,'w') or die('RegPortal could not open the htpasswd file for writing. '.$htpasswd_path);
	fwrite($handle,' ') or die('RegPortal could not write the htpasswd file. '.$htpasswd_path);
	fclose($handle);
}

// generate the htaccess file
function generate_htaccess($htpasswd,$htaccess,$realm,$auth_type,$protected_url)
{
	global $_POST;

	$htaccess_path = $_SERVER['DOCUMENT_ROOT'].$htaccess;
	
	if(isset($_POST['htpasswd_relative'])){
		$htpasswd_path = $_SERVER['DOCUMENT_ROOT'].$htpasswd;
	} else {
		$htpasswd_path = $htpasswd;
	}

	if ($auth_type == 'basic') {
		$auth_type = 'Basic';
	}
	else {
		$auth_type = 'Digest';
	}

	$buffer = "AuthName \"" . $realm . "\"\nAuthType " . $auth_type . "\nAuthUserFile " .
		$htpasswd_path . "\nrequire valid-user";
	$handle = fopen($htaccess_path,'w') or die('RegPortal could not open the htaccess file for writing. '.$htaccess_path);
	fwrite($handle,$buffer) or die('RegPortal could not write the htaccess file. '.$htaccess_path);
	fclose($handle);
}

function generate_conf($docroot,$conf_path,$htaccess_path,$realm,$auth_type,$db_prefix,$protected_url)
{
	global $_POST;

	$protected_directory = $docroot . $htaccess_path;
	$conf_file = $conf_path . "/" . preg_replace("/\//", "-", substr($htaccess_path, 1)) . ".conf"; 

	$buffer = "<Directory " . $protected_directory . ">\nAuthName \"" . $realm . "\"\nAuthType ";
	if ($auth_type == 'digest') {
		$buffer_d = "Digest\nAuthDigestNcCheck On\nAuthDigestDomain " . $protected_url .
			"\nAuthDigestProvider dbd\nAuthDBDUserRealmQuery \"SELECT digest_password FROM " .
			$db_prefix . "users WHERE username = %s AND realm = %s\"\nrequire valid-user\n</Directory>";
		$buffer .= $buffer_d;
	}
	else {
		$buffer_b = "Basic\nAuthBasicProvider dbd\n" .
			"AuthDBDUserPWQuery \"SELECT basic_password FROM " . $db_prefix .
			"user WHERE username = %s\"\nrequire valid-user\n</Directory>";
		$buffer .= $buffer_b;
	}

	$handle = fopen($conf_file,'w') or die('RegPortal could not open the conf file for writing: ' . $conf_file);
	fwrite($handle,$buffer) or die('RegPortal could not write the conf file: ' . $conf_file);
	fclose($handle);
}

function validate_htparams(&$htpasswddir, &$passwdfile, &$htaccessdir, &$htaccessfile, &$htpasswd_relative)
{
	global $_POST;

	$res = null;
	if(empty($_POST['htpasswd_path'])){
		$res[] = 'Please enter the path to your .htpasswd file.';
	} else {
		if(isset($_POST['htpasswd_relative'])){
			$htpasswddir = $_SERVER['DOCUMENT_ROOT'].dirname($_POST['htpasswd_path']);
			$htpasswdfile = $_SERVER['DOCUMENT_ROOT'].$_POST['htpasswd_path'];
		} else {
			$htpasswddir = dirname($_POST['htpasswd_path']);
			$htpasswdfile = $_POST['htpasswd_path'];
		}
		// if the .htpasswd already exists, make sure it is writable before continuing
		if(file_exists($htpasswdfile) && !is_writable($htpasswdfile)){
			$res[] = 'The .htpasswd file specified is not writable by RegPortal. If you created this file manually, please delete it before continuing.';
		}

		if(!file_exists($htpasswddir)){
			$res[] = 'The directory that you specified in your .htpasswd path does not exist. The full path to the directory you specified is '.$htpasswddir.'.';
		} else {
			if(!is_writable($htpasswddir)){
				$res[] = 'The directory that you specified in your .htpasswd path is not writable. If you are using Unix, CHMOD '.$htpasswddir.' to 777.';
			}
		}
	}
	// validate .htaccess path field
	if(empty($_POST['htaccess_path'])){
		$res[] = 'Please enter the path to your .htaccess file.';
	} else {
		$htaccessdir = $_SERVER['DOCUMENT_ROOT'].dirname($_POST['htaccess_path']);
		$htaccessfile = $_SERVER['DOCUMENT_ROOT'].$_POST['htaccess_path'];
		// if the .htaccess already exists, make sure it is writable before continuing
		if(file_exists($htaccessfile) && !is_writable($htaccessfile)){
			$res[] = 'The .htaccess file specified is not writable by RegPortal. If you created this file manually, please delete it before continuing.';
		}

		if(!file_exists($htaccessdir)){
			$res[] = 'The directory that you specified in your .htaccess path does not exist. The full path to the directory you specified is '.$htaccessdir.'.';
		} else {
			if(!is_writable($htaccessdir)){
				$res[] = 'The directory that you specified in your .htaccess path is not writable. If you are using Unix, CHMOD '.$htaccessdir.' to 777.';
			}
		}
	}

	if(isset($_POST['htpasswd_relative'])){
		$htpasswd_relative = 'true';
	} else {
		$htpasswd_relative = 'false';
	}

	return($res);
}

function validate_confdir(&$conf_path, &$htaccesdir)
{
	global $_POST;

	$res = null;
	if(empty($_POST['conf_path'])){
		$res[] = 'Please enter the Apache configuration directory.';
	} else {

		$conf_path = $_POST['conf_path'];

		// Check that directory exists and is writable 
		if(!file_exists($conf_path)){
			$res[] = 'The Apache directory does not exist: ' . $conf_path;
		} else {
			if(!is_writable($conf_path)){
				$res[] = 'The Apache directory is not writable. If you are using Unix, CHMOD ' . $conf_path . ' to 777.';
			}
		}
	}

	// validate .htaccess path field
	if(empty($_POST['htaccess_path'])){
		$res[] = 'Please enter the path to the directory you are securing.';
	} else {
		
		$htaccessdir = $_POST['docroot'] . $_POST['htaccess_path'];
		
		if(!file_exists($htaccessdir)){
			$res[] = 'The Protected Directory does not exist:  ' . $htaccessdir;
		}

	}

	return($res);
}

if(isset($_GET['step'])){
	switch($_GET['step']){
		case '2': $currentstep='2'; break;
		case '3a': $currentstep='3a'; break;
		case '3b': $currentstep='3b'; break;
		default: $currentstep='1';
	}
} else {
	$currentstep = '1';
}

if($currentstep=='1'):

if(defined("DEADLOCK_INSTALLED")){
	die('RegPortal is already installed.');
}

if(!is_writable($db_config_path)){
	if(!chmod($db_config_path,0777)){
		$dbconfigpermissions = 'RegPortal has detected that ' . $db_config_path .
		' is not currently writable. If you are using Unix please CHMOD ' . $db_config_path .
		' to 777. Refresh the page once you have fixed this problem.';

	}
} else {
	$dbconfigpermissions = 'RegPortal has detected that ' . $db_config_path .
		 ' is currently writable. You may proceed.<br /><br />Click continue to proceed to step 2.
    <input type="button" onclick="window.location=\'./install.php?step=2&db_config_path=' . $db_config_path . '\'" value="Continue&gt;&gt;" />';
}

include('step1-form.php');

elseif($currentstep=='2'):

if(!is_writable($db_config_path)) die('RegPortal found that ' . $db_config_path . ' is not writable. Did you skip step 1?');

if(isset($_POST['submit'])){
	if(empty($_POST['db_name'])){
		$errors[] = 'You must enter a database name.';
	}

	if(!isset($errors)){
		$text = '<?php
//
// File: db_config.php
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
//         turn provide under GNU General Public Licence V3 as per
//         Deadlock code.
//         The Tux Logo has been removed and replaceid with MySQL & PostgreSQL
//         logo-s as key aim was to make this version RDBMS agnostic via
//         PHP PDO functions.
//         The new RegPortal logo includes original Deadlock logo (in miniture)
//         in recognition of the fact that this is derived work.
//         The Apache logo is includeid in the new logo, as this program
//         generates Apache specific outputs... and assumes Apache is the
//         underlying Web Server.
//
/******************************************************************************
* This file is part of the Deadlock PHP User Management System.               *
*                                                                             *
* File Description: pdodb config file                                         *
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

// PDO Configuration. Do NOT modify this file once the database has been
// created unless you know what you are doing.

// htaccess or mod_dbd/mod_authn_dbd
//
$pdodb["mechanism"] = "'.$_POST['mechanism'].'";

// RDBMS Provider
$pdodb["provider"] = "'.$_POST['db_provider'].'";

// RDBMS host
$pdodb["host"] = "'.$_POST['db_host'].'";

// RDBMS port
$pdodb["port"] = "'.$_POST['db_port'].'";

// RDBMS database name
$pdodb["database"] = "'.$_POST['db_name'].'";

// RDBMS table prefix
$pdodb["prefix"] = "'.$_POST['db_prefix'].'";

// Your RDBMS username for the above database
$pdodb["username"] = "'.$_POST['db_username'].'";

// RDBMS password for the above username
$pdodb["password"] = "'.$_POST['db_password'].'";


define("DEADLOCK_INSTALLED",true);

?>';
		$handle=fopen($db_config_path,'w');
		fwrite($handle,$text) or die('RegPortal was unable to write the RDBMS configuration file. Make sure the file "' .
			$db_config_path . '" exists and is writable.');
		fclose($handle);

		if ($_POST['mechanism'] == 'htaccess') {
			header('Location: '.$_SERVER['PHP_SELF'].'?step=3a&db_config_path=' . $db_config_path);
		}
		elseif($_POST['mechanism'] == 'mod_dbd') {
			header('Location: '.$_SERVER['PHP_SELF'].'?step=3b&db_config_path=' . $db_config_path);
		}
		else {
			die("RegPortal does not have valid protection mechanism: " . $pdodb['mechanism'] );
		}
	}
}

include('step2-form.php');

elseif (($currentstep == '3a') || ($currentstep == '3b')):

if(!file_exists($db_config_path)) die('Config file: ' . $db_config_path . ' does not yet exist. Did you skip step 2?');
if(!defined('DEADLOCK_INSTALLED')){
	die('Config file: ' . $db_config_path . ' does not have the needed data in it. It must not have been written correctly.');
}

if(isset($_POST['submit'])){

	if ($pdodb['mechanism'] == 'htaccess') {
		$errors = validate_htparams($htpasswddir, $htpasswdfile, $htaccessdir, $htaccessfile, $htpasswd_relative);
	}
	elseif ($pdodb['mechanism'] == 'mod_dbd') {
		$errors = validate_confdir($confdir, $htaccessdir);
	}

	// validate protected url field
	if(empty($_POST['protected_area_url'])){
		$errors[] = 'Please specify the URL to your protected area.';
	}
	// validate regportal url field
	if(empty($_POST['regportal_url'])){
		$errors[] = 'Please specify the URL to RegPortal.';
	}
	// validate admin email
	if(empty($_POST['admin_email'])){
		$errors[] = 'Please specify your email address.';
	}

	// create tables if there are no errors
	if(! isset($errors)){
		// connect to RDBMS using PDO
		$dbh = connect_db($pdodb, $err);
		if (empty($dbh)) {
			die($err);
		}

		// pdodb_connect($pdodb['host'],$pdodb['username'],$pdodb['password']) or die('The script could not connect to MySQL. Install Failed.');
		// pdodb_select_db($pdodb['database']) or die('Could not select MySQL datbase. Install failed.');

		if ($pdodb['mechanism'] == 'htaccess') {
			generate_htpasswd($_POST['htpasswd_path'],$_POST['htaccess_path']);
			generate_htaccess($_POST['htpasswd_path'],$_POST['htaccess_path'],$_POST['realm'],
				$_POST['auth_type'],$_POST['protected_area_url']);
		}
		elseif ($pdodb['mechanism'] == 'mod_dbd') {
			generate_conf($_POST['docroot'],$_POST['conf_path'],$_POST['htaccess_path'],$_POST['realm'],
				$_POST['auth_type'],$pdodb['prefix'],$_POST['protected_area_url']);
		}

		$sql = array();
		if ($pdodb['provider'] == 'mysql') {
			//
			// use original MySQL sql statements
			//
			include('mysql-table-defs.php'); 
		}
		elseif ($pdodb['provider'] == 'pgsql') {
			//
			// use PostgresSQL variation
			//
			include('pgsql-table-defs.php');
		}
		else {
			die("No valid SQL for provider: {$pdodb['provider']}."); 
		}

		// execute the query to insert data
		foreach($sql as $query){
			// var_dump($query);
			$result = $dbh->query($query);
			if (! $result) {
				die('The following RDBMS query failed. The installation failed.<br /><br />RDBMS said:'.$dbh->errorInfo().'<br /><br />'.nl2br(htmlentities($query)));
			}
		}
		if ($currentstep == '3a') {
			include('step3a-done.php');
		}
		elseif ($currentstep == '3b') {
			include('step3b-done.php');
		}
		exit;
	}
}

if ($currentstep == '3a') {
	include('step3a-form.php');
}
elseif ($currentstep == '3b') {
	include('step3b-form.php');
}
endif;
?>
