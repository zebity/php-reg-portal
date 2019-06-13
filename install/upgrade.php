<?php
/******************************************************************************
* This file is part of the Deadlock PHP User Management System.               *
*                                                                             *
* File Description: Upgrade database tables                                   *
*                                                                             *
* Deadlock is free software; you can redistribute it and/or modify            *
* it under the terms of the GNU General Public License as published by        *
* the Free Software Foundation; either version 2 of the License, or           *
* (at your option) any later version.                                         *
*                                                                             *
* Foobar is distributed in the hope that it will be useful,                   *
* but WITHOUT ANY WARRANTY; without even the implied warranty of              *
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               *
* GNU General Public License for more details.                                *
*                                                                             *
* You should have received a copy of the GNU General Public License           *
* along with Deadlock; if not, write to the Free Software                     *
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA  *
******************************************************************************/

require('../db_config.php');

function get_version(){
	$file = file_get_contents('../global.php');
	$pattern = '#\$software_version = \'(.*)\';#';
	preg_match($pattern,$file,$matches);
	return $matches[1];
}

if(!@include('../version.php')){
	$software_version = get_version();
}

mysql_connect($mysql['host'],$mysql['username'],$mysql['password']) or die('Could not connect to mysql.');
mysql_select_db($mysql['database']) or die('Could not select the database.');

if($software_version < 0.60){
	$sql['Modifying logins table, 0.60 - Change 1'] = 'ALTER TABLE `'.$mysql['prefix'].'logins` ADD `ip` VARCHAR( 50 ) NOT NULL ;';
	$sql['Modifying logins table, 0.60 - Change 2'] = 'ALTER TABLE `'.$mysql['prefix'].'logins` ADD `type` VARCHAR( 20 ) NOT NULL AFTER `id` ;';
	$sql['Inserting new configuration option, 0.60 - Change 3'] = 'INSERT INTO `'.$mysql['prefix'].'config` (`option_name` , `value`) VALUES (\'prune_inactive_users\', \'true\');';
	$sql['Inserting new configuration option, 0.60 - Change 4'] = 'INSERT INTO `'.$mysql['prefix'].'config` (`option_name` , `value`) VALUES (\'admin_username\', \'admin\');';
}

if($software_version < 0.62){
	$sql['Modifying users table, 0.62 - Change 1'] = 'ALTER TABLE `'.$mysql['prefix'].'users` CHANGE `firstname` `firstname` VARCHAR( 30 );';
	$sql['Modifying users table, 0.62 - Change 2'] = 'ALTER TABLE `'.$mysql['prefix'].'users` CHANGE `lastname` `lastname` VARCHAR( 30 );';
	$sql['Modifying users table, 0.62 - Change 3'] = 'ALTER TABLE `'.$mysql['prefix'].'users` CHANGE `email` `email` VARCHAR( 50 );';
	$sql['Inserting new configuration option, 0.62 - Change 4'] = 'INSERT INTO `'.$mysql['prefix'].'config` (`option_name` , `value`) VALUES (\'digest_auth\', \'false\');';
}

if($software_version < 0.64){
	$sql['Inserting new configuration option, 0.64 - Change 1'] = 'INSERT INTO `'.$mysql['prefix'].'config` (`option_name` , `value`) VALUES (\'err_401_doc\', \'\');';
	$sql['Inserting new configuration option, 0.64 - Change 2'] = 'INSERT INTO `'.$mysql['prefix'].'config` (`option_name` , `value`) VALUES (\'htpasswd_relative\', \'true\');';
}

if(!empty($sql)){
	foreach($sql as $text => $query){
		if(mysql_query($query)){
			print $text . '... <font color="green">Success</font><br /><br />';
		} else {
			print $text . '... <font color="red">Failed</font><br /><font size="small">MySQL said: '.mysql_error().'<br /><br />';
		}
		flush();
	}

	print 'If one of the above operations failed, look at the error and try to fix the problem. If nothing failed, you may continue with the upgrade.';
} else {
	print 'Your database does not need any updates.';
}
?>
