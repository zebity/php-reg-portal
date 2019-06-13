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

if(isset($_POST['email'])){
	// make sure the email address exists for a user
	if(check_email_exists($_POST['email'],$mysql['prefix']))
	{
		// query mysql to get the username of the email address specified
		$sql = 'SELECT `username` FROM `'.$mysql['prefix'].'users` WHERE `email`=\''.$_POST['email'].'\'';
		if($result = mysql_query($sql))
		{
			$username = mysql_fetch_row($result);
			$username = $username[0];
		}
		else
		{
			die('The following MySQL query failed. '.$sql);
		}
		get_email_body_sql('user_ForgotPassword',$username,$mysql['prefix'],true);

		// generate success page
		$template->readFileIntoString($templatedir."overall_header.html",$header);
		$template->readFileIntoString($templatedir."standard_message.html",$main);
		$template->readFileIntoString($templatedir."overall_footer.html",$footer);

		$template->setTemplateString($header . $main . $footer);

		// make the page redirect to login.php in 10 seconds
		$template->setVariable('refreshseconds','10');
		$template->setVariable('refreshpath','./login.php');
		$template->addBlock('refreshpage');

		$template->setVariable('message','We have sent the requested information to your mailbox. Please wait while you are redirected to the user panel login page.');
		$template->setVariable("phpself",$_SERVER['PHP_SELF']);
		$template->setVariable("footer",show_user_footer($software_signature));
		$template->setVariable("pagename","Forgotten Password");
		$template->generateOutput();
	}
	else
	{
		// user does not exist print out error page
		$template->readFileIntoString($templatedir."overall_header.html",$header);
		$template->readFileIntoString($templatedir."forgot_password.html",$main);
		$template->readFileIntoString($templatedir."overall_footer.html",$footer);

		$template->setTemplateString($header . $main . $footer);

		// error message
		$template->setVariable("error",'The email address entered was not found in our database.');
		$template->addBlock("error");

		$template->setVariable("phpself",$_SERVER['PHP_SELF']);
		$template->setVariable("footer",show_user_footer($software_signature));
		$template->setVariable("pagename","Forgotten Password");
		$template->generateOutput();
	}
}
else
{
	// user does not exist print out error page
	$template->readFileIntoString($templatedir."overall_header.html",$header);
	$template->readFileIntoString($templatedir."forgot_password.html",$main);
	$template->readFileIntoString($templatedir."overall_footer.html",$footer);

	$template->setTemplateString($header . $main . $footer);

	$template->setVariable("phpself",$_SERVER['PHP_SELF']);
	$template->setVariable("footer",show_user_footer($software_signature));
	$template->setVariable("pagename","Forgotten Password");
	$template->generateOutput();
}
?>