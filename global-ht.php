<?php
//
// File: global_ht.php
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

// This pulls all info from the database and rewrites the htpasswd.
function generate_htpasswd ($dbh,$prefix){

	$config = get_config($prefix);

	$sql = 'SELECT `username`,`password` FROM '.$prefix.'users WHERE `status`=2';
	if($result=$dbh->query($sql)){
		if($result->rowCount() > 0){
			$buffer = '';
			for ($i=0;$row = $result->fetch(PDO::FETCH_ASSOC);$i++) {
				if($config['digest_auth']=='true'){
					$user_password = enc_pass($row['password'],true,$row['username'],$config['protected_area_name']);
					$buffer .= $row['username'].':'.$config['protected_area_name'].':'.$user_password;
				} else {
					$user_password = enc_pass($row['password']);
					$buffer .= $row['username'].':'.$user_password;
				}
				if($result->rowCount() != $i) $buffer .= "\n";
			}
		} else {
			$buffer = ' ';
		}
	} else {
		db_failure($dbh, $dbh->errorInfo(), $sql);
	}

	$htpasswd_path = $_SERVER['DOCUMENT_ROOT'].$config['htpasswd_path'];
	$htaccess_path = $_SERVER['DOCUMENT_ROOT'].$config['htaccess_path'];

	$handle = fopen($htpasswd_path,'w') or die('Deadlock could not open the htpasswd file for writing. '.$htpasswd_path);
	fwrite($handle,$buffer) or die('Deadlock could not write the htpasswd file. '.$htpasswd_path);
	fclose($handle);
}

// generate the htaccess file
function generate_htaccess($prefix){
	$config = get_config($prefix);

	$htaccess_path = $_SERVER['DOCUMENT_ROOT'].$config['htaccess_path'];
	
	if($config['htpasswd_relative']=='true'){
		$htpasswd_path = $_SERVER['DOCUMENT_ROOT'].$config['htpasswd_path'];
	} else {
		$htpasswd_path = $config['htpasswd_path'];
	}

	if($config['digest_auth']=='true'){
		$authtype = 'Digest';
		$authuserfile = 'AuthDigestFile "'.$htpasswd_path.'"';
	} else {
		$authtype = 'Basic';
		$authuserfile = 'AuthUserFile "'.$htpasswd_path.'"';
	}

	if(!empty($config['err_401_doc'])){
		$err_401_doc = $config['err_401_doc'];
	} else {
		$error_401_doc = null;
	}

	$buffer = "AuthName \"".$config['protected_area_name']."\"\nAuthType ".$authtype."\n".$authuserfile."\nrequire valid-user";
	if(!empty($config['err_401_doc'])){
		$buffer .= "\nErrorDocument 401 " . $config['err_401_doc'];
	}

	$handle = fopen($htaccess_path,'w') or die('Deadlock could not open the htaccess file for writing. '.$htaccess_path);
	fwrite($handle,$buffer) or die('Deadlock could not write the htaccess file. '.$htaccess_path);
	fclose($handle);
}

?>
