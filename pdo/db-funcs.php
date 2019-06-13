<?php
//
// File: db-funcs.php
//
// Contents: This include file defined the PDO database functions, which encapsulate the different DB providers.
//
// Authur: John Hartley (Graphica Software/Dokmai Pty Ltd.
//
// Copyright: (c) Graphica Software/Dokmai Pty Ltd 2011
//		 and provided for use under terms of GNU GPL V3
//

function db_failure($dbh, $arr, $sql) {
	$str = '';
	foreach ($arr as $item)
		$str .= $item . "|";
	die("RDBMS SQL error [" . $sql . "] :" . $str); 
}

function connect_db($pdodb, &$err) {

	try {
		if ($pdodb['provider'] == 'mysql') {
			$dbh = new PDO("mysql:host={$pdodb['host']};port={$pdodb['port']};dbname={$pdodb['database']}",
					$pdodb['username'], $pdodb['password']);
		}
		elseif ($pdodb['provider'] == 'pgsql') {
			$dbh = new PDO("pgsql:host={$pdodb['host']};port={$pdodb['port']};dbname={$pdodb['database']}",
					$pdodb['username'], $pdodb['password']);
		}
		elseif ($ppddb['provider'] == 'mssql') {
			$dbh = new PDO("mssql:host={$pdodb['host']};dbname={$pdodb['database']}",
					$pdodb['username'], $pdodb['password']);
		}
		else {
			$err = 'Unsupported PDO Provider: ' . $pdodb['provider'];
		}
	}
	catch (PDOExecption $except) {
		$err = 'Connection Failed: ' . $except->getMessage();
	}

	return($dbh);
}
?>
