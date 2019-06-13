<?php
//
// File: index.php
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
//         PHP's PDO functions.
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


if (! isset($_SERVER['PHP_AUTH_USER'])) {
        die('RegPortal is not configured securely, please secure the install directory!');
}

if (empty($_GET) || (! isset($_GET['db_config_path']))) {
        die("RegPortal appears to be have invoked without specifing the db_config.php location.");
}

header('Location: ./install.php?db_config_path=' . $_GET['db_config_path']);
?>
