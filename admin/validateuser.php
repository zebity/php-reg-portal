<?php
/******************************************************************************
* This file is part of the Deadlock PHP User Management System.               *
*                                                                             *
* File Description: Deletes Users                                             *
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

// start the session
admin_sessions($config['admin_session_expire']);
if(!isset($_SESSION['logged_in'])){
	redirect('./login.php');
}

// check if the user exists, just to prevent errors. if they exist, update their status appripriately
if(check_user_exists($_GET['user'],$mysql['prefix'])){
	if($config['require_admin_accept']=='true'){
		$newstatus = '1';
		if($config['user_welcome_email']=='true'){
			get_email_body_sql('user_PendingApproval',$_GET['user'],$mysql['prefix'],true);
		}
		if($config['admin_user_email']=='true'){
			$emailbody = get_email_body_sql('admin_NewPendingUser',$_GET['user'],$mysql['prefix']);
			sendmail($config['admin_email'],$config['system_messages_email'],get_email_subject($mysql['prefix'],'admin_NewPendingUser'),$emailbody);
		}
	} else {
		$newstatus = '2';
		if($config['user_welcome_email'] == 'true')
		{
			get_email_body_sql('user_WelcomeEmail',$_GET['user'],$mysql['prefix'],true);
		}
		if($config['admin_user_email']=='true'){
			$emailbody = get_email_body_sql('admin_NewUser',$_GET['user'],$mysql['prefix']);
			sendmail($config['admin_email'],$config['system_messages_email'],get_email_subject($mysql['prefix'],'admin_NewUser'),$emailbody);
		}
	}
	UpdateUserStatus($_GET['user'],$newstatus,$mysql['prefix']);

	generate_htpasswd($mysql['prefix']);
}

// redirect back to the user list regardless of whether or not the operation was successful
redirect('./inactiveusers.php');

?>