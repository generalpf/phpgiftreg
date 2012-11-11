<?php
// This program is free software; you can redistribute it and/or modify
// This program is free software; you can redistribute it and/or modify
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

session_start();
if (!isset($_SESSION["userid"])) {
	header("Location: " . getFullPath("login.php"));
	exit;
}
else {
	$userid = $_SESSION["userid"];
}

$action = "";
if (!empty($_POST["action"])) {
	$action = $_POST["action"];
	
	if ($action == "changepwd") {
		$newpwd = $_POST["newpwd"];
		if (!get_magic_quotes_gpc())
			$newpwd = addslashes($newpwd);

		$query = "UPDATE {$OPT["table_prefix"]}users SET password = {$OPT["password_hasher"]}('$newpwd') WHERE userid = $userid";
		mysql_query($query) or die("Could run query: " . mysql_error());
		header("Location: " . getFullPath("index.php?message=Password+changed."));
		exit;
	}
	else if ($action == "save") {
		$fullname = $_POST["fullname"];
		$email = $_POST["email"];
		$comment = $_POST["comment"];
		$email_msgs = ($_POST["email_msgs"] == "on" ? 1 : 0);
		if (!get_magic_quotes_gpc()) {
			$fullname = addslashes($fullname);
			$email = addslashes($email);
			$comment = addslashes($comment);
		}

		$query = "UPDATE {$OPT["table_prefix"]}users SET fullname = '$fullname', email = '$email', email_msgs = $email_msgs, comment = " . ($comment == "" ? "NULL" : "'$comment'") . " WHERE userid = $userid";
		mysql_query($query) or die("Couldn't run query: " . mysql_error());
		$_SESSION["fullname"] = stripslashes($fullname);

		header("Location: " . getFullPath("index.php?message=Profile+updated."));
		exit;
	}
	else {
		echo "Unknown verb.";
		exit;
	}
}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Update Profile</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
<script language="JavaScript" type="text/javascript">
	function confirmPassword() {
		var theForm = document.forms["changepwd"];
		if (theForm.newpwd.value != theForm.confpwd.value) {
			alert("Passwords don't match.");
			return false;
		}
		return true;
	}
	function validateProfile() {
		var theForm = document.forms["profile"];
		if (!theForm.fullname.value.match("\\S")) {
			alert("A full name is required.");
			theForm.fullname.focus();
			return false;
		}
		if (!theForm.email.value.match("\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*")) {
			alert("The e-mail address '" + theForm.email.value + "' is not a valid address.");
			theForm.email.focus();
			return false;
		}
		return true;
	}
</script>
</head>
<body>
<form name="changepwd" action="profile.php" method="POST" onSubmit="return confirmPassword();">
	<input type="hidden" name="action" value="changepwd">
	<p>
		<table class="partbox" cellpadding="3">
			<tr class="partboxtitle">
				<td colspan="2" align="center">Change Password</td>
			</tr>
			<tr>
				<td>New Password</td>
				<td><input type="password" name="newpwd"></td>
			</tr>
			<tr>
				<td>Confirm Password</td>
				<td><input type="password" name="confpwd"></td>
			</tr>
			<tr>
				<td colspan="2" align="center"><input type="submit" value="Change Password"></td>
			</tr>
		</table>
	</p>
</form>
<?php
$query = "SELECT fullname, email, email_msgs, comment FROM {$OPT["table_prefix"]}users WHERE userid = " . $userid;
$rs = mysql_query($query) or die("You don't exist: " . mysql_error());
$row = mysql_fetch_array($rs,MYSQL_ASSOC);
?>
<form name="profile" action="profile.php" method="POST" onSubmit="return validateProfile();">
	<input type="hidden" name="action" value="save">
	<p>
		<table class="partbox" cellpadding="3">
			<tr class="partboxtitle">
				<td colspan="2" align="center">Update Profile</td>
			</tr>
			<tr>
				<td>Full Name</td>
				<td><input type="text" name="fullname" value="<?php echo htmlspecialchars($row["fullname"]); ?>"></td>
			</tr>
			<tr>
				<td>E-mail Address</td>
				<td><input type="text" name="email" value="<?php echo htmlspecialchars($row["email"]); ?>"></td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="email_msgs" <?php if ($row["email_msgs"] == 1) echo "CHECKED"; ?>>E-mail me a copy of every message
				</td>
			</tr>
			<tr>
				<td colspan="2">
					Comments / shipping address / etc. (optional)<br />
					<textarea name="comment" rows="5" cols="40"><?php echo htmlspecialchars($row["comment"]); ?></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center"><input type="submit" value="Update Profile"></td>
			</tr>
		</table>
	</p>
</form>
<?php
mysql_free_result($rs);
?>
<p>
<a href="index.php">Back to main</a>
</p>
</body>
</html>
