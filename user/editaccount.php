<?php
/******************************************************************************
* This file is part of the Deadlock PHP User Management System.               *
*                                                                             *
* File Description: This file updates a user's status if they enter the       *
* correct code in the form or query string.                                   *
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
require('../db_config.php');
require('../global.php');

// connect to the database
db_connect($mysql['username'],$mysql['password'],$mysql['database'],$mysql['host']);

// assign config options from database to an array
$config = get_config($mysql['prefix']);

debug_mode($config['debug_mode']);

// remove users that have not verified their email after 72 hours if email verification is enabled
if($config['verify_email']=='true' && $config['prune_inactive_users']=='true'){
	PruneInactiveUsers($mysql['prefix']);
}

// make sure user is logged in
require('./auth.inc.php');

// require the template engine class (MiniTemplator)
require('../lib/MiniTemplator.class.php');
$template = new MiniTemplator;
$templatedir = '../templates/';

// get the user's information
$sql = 'SELECT * FROM '.$mysql['prefix'].'users WHERE `username`=\''.$_SESSION['username'].'\'';
if($result = mysql_query($sql))
{
	$row = mysql_fetch_array($result);
	$username = $row['username'];
	$firstname = $row['firstname'];
	$lastname = $row['lastname'];
	$email = $row['email'];
	$phone = $row['phone'];
	$country = $row['country'];
	$username = $row['username'];
	$password = $row['password'];
}
else
{
	die('The following MySQL query failed. User data could not be retrieved. '.$sql);
}

// if the form has been submitted
if(isset($_POST['submit']))
{
	if(empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email']) || !validate_optional_fields($_POST['phone'], $config['optional_fields_phone']) || !validate_optional_fields($_POST['country'], $config['optional_fields_country']) || match_string($_POST['country'],'Not Selected',$config['optional_fields_country']))
	{
		$errors[] = 'One or more required fields were left empty. Please fill in all required fields.';
	}
	else
	{
		// check to make sure fields validate
		$_POST['firstname'] = ucwords(strtolower($_POST['firstname']));
		$_POST['lastname'] = ucwords(strtolower($_POST['lastname']));
		if(!validate_email_address($_POST['email']))
		{
			$errors[] = 'The email address you entered was invalid.';
		}

		if(!validate_name($_POST['firstname']))
		{
			$errors[] = 'Please enter a first name between 1 and 15 characters.';
		}

		if(!validate_name($_POST['lastname']))
		{
			$errors[] = 'Please enter a last name between 1 and 15 characters.';
		}

		if(strlen($_POST['email']) > 60)
		{
			$errors[] = 'Your email address must be no longer than 60 characters.';
		}

		if(check_email_exists($_POST['email'],$mysql['prefix'],$_SESSION['username']))
		{
			$errors[] = 'The email address you entered already exists for another user.';
		}

		if(!empty($_POST['password']))
		{
			if($_POST['password'] != $_POST['password2'])
			{
				$errors[] = 'The passwords you entered did not match.';
			}
			else
			{
				if(!validate_password($_POST['password']))
				{
					$errors[] = 'For maximum security, your password must be between 6 and 10 characters long, and it must contain at least one letter and one number.';
				}
			}
		}
		if(!validate_phone($_POST['phone'],$config['phone_digits'],$config['optional_fields_phone']))
		{
			$errors[] = 'Your phone number must be numeric and contain '.$config['phone_digits'].' digits.';
		}
	}
	if(!isset($errors))
	{
		if($_POST['firstname'] != $firstname)
		{
			UpdateUserField($username,'firstname',$_POST['firstname'],$mysql['prefix']);
		}

		if($_POST['lastname'] != $lastname)
		{
			UpdateUserField($username,'lastname',$_POST['lastname'],$mysql['prefix']);
		}

		if($_POST['email'] != $email)
		{
			UpdateUserField($username,'email',$_POST['email'],$mysql['prefix']);
		}

		if($_POST['phone'] != $phone)
		{
			UpdateUserField($username,'phone',$_POST['phone'],$mysql['prefix']);
		}

		if($_POST['country'] != $country)
		{
			UpdateUserField($username,'country',$_POST['country'],$mysql['prefix']);
		}

		if(!empty($_POST['password']))
		{
			if($_POST['password'] != $password)
			{
				UpdateUserField($username,'password',$_POST['password'],$mysql['prefix']);
				generate_htpasswd($mysql['prefix']);
			}
			$password = $_POST['password'];
		}

		sendmail($_POST['email'],$config['admin_email'],get_email_subject($mysql['prefix'],'user_AccountChanged'),get_email_body($_POST['firstname'],$_POST['lastname'],$_POST['email'],$_POST['username'],$password,$config['protected_area_url'],$config['deadlock_url'],$config['admin_email'],$mysql['prefix'],'user_AccountChanged'));

		// generate success message
		$template->readFileIntoString($templatedir."overall_header.html",$header);
		$template->readFileIntoString($templatedir."standard_message.html",$main);
		$template->readFileIntoString($templatedir."overall_footer.html",$footer);

		$template->setTemplateString($header . $main . $footer);

		$template->setVariable("message",'Your account has been successfully updated. Please wait while you are redirected to your account information page.');

		// make the page redirect to login.php in 10 seconds
		$template->setVariable('refreshseconds','10');
		$template->setVariable('refreshpath','./account.php');
		$template->addBlock('refreshpage');

		$template->setVariable("footer",show_user_footer($software_signature));
		$template->setVariable("pagename","Edit Your Account");
		$template->generateOutput();
		exit;
	} else {
		// generate error page
		$template->readFileIntoString($templatedir."overall_header.html",$header);
		$template->readFileIntoString($templatedir."edit_account.html",$main);
		$template->readFileIntoString($templatedir."overall_footer.html",$footer);

		$template->setTemplateString($header . $main . $footer);

		// display errors
		foreach($errors as $error){
			$template->setVariable("error",$error);
			$template->addBlock("error");
		}
		$template->addBlock("errortable");

		$template->setVariable("phpself",$_SERVER['PHP_SELF']);
		$template->setVariable("firstname_value",$_POST['firstname']);
		$template->setVariable("lastname_value",$_POST['lastname']);
		$template->setVariable("email_value",$_POST['email']);
		$template->setVariable("phone_value",$_POST['phone']);
		$template->setVariable("username_value",$username);
		$template->setVariable("country_selects",country_menu($_POST['country']));
		$template->setVariable("phone_digits",$config['phone_digits']);
		$template->setVariable("footer",show_user_footer($software_signature));
		$template->setVariable("pagename","Edit Your Account");
		if($config['optional_fields_country']!='true'){
			$template->addblock("optional_country");
		}
		if($config['optional_fields_phone']!='true'){
			$template->addblock("optional_phone");
		}
		$template->generateOutput();
		// exit the script
		exit;
	}
}

// generate default page
$template->readFileIntoString($templatedir."overall_header.html",$header);
$template->readFileIntoString($templatedir."edit_account.html",$main);
$template->readFileIntoString($templatedir."overall_footer.html",$footer);

$template->setTemplateString($header . $main . $footer);

$template->setVariable("phpself",$_SERVER['PHP_SELF']);
$template->setVariable("firstname_value",$firstname);
$template->setVariable("lastname_value",$lastname);
$template->setVariable("email_value",$email);
$template->setVariable("phone_value",$phone);
$template->setVariable("username_value",$username);
$template->setVariable("country_selects",country_menu($country));
$template->setVariable("phone_digits",$config['phone_digits']);
$template->setVariable("footer",show_user_footer($software_signature));
$template->setVariable("pagename","Edit Your Account");
if($config['optional_fields_country']!='true'){
	$template->addblock("optional_country");
}
if($config['optional_fields_phone']!='true'){
	$template->addblock("optional_phone");
}
$template->generateOutput();
?>