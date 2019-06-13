<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>PHP RegPortal Installation Complete</title>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
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
    <td height="20" colspan="2" class="style2"><strong>Install Complete </strong></td>
  </tr>
  <tr>
    <td colspan="2" class="style2">Installation appears to have succeeded. Before proceeding, please do the following things:<br />1) CHMOD <?php print($db_config_path) ?>  back to 644<br />2) Move the generated &lt;Directory&gt; entries into correct location and update httpd.conf if needed<br />The admin password is currently set to &quot;password&quot;. This should be changed as soon as possible!<br />
    <br />
    <a href="../admin/index.php?db_config_path=<?php print($db_config_path) ?>">Click here</a> to login to the admin panel. </td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  
  <tr>
    <td height="21" colspan="2" class="footercell"><div align="center">Powered By <a href="http://code.google.com/p/php-reg-portal/">PHP RegPortal</a></div></td>
  </tr>
</table>
</body>
</html>
