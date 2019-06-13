<?php
//
// File: inactiveusers.php
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
require("../lib/Pager.class.php");

$dbh = connect_db($pdodb, $err);
if (! isset($dbh)) {
        die("RegPortal was unable to connect to RDBMS: " . $err);
}

// assign config options from database to an array
$config = get_config($dbh, $pdodb['prefix']);

debug_mode($config['debug_mode']);

// remove users that have not verified their email after 72 hours if email verification is enabled
if($config['verify_email']=='true' && $config['prune_inactive_users']=='true'){
	PruneInactiveUsers($dbh, $pdodb['prefix']);
}

// start the session
admin_sessions($config['admin_session_expire'], "?db_config_path=" . $db_config_path);
if (!isset($_SESSION['logged_in']))
{
	redirect('./login.php?db_config_path=' . $db_config_path);
}

// start class
$p = new Pager;

// results per page
$limit = 30;

// Find the start depending on $_GET['page'] (declared if it's null)
$start = $p->findStart($limit);

if (!empty($_GET['search']))
{
	$sql = 'SELECT * FROM '.$pdodb['prefix'].'users WHERE CONCAT( firstname,lastname, username ) LIKE \'%'.mysql_escape_string($_GET['search']).'%\' and status=0';
	$sql2 = $sql.' LIMIT '. ($limit - $start) . " OFFSET " . $start;
	// $sql2 = $sql.' LIMIT '.$start.', '.$limit;
}

else
{
	// list all users
	$sql = 'SELECT * FROM '.$pdodb['prefix'].'users WHERE status=0 ORDER BY lastname';
	$sql2 = $sql.' LIMIT '. ($limit - $start) . " OFFSET " . $start;
	// $sql2 = $sql.' LIMIT '.$start.', '.$limit;
}

if ($result = $dbh->query($sql2))
{
	if ($result->rowCount() > 0)
	{
		$userlist = '';
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$userlist .= '<tr class="style2"><td>'.$row['lastname'].', '.$row['firstname'].'</td><td>'.$row['username'].'</td><td>'.str_chop($row['email'],30).'</td><td><a href="./userinfo.php?db_config_path=<?php echo $db_config_path; ?>&user='.$row['username'].'"><a href="#" onclick="deleteuser(\''.$row['username'].'\')"><img src="../images/delete15px.gif" alt="Remove" border="0" title="Remove" /></a> <a href="#" onclick="validateuser(\''.$row['username'].'\')"><img src="../images/accept15px.gif" alt="Validate" border="0" title="Validate" /></a></tr>'."\n";
		}
	}
	else
	{
		if (empty($_GET['search']))
		{
			$userlist = '<tr><td colspan="4"><span class="style11">There are currently no inactive users.</span></td></tr>';
		}
		else
		{
			$userlist = '<tr><td colspan="4"><span class="style11">Your search returned 0 results.</span></td></tr>';
		}
	}
}

else
{
	db_failure($dbh, $dbh->errorInfo(), $sql2);
}

$res = $dbh->query($sql);
$count = $res->rowCount();
$pages = $p->findPages($count, $limit);
// get the page list
$pagelist = $p->pageList($_GET['page'], $pages);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal - Inactive Users</title>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="../images/hint.js">
/***********************************************
* Show Hint script- © Dynamic Drive (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit http://www.dynamicdrive.com/ for this script and 100s more.
***********************************************/
</script>
<script type="text/javascript">
function deleteuser(username){
	var answer = confirm('Are you sure you want to delete the inactive user "'+username+'"?');
	if(answer==true){
		window.location="./userdel.php?db_config_path=<?php echo $db_config_path ; ?>&r=inactive&user="+username;
	}
}
function validateuser(username){
	var answer = confirm('Are you sure you want to validate the user "'+username+'"? This will make it as though they verified their email address.');
	if(answer==true){
		window.location="./validateuser.php?db_config_path=<?php echo $db_config_path ; ?>&user="+username;
	}
}
</script>
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
    <td height="20" colspan="2" class="style2"><strong><a href="./index.php?db_config_path=<?php echo $db_config_path ; ?>">Top</a>: Inactive Users</strong></td>
  </tr>
  <tr>
    <td height="28" colspan="2" class="style2">This is a list of all inactive users in the database. These users have not yet validated their email address. You may validate their email for them by clicking the green check mark, or you may delete it so that they can no longer validate their email address by clicking the red 'x'. You may also enter a first name, last name OR username into the search box to find a user.</td>
  </tr>
  <tr>
    <td height="19" colspan="2"><br />
        <form id="form1" name="form1" method="get" action="<?php echo $_SERVER['PHP_SELF'] . "?db_config_path=" . $db_config_path ; ?>">
          <span class="style2">Search:</span>
          <input type="text" name="search" />
          <input type="submit" value="Go" /><?php if(!empty($_GET['search'])): ?><input type="button" value="View All" onclick="window.location='<?php echo $_SERVER['PHP_SELF'] . "?db_config_path=" . $db_config_path ; ?>'" /><?php endif; ?>
        </form>
        <br />
      <table width="100%" border="0">
        <tr>
          <td width="25%" class="style5">Name</td>
          <td width="26%" class="style5">Username</td>
          <td width="28%" class="style5">Email</td>
          <td width="21%" class="style5">Actions</td>
        </tr>
       <?php echo $userlist ; ?>
      </table><br />
      <?php if($count > 0): ?><div align="center"><span class="style2">Page:</span> <span class="style5"><?php echo $pagelist ; ?></span></div><br /><?php endif; ?>
    <br /></td>
  </tr>

  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center"><?php show_footer($software_signature); ?></div></td>
  </tr>
</table>
</body>
</html>
