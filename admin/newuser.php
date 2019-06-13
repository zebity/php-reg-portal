<?php
//
// File: newuser.php
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
	PruneInactiveUsers($dbh,$pdodb['prefix']);
}

// start the session
admin_sessions($config['admin_session_expire'], "?db_config_path=" . $db_config_path);
if(!isset($_SESSION['logged_in'])){
	redirect('./login.php?db_config_path=' . $db_config_path);
}

if(isset($_POST['submit'])){
	if(empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email']) || !validate_optional_fields($_POST['phone'], $config['optional_fields_phone']) || !validate_optional_fields($_POST['country'], $config['optional_fields_country']) || match_string($_POST['country'],'Not Selected',$config['optional_fields_country']) || empty($_POST['username']) || empty($_POST['password']) || empty($_POST['password2'])){
		$errors[] = 'One or more required fields were left empty. Please fill in all required fields.';
	} else {
		$_POST['firstname'] = ucwords(strtolower($_POST['firstname']));
		$_POST['lastname'] = ucwords(strtolower($_POST['lastname']));
		if(!validate_email_address($_POST['email'])){
			$errors[] = 'The email address you entered was invalid.';
		}
		if(!validate_name($_POST['firstname'])){
			$errors[] = 'Please enter a first name between 1 and 15 characters.';
		}
		if(!validate_name($_POST['lastname'])){
			$errors[] = 'Please enter a last name between 1 and 15 characters.';
		}
		if(!validate_username($_POST['username'])){
			$errors[] = 'The username must be alphanumeric and 5-15 characters long.';
		}
		if(check_email_exists($dbh,$_POST['email'],$pdodb['prefix'])){
			$errors[] = 'The email address you entered already exists for another user.';
		}
		if(strlen($_POST['email']) > 60){
			$errors[] = 'Please enter an email that is no longer than 60 characters.';
		}
		if($_POST['password'] != $_POST['password2']){
			$errors[] = 'The passwords you entered did not match.';
		} else {
			if(!validate_password($_POST['password'], $config['min_passwd_length'], $config['max_passwd_length'],
						$config['passwd_pattern_1'], $config['passwd_pattern_2'])){
				$errors[] = 'Your password must be between ' . $config['min_passwd_length'] .
					 ' and ' . $config['max_passwd_length'] .  ' characters long, and should: ' .
					$config['passwd_rule'] . '.';
			}
		}
		if(!validate_phone($_POST['phone'],$config['phone_digits'],$config['optional_fields_phone'])){
			$errors[] = 'Your phone number must be numeric and contain '.$config['phone_digits'].' digits.';
		}
		if(check_user_exists($dbh,$_POST['username'],$pdodb['prefix'])){
			$errors[] = 'The username you have selected is already taken. Please choose a new one.';
		}
	}
	if(empty($errors)){
		if(!add_dbuser($dbh,$config,$_POST['firstname'],$_POST['lastname'],$_POST['email'],$_POST['phone'],$_POST['country'],$_POST['username'],$_POST['password'],$config['realm'],$pdodb['prefix'],true,true)){
			die('There was an error inserting data into the database.');
		}
		if ($pdodb['mechanism'] == 'htaccess') {
			generate_htpasswd($pdodb['prefix']);
		}
		$welcome_email_path = '../emails/UserWelcome_user.txt';
		$notify_admin_email_path = '../emails/AdminNotify_admin.txt';
		if(isset($_POST['welcome_user'])){
			if(!sendmail($_POST['email'],$config['admin_email'],get_email_subject($dbh,$pdodb['prefix'],'user_WelcomeEmail'),get_email_body($dbh,$_POST['firstname'],$_POST['lastname'],$_POST['email'],$_POST['username'],$_POST['password'],$config['protected_area_url'],$config['regportal_url'],$config['admin_email'],$pdodb['prefix'],'user_WelcomeEmail'))){
				die('There was an error sending a welcome email to the new user.');
			}
		}
		if(isset($_POST['notify_admin'])){
			if(!sendmail($config['admin_email'],$config['system_messages_email'],get_email_subject($dbh,$pdodb['prefix'],'admin_NewUser'),get_email_body($dbh,$_POST['firstname'],$_POST['lastname'],$_POST['email'],$_POST['username'],$_POST['password'],$config['protected_area_url'],$config['regportal_url'],$config['admin_email'],$pdodb['prefix'],'admin_NewUser'))){
				die('There was an error sending a notification email to the administrator.');
			}
		}
		// Everything was successful at this point. Print out the success page.
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<META HTTP-EQUIV="Refresh" CONTENT="5; URL=./userinfo.php?db_config_path=<?php echo $db_config_path; ?>&user=<?php echo htmlentities($_POST['username']); ?>">
<title>RegPortal - User Created Successfully</title>
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
    <td height="20" colspan="2" class="style2"><strong><a href="./index.php">Top</a>: User Created Successfully </strong></td>
  </tr>
  <tr>
    <td height="28" colspan="2" class="style2">The user <?php echo htmlentities($_POST['username']); ?> was created successfully! Please wait while your are redirected to the user's profile.</td>
  </tr>
  <tr>
    <td height="19" colspan="2"><br />
      <span class="style2">If you are not redirected within 5 seconds, <a href="./userinfo.php?db_config_path=<?php echo $db_config_path; ?>&user=<?php echo htmlentities($_POST['username']); ?>">click here</a>...</span><br /><br /><br /></td>
  </tr>
  
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center"><?php show_footer($software_signature); ?></div></td>
  </tr>
</table>
</body>
</html>
<?php
exit;
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal - Create New User</title>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="../images/hint.js">
/***********************************************
* Show Hint script- © Dynamic Drive (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit http://www.dynamicdrive.com/ for this script and 100s more.
***********************************************/
</script>
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
    <td height="20" colspan="2" class="style2"><strong><a href="./index.php?db_config_path=<?php echo $db_config_path; ?>">Top</a>: Create User Account </strong></td>
  </tr>
  <tr>
    <td height="28" colspan="2" class="style2">To add a user, fill in the required fields below. A welcome email will be sent to the email address provided once the account has been created. </td>
  </tr>
  <tr>
    <td height="275" colspan="2"><?php if (!empty($errors)){ ?><table width="95%" height="24" border="0" align="center">
      <tr>
        <td height="20">
		<div class="style9"><ul>
		<?php
		foreach($errors as $error){
			print '<li>'.$error.'</li>';
		}
		?>
		</ul></div></td>
      </tr>
    </table>
      <?php } else { print '<br />'; } ?>
	  <form action="<?php echo $_SERVER['PHP_SELF'] . "?db_config_path=" . $db_config_path; ?>" method="post">
      <table width="73%" border="0" align="center">
      <tr>
        <td width="47%" class="style5">First Name:</td>
        <td width="53%"><input name="firstname" maxlength="15" type="text" id="firstname"<?php if(isset($_POST['firstname'])) print ' value="'.$_POST['firstname'].'"'; ?> /><a href="#" class="hintanchor" onMouseover="showhint('Please enter your first name. This must be 1-15 characters and alphanumeric.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Last Name: </td>
        <td><input name="lastname" maxlength="15" type="text" id="lastname"<?php if(isset($_POST['lastname'])) print ' value="'.$_POST['lastname'].'"'; ?> /><a href="#" class="hintanchor" onMouseover="showhint('Please enter your last name. This must be 1-15 characters and alphanumeric.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Email:</td>
        <td><input name="email" type="text" id="email"<?php if(isset($_POST['email'])) print ' value="'.$_POST['email'].'"'; ?> /><a href="#" class="hintanchor" onMouseover="showhint('Please enter your email address. This email address must be valid.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Phone<?php if($config['optional_fields_phone']=="false") print ' (optional)'; ?>: </td>
        <td><input name="phone" type="text" id="phone"<?php if(isset($_POST['phone'])) print ' value="'.$_POST['phone'].'"'; ?> /><a href="#" class="hintanchor" onMouseover="showhint('Please enter your phone number. This should be <?php echo $config['phone_digits']; ?> digits and contain only numbers.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Country<?php if($config['optional_fields_country']=="false") print ' (optional)'; ?>:</td>
        <td>
<?php
if(isset($_POST['country'])){
	print country_menu($_POST['country']);
} else {
	print country_menu('Not Selected');
}
?></td>
      </tr>
      <tr class="style5">
        <td colspan="2" class="style2">&nbsp;</td>
        </tr>
      <tr>
        <td class="style5">Username:</td>
        <td><input name="username" maxlength="15" type="text" id="username"<?php if(isset($_POST['username'])) print ' value="'.$_POST['username'].'"'; ?> /><a href="#" class="hintanchor" onMouseover="showhint('Please choose a username. This must be alphanumeric and contain 5-10 characters.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Password:</td>
        <td><input name="password" maxlength="10" type="password" id="password" /><a href="#" class="hintanchor" onMouseover="showhint('Please choose a password. For maximum security this must be 6-10 characters and must contain at least one letter and one number', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Confirm Password: </td>
        <td><input name="password2" maxlength="10" type="password" id="password2" /><a href="#" class="hintanchor" onMouseover="showhint('Please confirm the password you entered above.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style2"><input name="welcome_user" type="checkbox" id="welcome_user" value="1" <?php if(($config['user_welcome_email']=="true" && !isset($_POST['submit'])) || isset($_POST['welcome_user'])) print 'checked="checked" '; ?>/>
        Send welcome email<a href="#" class="hintanchor2" onMouseover="showhint('Check this box if you would like to send a welcome email to the above email address.', this, event, '150px')">[?]</a></td>
        <td class="style2"><input name="notify_admin" type="checkbox" id="notify_admin" value="checkbox" <?php if(($config['admin_user_email']=="true" && !isset($_POST['submit'])) || isset($_POST['notify_admin'])) print 'checked="checked" '; ?>/>
Notify administrator<a href="#" class="hintanchor2" onmouseover="showhint('Check this box if you would like to send the administrator a notification email with this user\'s information.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td colspan="2" class="style2">&nbsp;</td>
        </tr>
      <tr>
        <td>&nbsp;</td>
        <td><input type="hidden" name="submit" value="1" /><input type="submit" value="Create User Account" /></td>
      </tr>
    </table>
	<br />
	</form>    </td>
  </tr>
  
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center"><?php show_footer($software_signature); ?></div></td>
  </tr>
</table>
</body>
</html>
