<?php
session_name('user_sid');
session_start();

// make sure the user is logged in
if(isset($_SESSION['user_logged_in'])){
	// make sure the current session is not expired
	if(isset($_SESSION['StartTimestamp'])){
		if(($_SESSION['StartTimestamp'] - time()) > $config['user_session_expire']){
			session_destroy();
			redirect('./login.php?db_config_path=' . $db_config_path);
		}
	}

	// reset session time since the session hasn't expired
	$_SESSION['StartTimestamp'] = time();

	// check to make sure the ip address of the person now is the same as the person who started the session
	if(isset($_SESSION['UserIP'])){
		if($_SERVER['REMOTE_ADDR'] != $_SESSION['UserIP']){
			session_destroy();
			redirect('./login.php?db_config_path=' . $db_config_path);
		}
	}
	// make sure the browser and os of the person now are the same as the person that started the session
	if(isset($_SESSION['UserAgent'])){
		if($_SERVER['HTTP_USER_AGENT'] != $_SESSION['UserAgent']){
			session_destroy();
			redirect('./login.php?db_config_path=' . $db_config_path);
		}
	}
} else {
	redirect('./login.php?db_config_path=' . $db_config_path);
}
?>
