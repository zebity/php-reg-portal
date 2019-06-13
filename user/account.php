<?php
//
// File: account.php
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

// remove users that have not verified their email after 72 hours if email verification is enabled
//if($config['verify_email']=='true' && $config['prune_inactive_users']=='true'){
//	PruneInactiveUsers($pdodb['prefix']);
//}

// make sure user is logged in
require('auth.inc.php');

// require the template engine class (MiniTemplator)
require('../lib/MiniTemplator.class.php');
$template = new MiniTemplator;
$templatedir = '../templates/';

if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['verify']))
{
	remove_user($dbh,$_SESSION['username'],$pdodb['prefix']);
	if ($pdodb['mechanism'] == 'htaccess')
		generate_htpasswd($pdodb['prefix']);
	session_destroy();
	redirect('./login.php?db_config_path=' . $db_config_path);
}

$sql= 'SELECT * FROM '.$pdodb['prefix'].'users WHERE username="'.$_SESSION['username'].'"';

if(!$result = $dbh->query($sql))
{
	db_failure($dbh, $dbh->errorInfo(), $sql);
}

// assign the user info to variables
while ($row = $result->fetch(PDO::FETCH_ASSOC))
{
	$firstname = $row['firstname'];
	$lastname = $row['lastname'];
	$country = $row['country'];
	$phone = $row['phone'];
	$username = $row['username'];
	$email = $row['email'];
	$status = $row['status'];
	if ($pdodb['provider'] == 'pgsql')
		$RegistrationDate = $row['registered_dtstamp'];
	else
		$RegistrationDate = date($config['date_format'],$row['registered_dtstamp']);
}

if($country=='Not Selected')
{
	$country = '<i>Not Available</i>';
}

if(empty($phone))
{
	$phone = '<i>Not Available</i>';
}


$template->readFileIntoString($templatedir."overall_header.html",$header);
$template->readFileIntoString($templatedir."account_information.html",$main);
$template->readFileIntoString($templatedir."overall_footer.html",$footer);

$template->setTemplateString($header . $main . $footer);

// set the php self variable which is used to submit the form.
$template->setVariable("phpself",$_SERVER['PHP_SELF']);

// set the first name
$template->setVariable("firstname",$firstname);
// set the last name
$template->setVariable("lastname",$lastname);
// set the username
$template->setVariable("username",$username);
// set the email
$template->setVariable("email",$email);
// set the country
$template->setVariable("country",$country);
// set the phone
$template->setVariable("phone",$phone);
// set the registration date
$template->setVariable("registered",$RegistrationDate);

$javascript = <<<EOT
function deleteaccount(){
	var answer = confirm('Are you sure you want to delete your account? This action is irreversible!');
	if(answer==true){
		window.location="account.php?action=delete&verify=1";
	}
}
EOT;

// add javascript to the header
$template->setVariable("code",$javascript);
$template->addBlock("code");
$template->addBlock("javascript");

$template->setVariable("footer",show_user_footer($software_signature));
$template->setVariable("pagename","My Account");
$template->generateOutput();
?>
