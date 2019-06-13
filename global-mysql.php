<?php
//
// File: global-mysql.php
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

// Adds a user to the database
// Must first connect to mysql database
// 4th argument specifies whether or not admin has to approve users first.
//Returns true if successful.
function add_dbuser($dbh,$config,$firstname,$lastname,$email,$phone,$country,$username,$password,$prefix,$approve=true,$admin=false){
	if(validate_name($firstname) && validate_name($lastname) && validate_email_address($email) && validate_password($password) && validate_username($username)){
		if(!$approve || $admin) $status=2; else $status=1;
		$sql = 'INSERT INTO `'.$prefix.'users` ( `id` , `firstname` , `lastname` , `email`, `phone` , `country` , `username` , `password` , `status` , `registered_dtstamp` ) '
		. ' VALUES ( \'\', \''.PDO::quote($firstname).'\', \''.PDO::quote($lastname).'\', \''.PDO::quote($email).'\', \''.PDO::quote($phone).'\', \''.PDO::quote($country).'\', \''.PDO::quote($username).'\', \''.PDO::quote($password).'\', \''.$status.'\', \''.time().'\')';
		if($dbh->query($sql)){
			return true;
		} else {
			db_failure($dbh, $dbh->errorInfo(), $sql);
			return false;
		}
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
		return false;
	}
}
// same as above function except this is used on the user registration page
function user_add_dbuser($dbh,$config,$firstname,$lastname,$email,$phone,$country,$username,$password,$prefix,$status='0'){
	if(validate_name($firstname) && validate_name($lastname) && validate_email_address($email) && validate_password($password) && validate_username($username)){
		$sql = 'INSERT INTO `'.$prefix.'users` ( `id` , `firstname` , `lastname` , `email`, `phone` , `country` , `username` , `password` , `status` , `registered_dtstamp` ) '
		. ' VALUES ( \'\', \''.PDO::quote($firstname).'\', \''.PDO::quote($lastname).'\', \''.PDO::quote($email).'\', \''.PDO::quote($phone).'\', \''.PDO::quote($country).'\', \''.PDO::quote($username).'\', \''.PDO::quote($password).'\', \''.$status.'\', \''.time().'\')';
		if($dbh->query($sql)){
			return true;
		} else {
			db_failure($dbh, $dbh->errorInfo(), $sql);
			return false;
		}
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
		return false;
	}
}

// log a failed login attempt for an ip address
function LogFailedLogin($dbh,$prefix,$username){
	$sql = 'INSERT INTO `'.$prefix.'logins` (`type`, `username`, `timestamp`, `user_agent`, `ip`) VALUES (\'failed\', \''.PDO::quote($username).'\', \''.time().'\', \''.$_SERVER['HTTP_USER_AGENT'].'\', \''.$_SERVER['REMOTE_ADDR'].'\');';
	$dbh->query($sql) or db_failure($dbh, $dbh->errorInfo(), $sql);
}

// return the number of failed logins for an ip address
function CheckFailedLogins($dbh,$prefix,$ip){
	$sql = 'SELECT id FROM '.$prefix.'logins WHERE '.time().'-`timestamp` < 600 and `ip`=\''.$ip.'\' and `type`="failed" and `username`="admin"';
	$result = $dbh->query($sql) or db_failure($dbh, $dbh->errorInfo(), $sql);
	return $result->rowCount();
}

// remove all users that have not verified their email address after 72 hours (259200 seconds)
function PruneInactiveUsers($dbh,$prefix){
	$sql = 'DELETE FROM '.$prefix.'users WHERE `status`=1 and '.time().'-`registered_dtstamp` > 259200';
	$dbh->query($sql) or db_failure($dbh, $dbh->errorInfo(), $sql);
}

?>
