<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal Installation Step 3a</title>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="../images/hint.js">
/***********************************************
* Show Hint script- � Dynamic Drive (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit http://www.dynamicdrive.com/ for this script and 100s more.
***********************************************/
</script>
</head>

<body>
<table width="549" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="329" height="58"><a href="./index.php"><img src="../images/header_logo.gif" width="183" height="58" border="0" /></a></td>
    <td width="220"><div align="right"><img src="../images/rdbms.gif" width="119" height="56" /></div></td>
  </tr>
  <tr>
    <td height="2" colspan="2"><img src="../images/grey_pixel.gif" width="100%" height="2" /></td>
  </tr>
  <tr>
    <td height="20" colspan="2" class="style2"><strong>Installation Step 3a</strong></td>
  </tr>
  <tr>
    <td colspan="2" class="style2">Here we need to specify settings required in order for RegPortal to function properly. Clicking Submit will create the database tables, provided there are no errors.</td>
  </tr>
  <tr>
    <td colspan="2"><?php if (!empty($errors)){ ?>
      <table width="95%" height="24" border="0" align="center">
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
      <br />
    <br />
    <form id="form1" name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']?>?<?php echo $_SERVER['QUERY_STRING']?>">
      <table width="70%" border="0" align="center">
        <tr>
          <td class="style5">Authentication Realm:</td>
          <td><input name="realm" type="text" id="realm" value="<?php if(isset($_POST['realm'])) print $_POST['realm']; else print "Protected Area"; ?>" />
            <a href="#" class="hintanchor" onmouseover="showhint('Enter Authentication Realm.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style5">Authentication Type:</td>
          <td><select name="auth_type" type="text" id="auth_type" value="basic"><option value="basic">Basic</option><option value="digest">Digest</option></select></td>
	 </tr>
	<tr>
          <td class="style5">Path to Htpasswd:</td>
          <td><input name="htpasswd_path" type="text" id="htpasswd_path" value="<?php if(isset($_POST['htpasswd_path'])) print $_POST['htpasswd_path']; else print "/protected/.htpasswd"; ?>" />
            <a href="#" class="hintanchor" onmouseover="showhint('Enter the path to your htpasswd file. This must be relative to the document root. For example, if the URL to your protected area is http://www.example.com/protected/, in this field you would most likely enter &quot;/protected/.htpasswd&quot;.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style5">&nbsp;</td>
          <td class="style2">Relative?
            <input name="htpasswd_relative" type="checkbox" value="true"<?php if (! isset($htpasswd_relative)) $htpasswd_relative=true;  if($htpasswd_relative){ print ' checked="checked"'; } elseif(!isset($_POST['submit'])) { print ' checked="checked"'; } ?> />
            <a href="#" class="hintanchor" onmouseover="showhint('If this box is checked, the path entered above must be relative to your document root. If this box is not checked, the path above must be a full path.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td width="48%" class="style5">Protected Directory:</td>
          <td width="52%"><input name="htaccess_path" type="text" id="htaccess_path" value="<?php if(isset($_POST['htaccess_path'])) print $_POST['htaccess_path']; else print "/protected/.htaccess"; ?>" />
            <a href="#" class="hintanchor" onmouseover="showhint('Enter the path to your htaccess file. This MUST be relative to the document root and MUST be within your protected area. For example, if the URL to your protected area is http://www.example.com/protected/, in this field you would most likely enter &quot;/protected/.haccess&quot;. This file MUST be in your protected area directory for RegPortal to function properly!', this, event, '200px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style5">&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
        <tr>
          <td class="style5">Protected URL:</td>
          <td><input name="protected_area_url" type="text" id="protected_area_url" value="<?php if(isset($_POST['protected_area_url'])) print $_POST['protected_area_url']; else print "http://".$_SERVER['HTTP_HOST']."/protected/"; ?>" />
		  <a href="#" class="hintanchor" onmouseover="showhint('Enter the URL to your protected area.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style5">RegPortal URL:</td>
          <td><input name="regportal_url" type="text" id="regportal_url" value ="<?php if(isset($_POST['regportal_url'])) print $_POST['regportal_url']; else print "http://".$_SERVER['HTTP_HOST'].str_replace('/install','',dirname($_SERVER['REQUEST_URI'])); ?>" />
		  <a href="#" class="hintanchor" onmouseover="showhint('This is the URL to the root directory of RegPortal. On most installations this will be http://www.yoursite.com/regportal. Do NOT include a trailing forwardslash!', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style5">Admin Email: </td>
          <td><input name="admin_email" type="text" id="admin_email" value="<?php if(isset($_POST['admin_email'])) print $_POST['admin_email']; else print "admin@example.com"; ?>" />
		  <a href="#" class="hintanchor" onmouseover="showhint('Enter your email address.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><input type="submit" value="Submit" />
            <input name="submit" type="hidden" id="submit" value="1" /></td>
        </tr>
      </table>
    </form>
          <br /></td>
  </tr>
  
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center">Powered By <a href="http://code.google.com/p/php-reg-portal/">PHP RegPortal</a></div></td>
  </tr>
</table>
</body>
</html>
