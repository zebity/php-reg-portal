<?php
/******************************************************************************
* This file is part of the Deadlock PHP User Management System.               *
*                                                                             *
* File Description: This file adds a user to the database                     *
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

// start session and make sure the user is logged in
//require('./auth.inc.php');

// require the template engine class (MiniTemplator)
require('../lib/MiniTemplator.class.php');
$template = new MiniTemplator;
$templatedir = '../templates/';

if(isset($_POST['submit']))
{
	if(empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email']) || !validate_optional_fields($_POST['phone'], $config['optional_fields_phone']) || !validate_optional_fields($_POST['country'], $config['optional_fields_country']) || match_string($_POST['country'],'Not Selected',$config['optional_fields_country']) || empty($_POST['username']) || empty($_POST['password']) || empty($_POST['password2']))
	{
		$errors[] = 'One or more required fields were left empty. Please fill in all required fields.';
	}
	else
	{
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

		if(!validate_username($_POST['username']))
		{
			$errors[] = 'The username must be alphanumeric and 5-15 characters long.';
		}

		if(check_email_exists($_POST['email'],$mysql['prefix']))
		{
			$errors[] = 'The email address you entered already exists for another user.';
		}
		
		if(strlen($_POST['email']) > 60)
		{
			$errors[] = 'Your email address must be no longer than 60 characters.';
		}

		if($_POST['password'] != $_POST['password2'])
		{
			$errors[] = 'The passwords you entered did not match.';
		}
		else
		{
			if(!validate_password($_POST['password'])){
				$errors[] = 'For maximum security, your password must be between 6 and 10 characters long, and it must contain at least one letter and one number.';
			}
		}
		if(!validate_phone($_POST['phone'],$config['phone_digits'],$config['optional_fields_phone']))
		{
			$errors[] = 'Your phone number must be numeric and contain '.$config['phone_digits'].' digits.';
		}

		if(check_user_exists($_POST['username'],$mysql['prefix']))
		{
			$errors[] = 'The username you have selected is already taken. Please choose a new one.';
		}
	}
	if(empty($errors))
	{
		if($config['verify_email']=='true')
		{
			$status = '0';
		}
		elseif($config['verify_email']!='true' && $config['require_admin_accept']=='true')
		{
			$status = '1';
		}
		else
		{
			$status = '2';
		}

		if(!user_add_dbuser($_POST['firstname'],$_POST['lastname'],$_POST['email'],$_POST['phone'],$_POST['country'],$_POST['username'],$_POST['password'],$mysql['prefix'],$status))
		{
			die('There was an error inserting data into the database. Please contact the administrator.');
		}

		// select the emails to send
		switch($status)
		{
			case '1':
			$useremail = 'user_PendingApproval';
			$adminemail = 'admin_NewPendingUser';
			$page_message = 'Your account information has been added to the database, however, the administrator must approve of it before you are able to login to the protected area. Depending on the system settings, you will receive an email as soon as the administrator answers your request. Please wait while you are redirected to the user account login page.';
			break;
			
			case '2':
			$useremail = 'user_WelcomeEmail';
			$adminemail = 'admin_NewUser';
			generate_htpasswd($mysql['prefix']);
			$page_message = 'An account for you has successfully been created in our database. Please wait while you are redirected to the user login page.';
			break;
			
			case '0':
			$useremail = 'user_EmailVerification';
			$adminemail = 'admin_NewPendingUser';
			$verify_code = random_string(12);
			AddEmailVerifyCode($_POST['username'],$verify_code,$mysql['prefix']);
			$user_verify_email_message = get_email_body($_POST['firstname'],$_POST['lastname'],$_POST['email'],$_POST['username'],$_POST['password'],$config['protected_area_url'],$config['deadlock_url'],$config['admin_email'],$mysql['prefix'],$useremail);
			$user_verify_email_message = str_ireplace('<%VerificationCode%>',$verify_code,$user_verify_email_message);
			$page_message = 'Your account has been added to the database, however, you must verify that the email address entered is actually your email address. To do this, we have sent an email to the address provided. Click the link inside of the email to verify that it is your email. Please wait while you are redirected to the user account login page.';
			sendmail($_POST['email'],$config['admin_email'],get_email_subject($mysql['prefix'],$useremail),$user_verify_email_message);
			break;
		}

		if($config['user_welcome_email']=='true' && $status!='0')
		{
			get_email_body_sql($useremail,$_POST['username'],$mysql['prefix'],true);
		}

		if($config['admin_user_email']=='true' && $status!='0')
		{
			sendmail($config['admin_email'],$config['system_messages_email'],get_email_subject($mysql['prefix'],$adminemail),get_email_body_sql($adminemail,$_POST['username'],$mysql['prefix']));
		}

		$template->readFileIntoString($templatedir."overall_header.html",$header);
		$template->readFileIntoString($templatedir."user_registration_message.html",$main);
		$template->readFileIntoString($templatedir."overall_footer.html",$footer);

		$template->setTemplateString($header . $main . $footer);

		$template->setVariable('message',$page_message);
		$template->setVariable('refreshseconds','20');
		$template->setVariable('refreshpath','./login.php');
		$template->addBlock('refreshpage');

		// user information template variables
		$template->setVariable('firstname',$_POST['firstname']);
		$template->setVariable('lastname',$_POST['lastname']);
		$template->setVariable('username',$_POST['username']);
		$template->setVariable('email',$_POST['email']);
		$template->setVariable('password',$_POST['password']);

		if(!empty($_POST['phone']))
		{
			$template->setVariable('phone',$_POST['phone']);
		}
		else
		{
			$template->setVariable('phone','Not Available');
		}

		if(!empty($_POST['country']))
		{
			$template->setVariable('country',$_POST['country']);
		}
		else
		{
			$template->setVariable('country','Not Available');
		}

		$template->setVariable("footer",show_user_footer($software_signature));
		$template->setVariable("pagename","Registration Successful");

		// output the generated html code
		$template->generateOutputPage();

		// exit the script
		exit;

	}
	else
	{
		// If there were errors in the form, generate a new form page with errors and values filled in.
		$template->readFileIntoString($templatedir."overall_header.html",$header);
		$template->readFileIntoString($templatedir."user_registration.html",$main);
		$template->readFileIntoString($templatedir."overall_footer.html",$footer);

		$template->setTemplateString($header . $main . $footer);

		foreach($errors as $error){
			$template->setVariable("error",$error);
			$template->addBlock("errors");
		}

		$template->addBlock("errortable");
		$template->setVariable("firstname_value",$_POST['firstname']);
		$template->setVariable("lastname_value",$_POST['lastname']);
		$template->setVariable("email_value",$_POST['email']);
		$template->setVariable("phone_value",$_POST['phone']);
		$template->setVariable("username_value",$_POST['username']);
		$template->setVariable("country_selects",country_menu($_POST['country']));
		$template->setVariable("phone_digits",$config['phone_digits']);
		$template->setVariable("footer",show_user_footer($software_signature));
		$template->setVariable("pagename","Register");
		if($config['optional_fields_country']!='true')
		{
			$template->addblock("optional_country");
		}

		if($config['optional_fields_phone']!='true')
		{
			$template->addblock("optional_phone");
		}

		// output page
		$template->generateOutput();
		exit;
	}
}

// show the default page
$template->readFileIntoString($templatedir."overall_header.html",$header);
$template->readFileIntoString($templatedir."user_registration.html",$main);
$template->readFileIntoString($templatedir."overall_footer.html",$footer);

$template->setTemplateString($header . $main . $footer);

$template->setVariable("country_selects",country_menu('Not Selected'));
$template->setVariable("footer",show_user_footer($software_signature));
$template->setVariable("phone_digits",$config['phone_digits']);
$template->setVariable("pagename","Register");
if($config['optional_fields_country']!='true')
{
	$template->addblock("optional_country");
}

if($config['optional_fields_phone']!='true')
{
	$template->addblock("optional_phone");
}

$template->generateOutput();
?>