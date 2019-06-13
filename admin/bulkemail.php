<?php
//
// File: bulkemail.php
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

// include needed files
$db_config_path = $_GET['db_config_path'];
require($db_config_path);
require('../pdo/db-funcs.php');
require('../global.php');
require('../fckeditor/fckeditor.php');

$dbh = connect_db($pdodb, $err);
if (! isset($dbh)) {
        die("RegPortal was unable to connect to RDBMS: " . $err);
}

// assign config options from database to an array
$config = get_config($dbh, $pdodb['prefix']);

debug_mode($config['debug_mode']);

// remove users that have not verified their email after 72 hours if email verification is enabled
if($config['verify_email']=='true' && $config['prune_inactive_users']=='true'){
	PruneInactiveUsers($dbh, $pdodb['prefix']);
}

// start the session
admin_sessions($config['admin_session_expire'], "?db_config_path=" . $db_config_path);
if(!isset($_SESSION['logged_in']))
{
	redirect('./login.php?db_config_path=' . $db_config_path);
}

// Who should be selected by default?
if(isset($_GET['user']))
{
	if(check_user_exists($dbh,$_GET['user'],$pdodb['prefix']))
	{
		$selected_user = $_GET['user'];
	}
	else
	{
		$errors[] = 'The user specified in the query string does not exist. Please remove "user=x" from the URL of this page.';
	}
}
if(!isset($selected_user))
{
	$select_default = ' selected="selected"';
	$selected_user = null;
}
else
{
	$select_default = null;
}

if(isset($_GET['user']))
{
	$htmllocation = $_SERVER['PHP_SELF'] . "?db_config_path=" . $db_config_path . '&type=html&user='.$_GET['user'];
	$textlocation = $_SERVER['PHP_SELF'] . '?user='.$_GET['user'];
} 
else 
{
	$htmllocation = $_SERVER['PHP_SELF'] . "?db_config_path=" . $db_config_path . '&type=html';
	$textlocation = $_SERVER['PHP_SELF'];
}

if(isset($_POST['submit'])){
	if(empty($_POST['to']))
	{
		$errors[] = 'Somehow, you managed to not submit the form field which specifies who to send the form to.';
	}

	if(empty($errors))
	{

		// if the email is html, we need to adjust the headers of the email
		if($_POST['type']=='html'){
			$ishtml = true;
			$_POST['footer'] = '';
			$_POST['message'] = str_ireplace(
			array('&lt;%FirstName%&gt;','&lt;%LastName%&gt;','&lt;%Email%&gt;','&lt;%Username%&gt;','&lt;%RegistrationDate%&gt;','&lt;%RemovalURL%&gt;'),
			array('<%FirstName%>','<%LastName%>','<%Email%>','<%Username%>','<%RegistrationDate%>','<%RemovalURL%>'),$_POST['message']);
		} else {
			$ishtml = false;
		}

		if($_POST['to']=='//all//')
		{
			$sql = 'SELECT * FROM '.$pdodb['prefix'].'users WHERE status=2 ORDER BY username';
			if($result = $dbh->query($sql))
			{
				if($result->rowCount() > 0)
				{
					while ($row = $result->fetch(PDO::FETCH_ASSOC))
					{
						$body = $_POST['message'] . "\n\n" . $_POST['footer'];
						if ($pdodb['provider'] == 'pgsql')
							$date = $row['registered_dtstamp'];
						else
							$date = date($config['date_format'],$row['registered_dstamp']);
						$remove = $config['regportal_url'].'/anon/remove.php?db_config_path=' . $db_config_path . '&email='.$row['email'];
						$body = str_ireplace(array('<%FirstName%>','<%LastName%>','<%Email%>','<%Username%>','<%RegistrationDate%>','<%RemovalURL%>'), array($row['firstname'], $row['lastname'], $row['email'],$row['username'], $date, $remove), $body);
						if(!sendmail($row['email'],$config['admin_email'],$_POST['subject'],$body,$ishtml))
						{
							die('There was an error while sending the email to '.$row['email'].'. Please make sure the PHP mail function is configured on your server.');
						}
					}
				}
			}
			else
			{
				db_failure($dbh, $dbh->errorInfo(), $sql);
			}
		}
		else
		{
			$sql = 'SELECT * FROM ' . $pdodb['prefix'] . 'users WHERE id='.$_POST['to'];
			if($result = $dbh->query($sql))
			{
				if($result->rowCount() > 0)
				{
					while ($row = $result->fetch(PDO::FETCH_ASSOC))
					{
						$body = $_POST['message'] . "\n\n" . $_POST['footer'];
						if ($pdodb['provider'] == 'pgsql')
							$date = $row['registered_dtstamp'];
						else
							$date = date($config['date_format'],$row['registered_dstamp']);
						$remove = $config['regportal_url'].'/anon/remove.php?db_config_path=' . $db_config_path . '&email='.$row['email'];
						$body = str_ireplace(array('<%FirstName%>','<%LastName%>','<%Email%>','<%Username%>','<%RegistrationDate%>','<%RemovalURL%>'), array($row['firstname'], $row['lastname'], $row['email'],$row['username'], $date, $remove), $body);
						if(!sendmail($row['email'],$config['admin_email'],$_POST['subject'],$body,$ishtml))
						{
							die('There was an error while sending the email to '.$row['email'].'. Please make sure the PHP mail function is configured on your server.');
						}
					}
				} else {
					die('You selected a user that does not exist in the database.');
				}
			} else {
				db_failure($dbh, $dbh->errorInfo(), $sql);
			}
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal - Bulk Email</title>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
<table width="549" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="329" height="58"><a href="./index.php?db_config_path=<?php echo $db_config_path; ?>"><img src="../images/header_logo.gif" width="183" height="58" border="0" /></a></td>
    <td width="220"><div align="right"><img src="../images/rdbms.gif" width="119" height="56" /></div></td>
  </tr>
  <tr>
    <td height="2" colspan="2"><img src="../images/grey_pixel.gif" width="100%" height="2" /></td>
  </tr>
  <tr>
    <td height="20" colspan="2" class="style2"><strong><a href="./index.php?db_config_path=<?php echo $db_config_path ; ?>">Top</a>: Bulk Email </strong></td>
  </tr>
  <tr>
    <td height="28" colspan="2" class="style2">Your email was successfully sent to the requested user(s). Below is what was sent, except what the user receives will have valid codes replaced with values. </td>
  </tr>
  <tr>
    <td height="19" colspan="2"><br />
      <table width="91%" height="107" border="0" align="center">
      <tr>
        <td width="13%" height="20"><span class="style5">Subject:</span></td>
        <td width="87%"><span class="style2"><?php echo $_POST['subject'] ?></span></td>
      </tr>
      <tr>
        <td height="21" colspan="2"><span class="style5">Message:</span></td>
        </tr>
      <tr>
        <td colspan="2"><div class="style2"><?php echo nl2br(htmlentities($_POST['message']."\n\n".$_POST['footer'])) ?></div></td>
        </tr>
    </table>

        <br />
    <br /></td>
  </tr>
  
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center"><?php show_footer($software_signature); ?></div></td>
  </tr>
</table>
</body>
</html>
<?php
exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal - Email Users</title>
<script type="text/javascript" src="../images/hint.js">
/***********************************************
* Show Hint script- © Dynamic Drive (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit http://www.dynamicdrive.com/ for this script and 100s more.
***********************************************/
</script>
<script type="text/javascript">
<?php if((isset($_GET['type']) && $_GET['type']=='html') && isset($_GET['editor'])): ?>
function insertAtCursor(myField, myValue){
	// Get the editor instance that we want to interact with.
	var oEditor = FCKeditorAPI.GetInstance('message') ;

	// Check the active editing mode.
	if ( oEditor.EditMode == FCK_EDITMODE_WYSIWYG )
	{
		// Insert the desired HTML.
		oEditor.InsertHtml(myValue) ;
	}
	else
		alert( 'You must be on WYSIWYG mode!' ) ;
}
<?php else: ?>
function insertAtCursor(myField, myValue) {
	//IE support
	if (document.selection) {
		myField.focus();
		sel = document.selection.createRange();
		sel.text = myValue;
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		myField.value = myField.value.substring(0, startPos)
		+ myValue
		+ myField.value.substring(endPos, myField.value.length);
	} else {
		myField.value += myValue;
	}
	myField.focus();
}
<?php endif; ?>
function changeeditor(){
	if(document.mailer.enableeditor.checked == true){
		window.location = './bulkemail.php?db_config_path=<?php echo $db_config_path ; ?>&type=html&editor=1';
	} else {
		window.location = './bulkemail.php?db_config_path=<?php echo $db_config_path ; ?>&type=html';
	}
}
</script>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
<style type="text/css">
<!--
.style13 {font-size: 9px}
-->
</style>
</head>
<body>
<table width="549" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="329" height="58"><a href="./index.php?db_config_path=<?php echo $db_config_path ; ?>"><img src="../images/header_logo.gif" width="183" height="58" border="0" /></a></td>
    <td width="220"><div align="right"><img src="../images/rdbms.gif" width="119" height="56" /></div></td>
  </tr>
  <tr>
    <td height="2" colspan="2"><img src="../images/grey_pixel.gif" width="100%" height="2" /></td>
  </tr>
  <tr>
    <td height="20" colspan="2" class="style2"><strong><a href="./index.php?db_config_path=<?php echo $db_config_path ; ?>">Top</a>: Bulk Email </strong></td>
  </tr>
  <tr>
    <td height="28" colspan="2" class="style2">This form allows you to send an email to a specific member, or to all of the members in the database. Clicking the insert links will insert codes for pieces of data into the message. These codes will be replaced with values when the email is sent. If you would like to put these pieces of information into the footer, you may insert them manually. Be aware that if these are entered wrong, they will not be replaced. <br />
      <br />
      <strong>Codes:</strong>    &lt;%FirstName%&gt;, &lt;%LastName%&gt;, &lt;%Email%&gt;, &lt;%Username%&gt;, &lt;%RegistrationDate%&gt;, &lt;%RemovalURL%&gt; </td>
  </tr>
  <tr>
    <td height="275" colspan="2"><?php if (!empty($errors)){ ?><table width="95%" height="24" border="0" align="center">
      <tr>
        <td height="20">
		<div class="style9"><ul>
		<?php
		foreach($errors as $error){
			print '<li>'.$error.'</li>';
		}
		?>
		</ul></div></td>
      </tr>
    </table>
      <?php } else { print '<br />'; } ?>
	  <form action="<?php echo $_SERVER['PHP_SELF'] . "?db_config_path=" . $db_config_path ; ?>" method="post" name="mailer">
      <table width="100%" border="0" align="center">
      <tr>
        <td class="style5">Send To<a href="#" class="hintanchor" onmouseover="showhint('Please specify who you would like this email to be sent to. You may either select &quot;All Users&quot; or a specific user.', this, event, '150px')">[?]</a>:</td>
        <td><select name="to">
        <option value="//all//"<?php echo $select_default; ?>>All Users</option>
        <?php echo generate_user_menu($dbh,$pdodb['prefix'],$selected_user) ; ?>
        </select></td>
      </tr>
      <tr>
        <td width="45%" class="style5">Email Subject<a href="#" class="hintanchor" onmouseover="showhint('Please enter the subject of the email you wish to send.', this, event, '150px')">[?]</a>:</td>
        <td width="55%"><input name="subject" type="text" id="subject"<?php if(isset($_POST['subject'])) echo ' value="'.$_POST['subject'].'"'; ?> /></td>
      </tr>
      <tr>
        <td height="22" class="style5">Email Type<a href="#" class="hintanchor" onmouseover="showhint('If you want your email to be in HTML, select HTML, otherwise select Plain Text. Changing this will clear the whole form.', this, event, '150px')">[?]</a>:</td>
        <td height="22" class="style2">
		<label><input name="type" type="radio" onclick="window.location='<?php echo $textlocation; ?>'" value="text"<?php if(!isset($_GET['type']) || $_GET['type']!='html') print ' checked="checked"'; ?> />Plain Text</label>
        <label><input name="type" type="radio" onclick="window.location='<?php echo $htmllocation; ?>'" value="html"<?php if(isset($_GET['type']) && $_GET['type']=='html') echo ' checked="checked"'; ?> />HTML</label>
		</td>
      </tr>
      <?php  if(isset($_GET['type']) && $_GET['type']=='html'): ?>
      <tr>
        <td height="22" class="style5">Enable HTML Editor:<a href="#" class="hintanchor" onmouseover="showhint('Select this box if you wish to enable the WYSIWYG editor. Changing the value here will reset the form.', this, event, '150px')">[?]</a>:</td>
        <td height="22" class="style2">
		<input type="checkbox" id="enableeditor" name="enableeditor" value="1" onchange="changeeditor()"<?php if(isset($_GET['editor'])) print ' checked="checked"'; ?> />
		</td>
      </tr>
      <?php endif; ?>
      <tr>
        <td height="22" colspan="2" class="style5">Email Message<a href="#" class="hintanchor" onmouseover="showhint('Please enter your message here. This will appear in the body of the email.', this, event, '150px')">[?]</a>: </td>
        </tr>
      <?php //if((!isset($_GET['type']) || $_GET['type']!='html') && (!isset($_GET['editor']))): ?><tr>
        <td height="19" colspan="2" class="style2 style13"><div align="center"><strong>Insert:</strong> <a href="javascript:insertAtCursor(document.mailer.message, '<?php echo '<%FirstName%>'; ?>')">First Name</a> | <a href="javascript:insertAtCursor(document.mailer.message, '<?php '<%LastName%>'; ?>')">Last Name</a> | <a href="javascript:insertAtCursor(document.mailer.message, '<?php '<%Email%>'; ?>')">Email</a> | <a href="javascript:insertAtCursor(document.mailer.message, '<?php echo '<%Username%>'; ?>')">Username</a> | <a href="javascript:insertAtCursor(document.mailer.message, '<?php echo '<%RegistrationDate%>'; ?>')">Date Registered</a> | 
            <a href="javascript:insertAtCursor(document.mailer.message, '<?php echo '<%RemovalURL%>'; ?>')">Removal URL</a></div></td>
      </tr><?php //endif; ?>
      <tr>
        <td colspan="2" class="style2">
<?php 
if((isset($_GET['type']) && $_GET['type']=='html') && isset($_GET['editor'])):
$oFCKeditor = new FCKeditor('message') ;
$oFCKeditor->BasePath = '../fckeditor/';
$oFCKeditor->ToolbarSet = 'Basic';
$oFCKeditor->Width  = '100%' ;
$oFCKeditor->Height = '400' ;
$oFCKeditor->Create() ;
else:
?> 
        <textarea name="message" cols="60" rows="10" id="message"><?php if(isset($_POST['message'])) print $_POST['message']; ?></textarea>
<?php endif; ?>
        </td>
        </tr>
      <?php if(!isset($_GET['type'])): ?>
	  <tr>
        <td height="20" colspan="2" class="style5">Footer<a href="#" class="hintanchor" onmouseover="showhint('Whatever is entered in this field will be directly attached to your message.', this, event, '150px')">[?]</a>:</td>
        </tr>
      
      <tr>
        <td colspan="2"><span class="style2">
          <textarea name="footer" cols="60" rows="5" id="footer"><?php echo $config['bulk_email_footer']; ?></textarea>
        </span></td>
        </tr>
      <tr>
      <?php endif; ?>
        <td>&nbsp;</td>
        <td><div align="right">
          <input type="hidden" name="submit" value="1" />
          <input type="submit" value="Submit" />
            <input type="button" value="Reset" onclick="window.location='<?php echo $_SERVER['PHP_SELF'] . "?db_config_path=" . $db_config_path ; ?>'" />
        </div></td>
      </tr>
    </table>
	<br />
	</form>    </td>
  </tr>
  
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center"><?php show_footer($software_signature); ?></div></td>
  </tr>
</table>
</body>
</html>
