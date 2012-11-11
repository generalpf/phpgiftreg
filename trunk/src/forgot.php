<?php
/// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

include("config.php");
include("db.php");
include("funcLib.php");

$error = "";

if (isset($_POST["action"])) {
	if ($_POST["action"] == "forgot") {
		$username = $_POST["username"];
		if (!get_magic_quotes_gpc()) {
			$username = addslashes($username);
		}
		
		// make sure that username is valid 
		$query = "SELECT email FROM {$OPT["table_prefix"]}users WHERE username = '$username'";
		$rs = mysql_query($query) or die("Could not query: " . mysql_error());
		if (mysql_num_rows($rs) == 0) {
			$error = "The username '" . stripslashes($username) . "' could not be found.";
			mysql_free_result($rs);
		}
		else {
			$row = mysql_fetch_array($rs,MYSQL_ASSOC);
			$email = $row["email"];
			mysql_free_result($rs);
			
			if ($email == "")
				$error = "The username '" . stripslashes($username) . "' does not have an e-mail address, so the password could not be sent.";
			else {
				$pwd = generatePassword();
				$query = "UPDATE {$OPT["table_prefix"]}users SET password = {$OPT["password_hasher"]}('$pwd') WHERE username = '$username'";
				mysql_query($query) or die("Could not query: " . mysql_error());
				mail(
					$email,
					"Gift Registry password reset",
					"Your Gift Registry account information:\r\n" . 
						"Your username is '" . $username . "' and your new password is '$pwd'.",
					"From: {$GLOBALS["OPT"]["email_from"]}\r\nReply-To: {$OPT["email_reply_to"]}\r\nX-Mailer: {$GLOBALS["OPT"]["email_xmailer"]}\r\n"
				) or die("Mail not accepted for $email");	
			}
		}
	}
}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Forgot Password</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
<script language="JavaScript" type="text/javascript">
	function validate() {
		field = document.forgot.username;
		if (field == null || field == undefined || !field.value.match("\\S")) {
			alert("You must supply a username.");
			field.focus();
			return false;
		}
		
		return true;
	}
</script>
</head>
<body>
<?php
if (isset($_POST["action"]) && $_POST["action"] == "forgot" && $error == "") {
	// success!
	?>
	<p>
	Shortly, you will receive an e-mail with your new password.</p>
	<p>Once you've received your password, click <a href="login.php">here</a> to login.</p>
	<?php
} else {
	?>
	<form name="forgot" method="post" action="forgot.php">	
		<input type="hidden" name="action" value="forgot">
		<div align="center">
			<table cellpadding="3" class="partbox" width="50%">
				<tr>
					<td colspan="2" class="partboxtitle" align="center">Retrieve Your Password</td>
				</tr>
				<tr>
					<td colspan="2">
						<p>
							Supply your username and click Submit.  
							Your password will be reset and the new password will be sent to the e-mail address you have associated with your account.
						</p>
						<?php
						if ($error != "")
							echo "<div class=\"message\">" . $error . "</div>";
						?>
					</td>
				</tr>
				<tr>
					<td width="25%">Username</td>
					<td>
						<input name="username" size="20" maxlength="20" type="text" value="<?php echo htmlspecialchars(stripslashes($_POST["username"])); ?>"/>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="submit" value="Submit" onClick="return validate();" />
					</td>
				</tr>
			</table>
		</div>
	</form>
	<?php
}
?>
</body>
</html>
