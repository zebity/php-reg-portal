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

// require the template engine class (MiniTemplator)
require('../lib/MiniTemplator.class.php');
$template = new MiniTemplator;
$templatedir = '../templates/';

if(isset($_GET['code']) && isset($_GET['username']))
{

	// make sure this user hasn't already validated their email to prevent users from loosing a higher status
	if(GetCurrentStatus($_GET['username'],$mysql['prefix']) == '0')
	{

		if(check_verification_code($_GET['username'],$_GET['code'],$mysql['prefix']))
		{
			if($config['require_admin_accept']=='true')
			{
				$newstatus = '1';
				if($config['user_welcome_email']=='true'){
					get_email_body_sql('user_PendingApproval',$_GET['username'],$mysql['prefix'],true);
				}
				if($config['admin_user_email']=='true'){
					$emailbody = get_email_body_sql('admin_NewPendingUser',$_GET['username'],$mysql['prefix']);
					sendmail($config['admin_email'],$config['system_messages_email'],get_email_subject($mysql['prefix'],'admin_NewPendingUser'),$emailbody);
				}
			}
			else
			{
				$newstatus = '2';
				if($config['user_welcome_email'] == 'true')
				{
					get_email_body_sql('user_WelcomeEmail',$_GET['username'],$mysql['prefix'],true);
				}
				if($config['admin_user_email']=='true'){
					$emailbody = get_email_body_sql('admin_NewUser',$_GET['username'],$mysql['prefix']);
					sendmail($config['admin_email'],$config['system_messages_email'],get_email_subject($mysql['prefix'],'admin_NewUser'),$emailbody);
				}
			}
			// update the user's status
			UpdateUserStatus($_GET['username'],$newstatus,$mysql['prefix']);
			if(GetCurrentStatus($_GET['username'],$mysql['prefix']) == '2')
			{
				generate_htpasswd($mysql['prefix']);
			}

			// generate template to tell the user they were successfully validated
			$template->readFileIntoString($templatedir."overall_header.html",$header);
			$template->readFileIntoString($templatedir."standard_message.html",$main);
			$template->readFileIntoString($templatedir."overall_footer.html",$footer);

			$template->setTemplateString($header . $main . $footer);

			if($config['require_admin_accept'] == 'true')
			{
				$template->setVariable("message",'Thank you for validating your email address. You have been added to the list pending users. Once the administrator has approved your account, your will be able to login to the protected area. Please wait while you are redirected to the user panel login page.');
			} else {
				$template->setVariable("message",'Thank you for validating your email address. You should now be able to login to the protected area. Please wait while you are redirected the the user panel login page.');
			}

			// make the page redirect to login.php in 10 seconds
			$template->setVariable('refreshseconds','10');
			$template->setVariable('refreshpath','./login.php');
			$template->addBlock('refreshpage');

			$template->setVariable("footer",show_user_footer($software_signature));
			$template->setVariable("pagename","Validate Email");
			$template->generateOutput();
		}
		else
		{
			// generate template if the code or username were incorrect
			$template->readFileIntoString($templatedir."overall_header.html",$header);
			$template->readFileIntoString($templatedir."verify_email.html",$main);
			$template->readFileIntoString($templatedir."overall_footer.html",$footer);

			$template->setTemplateString($header . $main . $footer);

			// error message
			$template->setVariable("error",'The code or username you entered was incorrect.');
			$template->addBlock("error");

			// display a username field input incase what was entered was incorrect
			$template->addBlock("usernamefield");

			$template->setVariable("phpself",$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
			$template->setVariable("footer",show_user_footer($software_signature));
			$template->setVariable("pagename","Validate Email");
			$template->generateOutput();
		}
	}
	else
	{
		// generate template if the code or username were incorrect
		$template->readFileIntoString($templatedir."overall_header.html",$header);
		$template->readFileIntoString($templatedir."standard_message.html",$main);
		$template->readFileIntoString($templatedir."overall_footer.html",$footer);

		$template->setTemplateString($header . $main . $footer);

		$template->setVariable("message",'You have already validated your email address. Please wait while you are redirected to the user panel login page.');

		// make the page redirect to login.php in 10 seconds
		$template->setVariable('refreshseconds','10');
		$template->setVariable('refreshpath','./login.php');
		$template->addBlock('refreshpage');

		$template->setVariable("footer",show_user_footer($software_signature));
		$template->setVariable("pagename","Validate Email");
		$template->generateOutput();
	}
}
else
{
	// generate template if the code or username were incorrect
	$template->readFileIntoString($templatedir."overall_header.html",$header);
	$template->readFileIntoString($templatedir."verify_email.html",$main);
	$template->readFileIntoString($templatedir."overall_footer.html",$footer);

	$template->setTemplateString($header . $main . $footer);

	// should we display a username field, or use a hidden field?
	if(isset($_GET['username']) && check_user_exists($_GET['username'],$mysql['prefix']))
	{
		$template->setVariable("username",$_GET['username']);
		$template->addBlock("hiddenusername");
	}
	else
	{
		$template->addBlock("usernamefield");
	}

	$template->setVariable("phpself",$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
	$template->setVariable("footer",show_user_footer($software_signature));
	$template->setVariable("pagename","Validate Email");
	$template->generateOutput();
}