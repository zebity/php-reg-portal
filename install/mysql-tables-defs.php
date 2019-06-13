<?php
$sql[] = 'CREATE TABLE `'.$pdodb['prefix'].'config` (
`id` int(10) NOT NULL auto_increment,
`option_name` varchar(30) NOT NULL default \'\',
`value` varchar(255) NOT NULL default \'\',
PRIMARY KEY  (`id`)
);';

$sql[] = 'CREATE TABLE `'.$pdodb['prefix'].'emails` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(30) NOT NULL default \'\',
  `subject` varchar(255) NOT NULL default \'\',
  `body` text NOT NULL,
  PRIMARY KEY  (`id`)
);';

$sql[] = 'CREATE TABLE `'.$pdodb['prefix'].'logins` (
  `id` int(10) NOT NULL auto_increment,
  `type` varchar(20) NOT NULL default \'\',
  `username` varchar(30) default NULL,
  `timestamp` int(20) NOT NULL default \'0\',
  `user_agent` varchar(200) NOT NULL default \'\',
  `ip` varchar(50) NOT NULL default \'\',
  PRIMARY KEY  (`id`)
);';

$sql[] = 'CREATE TABLE `'.$pdodb['prefix'].'users` (
  `id` int(10) NOT NULL auto_increment,
  `firstname` varchar(30) NOT NULL default \'\',
  `lastname` varchar(30) NOT NULL default \'\',
  `email` varchar(50) NOT NULL default \'\',
  `phone` varchar(15) default NULL,
  `country` varchar(30) default NULL,
  `username` varchar(30) NOT NULL default \'\',
  `password` varchar(30) NOT NULL default \'\',
  `status` int(1) NOT NULL default \'1\',
  `registration_timestamp` int(20) NOT NULL default \'0\',
  `email_verify_code` varchar(12) default NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
);';

$sql[] = 'INSERT INTO `'.$pdodb['prefix'].'config` (`id`, `option_name`, `value`) VALUES (1, \'admin_pass\', \'password\'),
(2, \'admin_email\', \''.$_POST['admin_email'].'\'),
(3, \'user_welcome_email\', \'true\'),
(4, \'admin_user_email\', \'true\'),
(5, \'require_admin_accept\', \'false\'),
(6, \'optional_fields_phone\', \'true\'),
(7, \'phone_digits\', \'10\'),
(8, \'optional_fields_country\', \'true\'),
(9, \'protected_area_url\', \''.$_POST['protected_area_url'].'\'),
(10, \'regportal_url\', \''.$_POST['regportal_url'].'\'),
(11, \'date_format\', \'n/j/Y\'),
(12, \'bulk_email_footer\', \'\'),
(13, \'admin_session_expire\', \'3600\'),
(14, \'debug_mode\', \'false\'),
(15, \'system_messages_email\', \'system@example.com\'),
(16, \'verify_email\', \'true\'),
(17, \'user_session_expire\', \'3600\'),
(18, \'email_user_accept\', \'true\'),
(19, \'htpasswd_path\', \''.$_POST['htpasswd_path'].'\'),
(20, \'htaccess_path\', \''.$_POST['htaccess_path'].'\'),
(21, \'realm\', \''.$_POST['realm'].'\'),
(22, \'prune_inactive_users\', \'true\'),
(23, \'admin_username\', \'true\'),
(24, \'digest_auth\', \'false\'),
(25, \'err_401_doc\', \'\'),
(26, \'htpasswd_relative\', \''.$htpasswd_relative.'\');';

$sql[] = 'INSERT INTO `'.$pdodb['prefix'].'emails` (`id`, `name`, `subject`, `body`) VALUES (1, \'admin_NewUser\', \'New User Notification\', \'Hello Admin. You have a new user for <%LoginURL%>\r\n\r\nName: <%FirstName%> <%LastName%>\r\nEmail: <%Email%>\r\nUsername: <%Username%>\r\nPassword: <%Password%>\'),
(2, \'admin_NewPendingUser\', \'New User Pending Approval\', \'Hello. Someone has registered to be a member of <%LoginURL%>. They either have not yet verified their email, or are waiting for your approval.\r\n\r\nName: <%FirstName%> <%LastName%>\r\nEmail: <%Email%>\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nLogin to the admin control panel to view more information or to accept/reject the account. <%DeadlockURL%>/admin/login.php\'),
(3, \'user_WelcomeEmail\', \'Welcome to the protected area!\', \'Hello <%FirstName%>. You have been added to our database as a member.\r\n\r\nTo login, copy/paste the following URL into your web browser: <%LoginURL%>\r\n\r\nIf you have any problems logging in, please contact <%AdminEmail%>.\r\n\r\nCurrently, your account information is set to the following:\r\nName: <%FirstName%> <%LastName%>\r\nEmail: <%Email%>\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nBest Regards,\r\nAdministrator\'),
(4, \'user_PendingApproval\', \'Your Account is Pending Approval\', \'Hello <%FirstName%>. Your account is now pending approval by the website administrator. You will be notified when your account is approved.\r\n\r\nYou submitted the following:\r\nName: <%FirstName%> <%LastName%>\r\nEmail: <%Email%>\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nThanks,\r\nAdmin\'),
(5, \'user_AccountChanged\', \'Your account has been modified\', \'Hello <%FirstName%>. This is a courtesy email to let you know that your account has been modified. Your account information is as follows:\r\n\r\nName: <%FirstName%> <%LastName%>\r\nUsername: <%Username%>\r\nEmail: <%Email%>\r\nPassword: <%Password%>\r\n\r\nBest Regards,\r\nAdministrator\'),
(6, \'user_AccountApproved\', \'Your account has been approved!\', \'Hello <%FirstName%>. Your request to be a member has been approved by the administrator! You may now login at the following URL: <%LoginURL%>\r\n\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nIf you have any trouble logging in, send an email to <%AdminEmail%>.\r\n\r\nBest Regards,\r\nAdministrator\'),
(7, \'user_AccountDenied\', \'Your account has been denied.\', \'Hello <%FirstName%>. Unfortunately, your request for an account has been denied by the site administrator.\r\n\r\nIf you have any questions or concerns, send an email to <%AdminEmail%>.\r\n\r\nBest Regards,\r\nAdministrator\'),
(8, \'user_EmailVerification\', \'You must verify your email\', \'Hello <%FirstName%>. Before you may become a member, you must verify that this is in fact your email address by clicking the below link. If you cannot click the below link, copy and paste it into your web browser.\r\n\r\n<%DeadlockURL%>/user/verifyemail.php?code=<%VerificationCode%>&username=<%Username%>\r\n\r\nBest Regards,\r\nAdministrator\'),
(9, \'user_ForgotPassword\', \'Your forgotten password\', \'Hello <%FirstName%>. Someone has requested that we send you your account username and password.\r\n\r\nUsername: <%Username%>\r\nPassword: <%Password%>\r\n\r\nBest Regards,\r\nAdministrator\');';
?>
