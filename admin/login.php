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
$config = get_config($dbh,$pdodb['prefix']);

debug_mode($config['debug_mode']);

// remove users that have not verified their email after 72 hours if email verification is enabled
if($config['verify_email']=='true' && $config['prune_inactive_users']=='true'){
	PruneInactiveUsers($dbh,$pdodb['prefix'],72);
}

// set the session name so that there is no conflict
session_name('admin_sid');

// start the session
session_start();

// if the query string says to logout, remove the session
if(isset($_GET['cmd']) && $_GET['cmd'] == 'logout' && isset($_SESSION['logged_in'])){
	session_destroy();
	redirect($_SERVER['PHP_SELF'] . "?db_config_path=" . $db_config_path);
}

// if the admin is already logged in, redirect to index.php
if(isset($_SESSION['logged_in'])){
	redirect('./index.php?db_config_path=' . $db_config_path);
}



if(isset($_POST['submit'])){
	$numfailed = CheckFailedLogins($dbh,$pdodb['prefix'],$_SERVER['REMOTE_ADDR']);
	if($numfailed >= 5){
		$message = 'You have reached the maximum number of failed login attempts (5). Please wait 10 minutes and try again.';
	} else {
		if($_POST['password'] == $config['admin_pass']){
			$_SESSION['logged_in'] = 1;
			redirect('index.php?db_config_path=' . $db_config_path);
		} else {
			LogFailedLogin($dbh, $pdodb['prefix'],'admin');
			$numfailed = CheckFailedLogins($dbh,$pdodb['prefix'],$_SERVER['REMOTE_ADDR']);
			$numleft = 5 - $numfailed;
			$message = 'The password you entered was incorrect. All failed logins are logged. You have '.$numleft.' login attempts left.';
		}
	}
} else {
	$message = 'Welcome to the administration panel. Please enter your password below, then click &quot;Login&quot; to login to access this area. Please note that all failed attemps are logged in the database. After 5 failed logins, you will not be able to login to the panel for 10 minutes.';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal - Admin Login</title>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
<table width="549" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="329" height="58"><a href="./index.php?db_config_path=<?php echo $db_config_path; ?>"><img src="../images/header_logo.gif" width="183" height="58" border="0" /></a></td>
    <td width="220"><div align="right"><img src="../images/rdbms.gif" width="119" height="56" /></div></td>
  </tr>
  <tr>
    <td height="2" colspan="2"><img src="../images/grey_pixel.gif" width="100%" height="2" /></td>
  </tr>
  <tr>
<td height="20" colspan="2" class="style2"><strong><a href="./index.php?db_config_path=<?php echo $db_config_path; ?>">Top</a>: Admin Panel Login </strong></td>
  </tr>
  <tr>
    <td height="22" colspan="2" class="style2"><?php echo $message; ?></td>
  </tr>
  <tr>
    <td height="19" colspan="2"><br /><br/><div align="center">
      <form id="form1" name="form1" method="post" <?php print('action="' . $_SERVER['PHP_SELF'] . "?" .$_SERVER['QUERY_STRING']) ?>">
        <span class="style2">Password:</span>
        <input name="password" type="password" id="password" />
        <input type="submit" value="Login" />
        <input name="submit" type="hidden" id="submit" value="1" />
      </form>
    </div><br/><br/><br/></td>
  </tr>
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center"><?php show_footer($software_signature); ?></div></td>
  </tr>
</table>
</body>
</html>
