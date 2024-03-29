<?php
//
// File: index.php
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
//         The Tux Logo has been removed and replaced with MySQL & PostgreSQL
//         logos as key aim was to make this version RDBMS agnostic via
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
* File Description: This is the main page of the admin panel.                 *
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
if($config['verify_email']=='true' && $config['prune_inactive_users']=='true'){
	PruneInactiveUsers($dbh,$pdodb['prefix']);
}

// start the session
admin_sessions($config['admin_session_expire'], "?db_config_path=" . $db_config_path);
if(!isset($_SESSION['logged_in'])){
	redirect('./login.php?db_config_path=' . $db_config_path);
}

// Get current version info from deadlock website.
$this_version = $software_version . ' ' . $software_release;
$urlfile = 'http://code.google.com/p/php-reg-portal/';
$versionurl = @file_get_contents($urlfile);

if($currentversion = @file_get_contents($versionurl)){
	$currentversion = explode(';',$currentversion);
	$latestversion = $currentversion[0] . ' ' . $currentversion[1];

	if($currentversion[0] <= $software_version){
		$upgrade = '<span class="style12">Current</span>';
		$statushint = 'Your installation of RegPortal is current.';
	} else {
		$upgrade = '<span class="style9">Out of date</span>';
		$statushint = 'Your installation of RegPortal is outdated. You should upgrade, as a newer release will usually be more secure.';
	}
} else {
	$latestversion = 'Unknown';
	$upgrade = '<span class="style9">Error</span>';
	$statushint = 'An error occurred while retrieving release information from the RegPortal website. The website could be down, or your server could be preventing RegPortal from connecting to external websites.';
}


$currentmembers = count_users($dbh,$pdodb['prefix']);

if($config['require_admin_accept']=="true"){
	$pendingmembers = '<a href="./userrequests.php?db_config_path=<?php echo $db_config_path; ?>">'.count_pending_users($dbh,$pdodb['prefix']).'</a>';
	$pendinghintbox = 'This is the number of requests that are waiting for your approval.';
} else {
	$pendingmembers = 'Disabled';
	$pendinghintbox = 'This feature is disabled in the RegPortal configuration.';
}

if($config['verify_email']=="true"){
	$inactivemembers = '<a href="./inactiveusers.php?db_config_path=<?php echo $db_config_path; ?>">'.count_inactive_users($dbh,$pdodb['prefix']).'</a>';
	$inactivehintbox = 'This is the number of users that have not validated their email.';
} else {
	$inactivemembers = 'Disabled';
	$inactivehintbox = 'This feature is disabled in the RegPortal configuration.';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal - Admin Panel</title>
<script type="text/javascript" src="../images/hint.js">
/***********************************************
* Show Hint script- � Dynamic Drive (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit http://www.dynamicdrive.com/ for this script and 100s more.
***********************************************/
</script>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
</head>

<body>
<table width="549" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="412" height="58"><a href="index.php?db_config_path=<?php echo $db_config_path; ?>"><img src="../images/header_logo.gif" width="183" height="58" border="0" /></a></td>
    <td width="137"><div align="right"><img src="../images/rdbms.gif" width="119" height="56" /></div></td>
  </tr>
  <tr>
    <td height="2" colspan="2"><img src="../images/grey_pixel.gif" width="100%" height="2" /></td>
  </tr>
  
  <tr>
    <td height="34" colspan="2" class="style2">Welcome to the RegPortal administration panel. Here you can manage users, accept new requests, change settings and more. To begin, select a link to the right. </td>
  </tr>
  <tr>
    <td height="171" valign="top"><table width="85%" border="0">
      <tr>
        <td colspan="2"><span class="style5">Protected  Area Infomation</span></td>
      </tr>
      <tr>
        <td width="40%" class="style2">Active Members:</td>
        <td width="60%" class="style2"><a href="./userlist.php?db_config_path=<?php echo $db_config_path; ?>"><?php echo $currentmembers; ?></a>
          <a href="#" class="hintanchor" onmouseover="showhint('This is the number of registered and approved members currently in the database.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style2">Pending Requests:</td>
        <td class="style2"><?php echo $pendingmembers; ?>
          <a href="#" class="hintanchor" onmouseover="showhint('<?php echo $pendinghintbox; ?>', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style2">Inactive Users:</td>
        <td class="style2"><?php echo $inactivemembers; ?>
          <a href="#" class="hintanchor" onmouseover="showhint('<?php echo $inactivehintbox; ?>', this, event, '150px')">[?]</a></td>
      </tr>
    </table>
      <br />
      <table width="85%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td colspan="2" class="style5">RegPortal Information</td>
        </tr>
        <tr>
          <td width="40%" class="style2">Version:</td>
          <td width="60%"><span class="style11"><?php echo $this_version; ?></span> <a href="#" class="hintanchor" onmouseover="showhint('This is the version of RegPortal in which is installed on your server.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style2">Current Version:</td>
          <td><span class="style11"><?php echo $latestversion; ?></span> <a href="#" class="hintanchor" onmouseover="showhint('This is the current version of RegPortal available for download.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td height="20" class="style2">Status:</td>
          <td><?php echo $upgrade; ?> <a href="#" class="hintanchor" onmouseover="showhint('<?php echo $statushint; ?>', this, event, '150px')">[?]</a></td>
        </tr>
    </table></td>
    <td height="171" valign="top"><span class="style2"><span class="style5">Navigation Menu</span><br />
<a href="./index.php?db_config_path=<?php echo $db_config_path; ?>">Home</a><br />
<a href="./userlist.php?db_config_path=<?php echo $db_config_path; ?>">Manage Users</a><br />
<?php if($config['require_admin_accept']=='true'): ?><a href="./userrequests.php?db_config_path=<?php echo $db_config_path; ?>">User Requests</a><br /><?php endif; ?>
<?php if($config['verify_email']=='true'): ?><a href="./inactiveusers.php?db_config_path=<?php echo $db_config_path; ?>">Inactive Users</a><br /><?php endif; ?>
<a href="./bulkemail.php?db_config_path=<?php echo $db_config_path; ?>">Bulk Email</a><br />
<br />
<a href="./editconfig.php?db_config_path=<?php echo $db_config_path; ?>">Configuration</a><br />
<a href="<?php echo htmlentities($config['protected_area_url']); ?>">My Protected Area</a><br />
<a href="./login.php?db_config_path=<?php echo $db_config_path; ?>&cmd=logout">Logout</a></span></td>
  </tr>
  
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center"><?php show_footer($software_signature); ?></div></td>
  </tr>
</table>
</body>
</html>
