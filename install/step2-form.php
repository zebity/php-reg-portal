<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>RegPortal Installation Step 2</title>
<link href="../images/admin.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="../images/hint.js">
/***********************************************
* Show Hint script- © Dynamic Drive (www.dynamicdrive.com)
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
    <td height="20" colspan="2" class="style2"><strong>Installation Step 
    2</strong></td>
  </tr>
  <tr>
    <td colspan="2" class="style2">Here you need to specify your RDBMS database information, so that we can create the db_config.php file containing your database settings. </td>
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
	  <td class="style5">Protection Via:</td>
	  <td><select name="mechanism" type="text" id="mechanism" value="htaccess"><option value="htaccess">htaccess</option><option value="mod_dbd">mod_dbd</option></select></td>
	</tr>
	<tr>
	  <td class="style5">Database:</td>
	  <td><select name="db_provider" type="text" id="db_provider" value="mysql"><option value="mysql">MySQL</option><option value="pgsql">PostgreSQL</option><option value="mssql">MS SQLServer</option></select></td>
	</tr>
        <tr>
          <td class="style5">SQL Host:</td>
          <td><input name="db_host" type="text" id="db_host" value="localhost" />
            <a href="#" class="hintanchor" onmouseover="showhint('Enter the hostname of the server that the database is hosted on. Usually this is localhost.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style5">Port:</td>
          <td><input name="db_port" type="text" id="db_port" value="3306" />
            <a href="#" class="hintanchor" onmouseover="showhint('Enter TCP/IP port the RDBMS is listening on. MySQL - 3306, PG - 5432, MS - ??', this, event, '150px')">[?]</a></td>
        <tr>
          <td width="48%" class="style5">Database Name:</td>
          <td width="52%"><input name="db_name" type="text" id="db_name" />
            <a href="#" class="hintanchor" onmouseover="showhint('Please enter the name of your MySQL database.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style5">Database Prefix:</td>
          <td><input name="db_prefix" type="text" id="db_prefix" value="reg_" />
		  <a href="#" class="hintanchor" onmouseover="showhint('Please enter the desired database prefix. This may be left blank for none.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style5">Database Username:</td>
          <td><input name="db_username" type="text" id="db_username" />
		  <a href="#" class="hintanchor" onmouseover="showhint('Please enter the MySQL user you would like to connect to the database as.', this, event, '150px')">[?]</a></td>
        </tr>
        <tr>
          <td class="style5">Database Password:</td>
          <td><input name="db_password" type="password" id="db_password" />
		  <a href="#" class="hintanchor" onmouseover="showhint('Enter the password for the username specified above.', this, event, '150px')">[?]</a></td>
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
