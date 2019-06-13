<?php
//
// File: login.php
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

// include needed files
$db_config_path = $_GET['db_config_path'];
require($db_config_path);
require('../pdo/db-funcs.php');
require('../global.php');

$dbh = connect_db($pdodb, $err);
if (! isset($dbh)) {
        die("RegPortal was unable to connect to RDBMS: " . $err);
}

// assign config options from database to an array
$config = get_config($dbh, $pdodb['prefix']);

debug_mode($config['debug_mode']);

// require the template engine class (MiniTemplator)
require('../lib/MiniTemplator.class.php');
$template = new MiniTemplator;
$templatedir = '../templates/';

// set the session name
session_name('user_sid');
// start the session
session_start();

// logout user
if(isset($_GET['action']) && $_GET['action'] == 'logout')
{
	session_destroy();
	redirect($_SERVER['PHP_SELF'] . "?db_config_path=" . $db_config_path);
}

// if the user is logged in, send them to the logged in page
if(isset($_SESSION['user_logged_in']))
{
	redirect('./account.php?db_config_path' . $db_config_path);
}

// if the login form has been submitted, let's see if the info submitted is valid
if(isset($_POST['username']) && isset($_POST['password'])){
	if(check_login_info($dbh,$_POST['username'],$_POST['password'],$pdodb['prefix']))
	{
		if(GetCurrentStatus($_POST['username'],$pdodb['prefix']) == '2')
		{
			$_SESSION['user_logged_in'] = 1;
			$_SESSION['username'] = $_POST['username'];
			$_SESSION['StartTimestamp'] = time();
			$_SESSION['UserIP'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['UserAgent'] = $_SERVER['HTTP_USER_AGENT'];
			redirect('./account.php?db_config_path=' . $db_config_path);
		}
		else
		{
			$error = 'That account is currently inactive. It may be waiting for email validation or the administrator\'s approval.';
		}
	}
	else
	{
		$error = 'The username/password combination you entered was invalid.';
	}
}

// generate html login page using minitemplator
$template->readFileIntoString($templatedir."overall_header.html",$header);
$template->readFileIntoString($templatedir."user_panel_login.html",$main);
$template->readFileIntoString($templatedir."overall_footer.html",$footer);

$template->setTemplateString($header . $main . $footer);

// assign error variables
if(isset($error))
{
	$template->setVariable("error",$error);
	$template->addBlock("error");
}

if($config['verify_email']=='true')
{
	$template->addBlock("activation_enabled");
}

// set the php self variable which is used to submit the form.
$template->setVariable("phpself",$_SERVER['PHP_SELF']);

// set the url to the protected area
$template->setVariable("protected_url",$config['protected_area_url']);

$template->setVariable("footer",show_user_footer($software_signature));
$template->setVariable("pagename","User Login");
$template->generateOutput();
?>
