<?php
//
// File: edituser.php
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
admin_sessions($config['admin_session_expire']);
if(!isset($_SESSION['logged_in'])){
	redirect('./login.php?db_config_path=' . $db_config_path);
}

// if the form has been submitted
if(isset($_POST['submit'])){
	$sql = 'SELECT * FROM '.$pdodb['prefix'].'users WHERE username=\''.$_POST['username'].'\'';
	if($result = $dbh->query($sql)){
		while(($row = $result->fetch(PDO::FETCH_ASSOC)) != false){
			$username = $row['username'];
			$firstname = $row['firstname'];
			$lastname = $row['lastname'];
			$email = $row['email'];
			$phone = $row['phone'];
			$country = $row['country'];
			$username = $row['username'];
			$password = $row['password'];
		}
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}
	if(empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email']) || !validate_optional_fields($_POST['phone'], $config['optional_fields_phone']) || !validate_optional_fields($_POST['country'], $config['optional_fields_country']) || match_string($_POST['country'],'Not Selected',$config['optional_fields_country']) || empty($_POST['username'])){
		$errors[] = 'One or more required fields were left empty. Please fill in all required fields.';
	} else {
		// check to make sure fields validate
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
		if(strlen($_POST['email']) > 60){
			$errors[] = 'Please enter an email that is no longer than 60 characters.';
		}
		if(check_email_exists($_POST['email'],$pdodb['prefix'],$_POST['username'])){
			$errors[] = 'The email address you entered already exists for another user.';
		}
		if(!empty($_POST['password'])){
			if($_POST['password'] != $_POST['password2']){
				$errors[] = 'The passwords you entered did not match.';
			} else {
				if(!validate_password($_POST['password'], $config['min_passwd_length'], $config['max_passwd_length'],
							$config['passwd_pattern_1'], $config['passwd_pattern_2'])){
					$errors[] = 'Your password must be between ' . $config['min_passwd_length'] . ' and ' .
						 $config['max_passwd_length'] and should: ' . $config['passwd_rule'] . '.';
				}
			}
		}
		if(!validate_phone($_POST['phone'],$config['phone_digits'],$config['optional_fields_phone'])){
			$errors[] = 'Your phone number must be numeric and contain '.$config['phone_digits'].' digits.';
		}
	}
	if(!isset($errors)){
		if($_POST['firstname'] != $firstname){
			UpdateUserField($username,'firstname',$_POST['firstname'],$pdodb['prefix']);
		}
		if($_POST['lastname'] != $lastname){
			UpdateUserField($username,'lastname',$_POST['lastname'],$pdodb['prefix']);
		}
		if($_POST['email'] != $email){
			UpdateUserField($username,'email',$_POST['email'],$pdodb['prefix']);
		}
		if($_POST['phone'] != $phone){
			UpdateUserField($username,'phone',$_POST['phone'],$pdodb['prefix']);
		}
		if($_POST['country'] != $country){
			UpdateUserField($username,'country',$_POST['country'],$pdodb['prefix']);
		}
		if(!empty($_POST['password'])){
			if($_POST['password'] != $password){
				UpdateUserField($username,'password',$_POST['password'],$pdodb['prefix']);
				generate_htpasswd($pdodb['prefix']);
			}
		}
		if(isset($_POST['notify_user'])){
			if(!empty($_POST['password'])){
				$password = $_POST['password'];
			}
			if(!sendmail($email,$config['admin_email'],get_email_subject($dbh,$pdodb['prefix'],'user_AccountChanged'),get_email_body($dbh,$_POST['firstname'],$_POST['lastname'],$_POST['email'],$_POST['username'],$password,$config['protected_area_url'],$config['regportal_url'],$config['admin_email'],$pdodb['prefix'],'user_AccountChanged'))){
				die('The email to the user failed to be sent.');
			}
		}
		redirect('./userinfo.php?db_config_path=" . $db_config_path . '&user='.$username);
	}
}


if(isset($_GET['user']) && check_user_exists($_GET['user'],$pdodb['prefix'])){
	$sql = 'SELECT * FROM '.$pdodb['prefix'].'users WHERE username=\''.$_GET['user'].'\'';
	if($result = $dbh->query($sql)){
		while($row = $result->fetch(PDO::FETCH_ASSOC)){
			$username = $row['username'];
			$firstname = $row['firstname'];
			$lastname = $row['lastname'];
			$email = $row['email'];
			$phone = $row['phone'];
			$country = $row['country'];
			$username = $row['username'];
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal - Edit User Account</title>
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
    <td height="20" colspan="2" class="style2"><strong><a href="./index.php?db_config_path=<?php echo $db_config_path; ?>">Top</a>: Edit User Account </strong></td>
  </tr>
  <tr>
    <td height="28" colspan="2" class="style2">To edit a user, change the values in the below form, then click Update. All fields are required except for those marked &quot;optional&quot;. </td>
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
	  <form action="<?php echo $_SERVER['PHP_SELF']; ?>?<?php echo $_SERVER['QUERY_STRING']; ?>" method="post">
      <table width="73%" border="0" align="center">
      <tr>
        <td width="47%" class="style5">First Name:</td>
        <td width="53%"><input name="firstname" maxlength="15" type="text" id="firstname" value="<?php if(isset($_POST['firstname'])) print $_POST['firstname']; else print $firstname ?>" /><a href="#" class="hintanchor" onMouseover="showhint('Please enter your first name. This must be 1-15 characters and alphanumeric.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Last Name: </td>
        <td><input name="lastname" maxlength="15" type="text" id="lastname" value="<?php if(isset($_POST['lastname'])) print $_POST['lastname']; else print $lastname ?>" /><a href="#" class="hintanchor" onMouseover="showhint('Please enter your last name. This must be 1-15 characters and alphanumeric.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Email:</td>
        <td><input name="email" type="text" id="email" value="<?php if(isset($_POST['email'])) print $_POST['email']; else print $email ?>" /><a href="#" class="hintanchor" onMouseover="showhint('Please enter your email address. This email address must be valid.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Phone<?php if($config['optional_fields_phone']=="false") print ' (optional)'; ?>: </td>
        <td><input name="phone" type="text" id="phone" value="<?php if(isset($_POST['phone'])) print $_POST['phone']; else print $phone ?>" /><a href="#" class="hintanchor" onMouseover="showhint('Please enter your phone number. This should be <?php echo $config['phone_digits']; ?> digits and contain only numbers.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Country<?php if($config['optional_fields_country']=="false") print ' (optional)'; ?>:</td>
        <td>
<?php
if(isset($_POST['country'])){
	print country_menu($_POST['country']);
} else {
	print country_menu($country);
}
?></td>
      </tr>
      <tr class="style5">
        <td colspan="2" class="style2">&nbsp;</td>
        </tr>

      <tr>
        <td class="style5">Username:</td>
        <td><input type="text" disabled="disabled" value="<?php echo $username; ?>" /><a href="#" class="hintanchor" onMouseover="showhint('Please choose a username. This must be alphanumeric and contain 5-10 characters.', this, event, '150px')"></a></td>
      </tr>
      <tr>
        <td class="style5"> New Password:</td>
        <td><input name="password" maxlength="10" type="password" id="password" /><a href="#" class="hintanchor" onMouseover="showhint('Enter the new password here. If you want to keep the current password, leave this box blank.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style5">Confirm Password: </td>
        <td><input name="password2" maxlength="10" type="password" id="password2" /><a href="#" class="hintanchor" onMouseover="showhint('If you are changing the password, confirm what you entered above.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td class="style2">&nbsp;</td>
        <td class="style2">&nbsp;</td>
      </tr>
      <tr>
        <td class="style2">Notify User:</td>
        <td class="style2"><input name="notify_user" type="checkbox" id="notify_user" value="1" <?php if(($config['user_welcome_email']=="true" && !isset($_POST['submit'])) || isset($_POST['welcome_user'])) print 'checked="checked" '; ?>/>
          <a href="#" class="hintanchor" onmouseover="showhint('Check this box if you would like to notify the user of these changes.', this, event, '150px')">[?]</a></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td><input type="hidden" name="submit" value="1" /><input type="hidden" name="username" value="<?php echo $username; ?>; " /><input type="submit" value="Update" /> <input type="button" onclick="window.location='./userinfo.php?user=<?php echo $username; ?>'" value="Back" /></td>
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
</html><?php 
}
?>
