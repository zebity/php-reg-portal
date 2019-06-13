<?php
$sql[] = "CREATE TABLE " . $pdodb['prefix'] . "config (
id		serial PRIMARY KEY,
option_name	varchar(30) NOT NULL default '',
value		varchar(255) NOT NULL default ''
);";

$sql[] = "CREATE TABLE " . $pdodb['prefix'] . "emails (
id		serial PRIMARY KEY,
name		varchar(30) NOT NULL default '',
subject		varchar(255) NOT NULL default '',
body		text NOT NULL
);";

$sql[] = "CREATE TABLE " . $pdodb['prefix'] . "logins (
id		serial PRIMARY KEY,
type		varchar(20) NOT NULL default '',
username	varchar(30) default NULL,
when_dtstamp	timestamp default NULL,
user_agent	varchar(200) NOT NULL default '',
ip		varchar(50) NOT NULL default ''
);";

$sql[] = "CREATE TABLE " . $pdodb['prefix'] . "users (
id		serial PRIMARY KEY,
firstname	varchar(60) NOT NULL default '',
lastname	varchar(60) NOT NULL default '',
email		varchar(80) NOT NULL UNIQUE,
phone		varchar(15) default NULL,
country		varchar(60) default NULL,
username	varchar(80) NOT NULL UNIQUE,
basic_passwd	varchar(128) NOT NULL default '',
digest_passwd	varchar(128) NOT NULL default '',
recover_passwd	varchar(128) default NULL, 
realm		varchar(128) default NULL,
status		integer NOT NULL default 1,
registered_dtstamp	timestamp NOT NULL,
disabled_dtstamp	timestamp default NULL,
email_verify_code	varchar(12) default NULL,
security_question	varchar(128) default NULL,
security_answer		varchar(128) default NULL,
party_ref	integer default NULL
);";

if ($currentstep == '3a') {
	$step_sql = " 
('htpasswd_path', '" . $_POST['htpasswd_path'] . "'),
('htpasswd_relative', '" . $htpasswd_relative . "'),
('conf_path', '')";
}
elseif ($currentstep == '3b') {
	$step_sql = " 
('htpasswd_path', ''),
('htpasswd_relative', ''),
('conf_path', '" . $_POST['conf_path'] . "');";
}

if ($_POST['auth_type'] == 'basic') {
	$digest_auth = 'false';
}
else {
	$digest_auth = 'true';
}

$sql[] = "INSERT INTO " . $pdodb['prefix'] . "config ( option_name, value) VALUES
('admin_pass', 'password'),
('admin_email', '" . $_POST['admin_email'] . "'),
('user_welcome_email', 'true'),
('admin_user_email', 'true'),
('require_admin_accept', 'false'),
('optional_fields_phone', 'true'),
('phone_digits', '12'),
('optional_fields_country', 'true'),
('mechanism', '" . $pdodb['mechanism'] . "'),
('realm', '" . $_POST['realm'] . "'),
('protected_area_url', '" . $_POST['protected_area_url'] . "'),
('regportal_url', '" . $_POST['regportal_url'] . "'),
('htaccess_path', '" . $_POST['htaccess_path'] . "'),
('min_passwd_length', '6'),
('max_passwd_length', '10'),
('passwd_pattern_1', '/^[a-zA-Z]+[0-9]+[a-zA-Z]*$/'),
('passwd_pattern_2', '/.*/'),
('passwd_rule', 'should start with letter (a-Z) and have at least 1 digit (0-9)'),
('max_userid_length', '64'),
('date_format', 'n/j/Y'),
('bulk_email_footer', ''),
('admin_session_expire', '3600'),
('debug_mode', 'false'),
('system_messages_email', 'webmaster@graphica.com.au'),
('verify_email', 'true'),
('user_session_expire', '3600'),
('email_user_accept', 'true'),
('prune_inactive_users', 'true'),
('admin_username', 'true'),
('digest_auth','" . $digest_auth . "'),
('err_401_doc', '')," . $step_sql ;

$sql[] = "INSERT INTO " . $pdodb['prefix'] . "emails ( name, subject, body) VALUES
('admin_NewUser', 'New User Notification', 'Hello Admin. You have a new user for <%LoginURL%>\r\n\r\nName: <%FirstName%> <%LastName%>\r\nEmail: <%Email%>\r\nUsername: <%Username%>\r\nPassword: <%Password%>'),
('admin_NewPendingUser', 'New User Pending Approval', 'Hello. Someone has registered to be a member of <%LoginURL%>. They either have not yet verified their email, or are waiting for your approval.\r\n\r\nName: <%FirstName%> <%LastName%>\r\nEmail: <%Email%>\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nLogin to the admin control panel to view more information or to accept/reject the account. <%RegPortalURL%>/admin/login.php'),
('user_WelcomeEmail', 'Welcome to the protected area!', 'Hello <%FirstName%>. You have been added to our database as a member.\r\n\r\nTo login, copy/paste the following URL into your web browser: <%LoginURL%>\r\n\r\nIf you have any problems logging in, please contact <%AdminEmail%>.\r\n\r\nCurrently, your account information is set to the following:\r\nName: <%FirstName%> <%LastName%>\r\nEmail: <%Email%>\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nBest Regards,\r\nAdministrator'),
('user_PendingApproval', 'Your Account is Pending Approval', 'Hello <%FirstName%>. Your account is now pending approval by the website administrator. You will be notified when your account is approved.\r\n\r\nYou submitted the following:\r\nName: <%FirstName%> <%LastName%>\r\nEmail: <%Email%>\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nThanks,\r\nAdmin'),
('user_AccountChanged', 'Your account has been modified', 'Hello <%FirstName%>. This is a courtesy email to let you know that your account has been modified. Your account information is as follows:\r\n\r\nName: <%FirstName%> <%LastName%>\r\nUsername: <%Username%>\r\nEmail: <%Email%>\r\nPassword: <%Password%>\r\n\r\nBest Regards,\r\nAdministrator'),
('user_AccountApproved', 'Your account has been approved!', 'Hello <%FirstName%>. Your request to be a member has been approved by the administrator! You may now login at the following URL: <%LoginURL%>\r\n\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nIf you have any trouble logging in, send an email to <%AdminEmail%>.\r\n\r\nBest Regards,\r\nAdministrator'),
('user_AccountDenied', 'Your account has been denied.', 'Hello <%FirstName%>. Unfortunately, your request for an account has been denied by the site administrator.\r\n\r\nIf you have any questions or concerns, send an email to <%AdminEmail%>.\r\n\r\nBest Regards,\r\nAdministrator'),
('user_EmailVerification', 'You must verify your email', 'Hello <%FirstName%>. Before you may become a member, you must verify that this is in fact your email address by clicking the below link. If you cannot click the below link, copy and paste it into your web browser.\r\n\r\n<%RegPortalURL%>/user/verifyemail.php?code=<%VerificationCode%>&username=<%Username%>\r\n\r\nBest Regards,\r\nAdministrator'),
('user_ForgotPassword', 'Your forgotten password', 'Hello <%FirstName%>. Someone has requested that we send you your account username and password.\r\n\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nBest Regards,\r\nAdministrator');";

$sql[] = "CREATE ROLE " . $pdodb['prefix'] . "user WITH NOLOGIN;";
$sql[] = "CREATE ROLE " . $pdodb['prefix'] . "admin WITH NOLOGIN;";
$sql[] = "CREATE ROLE " . $pdodb['prefix'] . "owner WITH NOLOGIN;";
$sql[] = "GRANT UPDATE ON TABLE " . $pdodb['prefix'] . "users TO " . $pdodb['prefix'] . "user;";
$sql[] = "GRANT SELECT ON TABLE " . $pdodb['prefix'] . "config TO " . $pdodb['prefix'] . "user;";
$sql[] = "GRANT SELECT ON TABLE " . $pdodb['prefix'] . "emails TO " . $pdodb['prefix'] . "user;";
$sql[] = "GRANT INSERT ON TABLE " . $pdodb['prefix'] . "logins TO " . $pdodb['prefix'] . "user;";
$sql[] = "GRANT DELETE ON TABLE " . $pdodb['prefix'] . "users TO " . $pdodb['prefix'] . "admin;";
$sql[] = "GRANT UPDATE ON TABLE " . $pdodb['prefix'] . "config TO " . $pdodb['prefix'] . "owner;";
$sql[] = "GRANT UPDATE ON TABLE " . $pdodb['prefix'] . "emails TO " . $pdodb['prefix'] . "owner;";
$sql[] = "GRANT DELETE ON TABLE " . $pdodb['prefix'] . "logins TO " . $pdodb['prefix'] . "owner;";
$sql[] = "GRANT " . $pdodb['prefix'] . "user TO " . $pdodb['prefix'] . "admin;";
$sql[] = "GRANT " . $pdodb['prefix'] . "admin TO " . $pdodb['prefix'] . "owner;";
?>
