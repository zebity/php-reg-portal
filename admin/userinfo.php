<?php
//
// File: userinfo.php
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
	PruneInactiveUsers($dbh,$pdodb['prefix']);
}

// start the session
admin_sessions($config['admin_session_expire'], "?db_config_path=" . $db_config_path);
if(!isset($_SESSION['logged_in'])){
	redirect('./login.php?db_config_path=' . $db_config_path);
}

if(check_user_exists($dbh,$_GET['user'],$pdodb['prefix'])):

$result = $dbh->query('SELECT * FROM '.$pdodb['prefix'] . 'users WHERE username=' . $dbh->quote($_GET['user']));
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
	$name = $row['firstname'].' '.$row['lastname'];
	$country = $row['country'];
	$phone = $row['phone'];
	$username = $row['username'];
	$email = $row['email'];
	$status = $row['status'];

	if ($pdodb['provider'] == 'mysql')
		 $RegistrationDate = date($config['date_format'],$row['registered_dtstamp']);
        elseif ($pdodb['provider'] == 'pgsql')
		 $RegistrationDate = $row['registered_dtstamp'];
}
if($country=='Not Selected'){
	$country = '<i>Not Available</i>';
}
if(empty($phone)){
	$phone = '<i>Not Available</i>';
}

switch($status){
	case '2':
	$statustext = '<font color="green">Active</font>';
	break;
	case '1':
	$statustext = '<font color="red">Inactive</font> - <i>Needs admin approval</i>';
	break;
	case '0':
	$statustext = '<font color="red">Inactive</font> - <i>Needs email verification</i>';
	break;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal - User Information</title>
<script type="text/javascript" src="../images/hint.js">
/***********************************************
* Show Hint script- © Dynamic Drive (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit http://www.dynamicdrive.com/ for this script and 100s more.
***********************************************/
</script>
<script type="text/javascript">
function deleteuser(username){
	var answer = confirm('Are you sure you want to delete the user "'+username+'"?');
	if(answer==true){
		window.location="./userdel.php?db_config_path=<?php echo $db_config_path; ?>&user="+username;
	}
}
function denyuser(username){
	var answer = confirm('Are you sure you want to deny the user "'+username+'"? This will completely remove them from the database.');
	if(answer==true){
		window.location="./useraccept.php?db_config_path=<?php echo $db_config_path ; ?>&action=deny&user="+username;
	}
}
function acceptuser(username){
	var answer = confirm('Are you sure you want to accept the user "'+username+'"? This will update their status to approved and will give them access to the protected area.');
	if(answer==true){
		window.location="./useraccept.php?db_config_path=<?php echo $db_config_path; ?>&action=accept&user="+username;
	}
}
</script>
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
    <td height="20" colspan="2" class="style2"><strong><a href="./index.php?db_config_path=<?php echo $db_config_path; ?>">Top</a>: User Information </strong></td>
  </tr>
  <tr>
    <td height="22" colspan="2" class="style2">Information about a specific user can be found below.</td>
  </tr>
  <tr>
    <td height="19" colspan="2"><br />
      <table width="70%" border="0">
      <tr>
        <td width="31%" class="style5">Full Name:</td>
        <td width="69%" class="style2"><?php echo $name; ?></td>
      </tr>
      <tr>
        <td class="style5">Username:</td>
        <td class="style2"><?php echo $username; ?></td>
      </tr>
      <tr>
        <td class="style5">Email Address: </td>
        <td class="style2"><?php if($status=='2'): ?><a href="./bulkemail.php?db_config_path=<?php echo $db_config_path; ?>&user=<?php echo $username; ?>"><?php echo $email; ?></a><?php else: print $email; endif; ?></td>
      </tr>
      <tr>
        <td class="style5">Country:</td>
        <td class="style2"><?php echo $country; ?></td>
      </tr>
      <tr>
        <td class="style5">Phone:</td>
        <td class="style2"><?php echo FormatPhoneNumber($phone); ?></td>
      </tr>
      <tr>
        <td class="style5">Date Registered:</td>
        <td class="style2"><?php echo $RegistrationDate; ?></td>
      </tr>
      <tr>
        <td class="style5">Status:</td>
        <td class="style2"><?php echo $statustext; ?></td>
      </tr>
    </table>
      <br />
      <?php if($status=='1'): ?>
      <input name="Button" type="button" value="Accept" onclick="acceptuser('<?php echo $username; ?>')" />
      <input name="Button" type="button" value="Decline" onclick="denyuser('<?php echo $username; ?>')" />
      <?php else: ?>
      <input name="Button" type="button" value="Delete" onclick="deleteuser('<?php echo $username; ?>')" />
      <input type="submit" value="Edit" onclick="window.location='./edituser.php?db_config_path=<?php echo $db_config_path; ?>&user=<?php echo $username; ?>'" />
      <?php endif; ?>
      <br />

  <span class="style2"><br />
      <br /><a href="./userlist.php?db_config_path=<?php echo $db_config_path; ?>">&lt;&lt; Back to user list</a><br/><br/><br/></span></td>
  </tr>
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center"><?php show_footer($software_signature); ?></div></td>
  </tr>
</table>
</body>
</html>
<?php else: ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal - User Information</title>
<script type="text/javascript" src="../images/hint.js">
/***********************************************
* Show Hint script- © Dynamic Drive (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit http://www.dynamicdrive.com/ for this script and 100s more.
***********************************************/
</script>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
<table width="549" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="329" height="58"><a href="./index.php"><img src="../images/header_logo.gif" width="183" height="58" border="0" /></a></td>
    <td width="220"><div align="right"><img src="../images/rdbms.gif" width="119" height="56" /></div></td>
  </tr>
  <tr>
    <td height="2" colspan="2"><img src="../images/grey_pixel.gif" width="100%" height="2" /></td>
  </tr>
  <tr>
    <td height="20" colspan="2" class="style2"><strong><a href="./index.php?db_config_path=<?php echo $db_config_path; ?>">Top</a>: User Information </strong></td>
  </tr>
  <tr>
    <td height="22" colspan="2" class="style2">Information about a specific user is below. This page also shows the user's last 10 logins. </td>
  </tr>
  <tr>
    <td height="19" colspan="2"><br />
      <span class="style2">Sorry, but the specified user was not found in the database. <br />
      <br /><a href="./userlist.php?db_config_path=<?php echo $db_config_path; ?>">&lt;&lt; Back to user list</a><br/><br/><br/>
      </span></td>
  </tr>
  
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center"><?php show_footer($software_signature); ?></div></td>
  </tr>
</table>
</body>
</html>
<?php endif; ?>
