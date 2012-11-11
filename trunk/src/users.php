<?php
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

if ($_SESSION["admin"] != 1) {
	echo "You don't have admin privileges.";
	exit;
}

if (!empty($_GET["message"])) {
    $message = strip_tags($_GET["message"]);
}

if (isset($_GET["action"]))
	$action = $_GET["action"];
else
	$action = "";

if ($action == "insert" || $action == "update") {
	/* validate the data. */
	$username = trim($_GET["username"]);
	$fullname = trim($_GET["fullname"]);
	$email = trim($_GET["email"]);
	$email_msgs = (strtoupper($_GET["email_msgs"]) == "ON" ? 1 : 0);
	$approved = (strtoupper($_GET["approved"]) == "ON" ? 1 : 0);
	$userisadmin = (strtoupper($_GET["admin"]) == "ON" ? 1 : 0);
	if (!get_magic_quotes_gpc()) {
		$username = addslashes($username);
		$fullname = addslashes($fullname);
		$email = addslashes($email);
	}
		
	$haserror = false;
	if ($username == "") {
		$haserror = true;
		$username_error = "A username is required.";
	}
	if ($fullname == "") {
		$haserror = true;
		$fullname_error = "A full name is required.";
	}
	if ($email == "") {
		$haserror = true;
		$email_error = "An e-mail address is required.";
	}

}

if ($action == "delete") {
	// MySQL is too l4m3 to have cascade deletes, so we'll have to do the
	// work ourselves.
	$deluserid = (int) $_GET["userid"];
	
	mysql_query("DELETE FROM {$OPT["table_prefix"]}shoppers WHERE shopper = $deluserid OR mayshopfor = $deluserid") or die("Could not query: " . mysql_error());
	// we can't leave messages with dangling senders, so delete those too.
	mysql_query("DELETE FROM {$OPT["table_prefix"]}messages WHERE sender = $deluserid OR recipient = $deluserid") or die("Could not query: " . mysql_error());
	mysql_query("DELETE FROM {$OPT["table_prefix"]}events WHERE userid = $deluserid") or die("Could not query: " . mysql_error());
	mysql_query("DELETE FROM {$OPT["table_prefix"]}items WHERE userid = $deluserid") or die("Could not query: " . mysql_error());
	mysql_query("DELETE FROM {$OPT["table_prefix"]}users WHERE userid = $deluserid") or die("Could not query: " . mysql_error()); 
	header("Location: " . getFullPath("users.php?message=User+deleted."));
	exit;
}
else if ($action == "edit") {
	$query = "SELECT username, fullname, email, email_msgs, approved, admin FROM {$OPT["table_prefix"]}users WHERE userid = " . (int) $_GET["userid"];
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$username = $row["username"];
		$fullname = $row["fullname"];
		$email = $row["email"];
		$email_msgs = $row["email_msgs"];
		$approved = $row["approved"];
		$userisadmin = $row["admin"];
	}
	mysql_free_result($rs);
}
else if ($action == "") {
	$username = "";
	$fullname = "";
	$email = "";
	$email_msgs = 1;
	$approved = 1;
	$userisadmin = 0;
}
else if ($action == "insert") {
	if (!$haserror) {
		// generate a password and insert the row.
		$pwd = generatePassword();
		$query = "INSERT INTO {$OPT["table_prefix"]}users(username,password,fullname,email,email_msgs,approved,admin) " .
					"VALUES('$username',{$OPT["password_hasher"]}('$pwd'),'$fullname'," . ($email == "" ? "NULL" : "'$email'") . ",$email_msgs,$approved,$userisadmin)";
		mysql_query($query) or die("Could not query: " . mysql_error());
		mail(
			$email,
			"Gift Registry account created",
			"Your Gift Registry account was created.\r\n" . 
				"Your username is $username and your password is $pwd.",
			"From: {$OPT["email_from"]}\r\nReply-To: {$OPT["email_reply_to"]}\r\nX-Mailer: {$OPT["email_xmailer"]}\r\n"
		) or die("Mail not accepted for $email");	
		header("Location: " . getFullPath("users.php?message=User+added+and+e-mail+sent."));
		exit;
	}
}
else if ($action == "update") {
	if (!$haserror) {
		$query = "UPDATE {$OPT["table_prefix"]}users SET " .
				"username = '$username', " .
				"fullname = '$fullname', " .
				"email = " . ($email == "" ? "NULL" : "'$email'") . ", " .
				"email_msgs = $email_msgs, " .
				"approved = $approved, " . 
				"admin = $userisadmin " . 
				"WHERE userid = " . $_GET["userid"];
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("users.php?message=User+updated."));
		exit;		
	}
}
else if ($action == "reset") {
	$resetuserid = $_GET["userid"];
	$resetemail = $_GET["email"];
	if (!get_magic_quotes_gpc()) {
		$resetuserid = addslashes($resetuserid);
		$resetemail = addslashes($resetemail);
	}
	// generate a password and insert the row.
	$pwd = generatePassword();
	$query = "UPDATE {$OPT["table_prefix"]}users SET password = {$OPT["password_hasher"]}('$pwd') WHERE userid = $resetuserid";
	mysql_query($query) or die("Could not query: " . mysql_error());
	mail(
		$resetemail,
		"Gift Registry password reset",
		"Your Gift Registry password was reset to $pwd.",
		"From: {$OPT["email_from"]}\r\nReply-To: {$OPT["email_reply_to"]}\r\nX-Mailer: {$OPT["email_xmailer"]}\r\n"
	) or die("Mail not accepted for $email");
	header("Location: " . getFullPath("users.php?message=Password+reset."));
	exit;
}
else {
	echo "Unknown verb.";
	exit;
}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Manage Users</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
<script language="JavaScript" type="text/javascript">
	function confirmDelete(fullname) {
		return confirm("Are you sure you want to delete " + fullname + "?");
	}
</script>
</head>
<body>
<?php
if (isset($message)) {
    echo "<span class=\"message\">" . $message . "</span>";
}
$query = "SELECT userid, username, fullname, email, email_msgs, approved, admin FROM {$OPT["table_prefix"]}users ORDER BY username";
$users = mysql_query($query) or die("Could not query: " . mysql_error());
?>
<p>
	<table class="partbox" width="100%" cellspacing="0">
		<tr class="partboxtitle">
			<td colspan="7" align="center">Users</td>
		</tr>
		<tr>
			<th class="colheader">Username</th>
			<th class="colheader">Fullname</th>
			<th class="colheader">E-mail</th>
			<th class="colheader">E-mail messages?</th>
			<th class="colheader">Approved?</th>
			<th class="colheader">Admin?</th>
			<th>&nbsp;</th>
		</tr>
		<?php
		$i = 0;
		while ($row = mysql_fetch_array($users,MYSQL_ASSOC)) {
			?>
			<tr class="<?php echo (!($i++ % 2)) ? "evenrow" : "oddrow" ?>">
				<td><?php echo htmlspecialchars($row["username"]); ?></td>
				<td><?php echo htmlspecialchars($row["fullname"]); ?></td>
				<td><?php echo htmlspecialchars($row["email"]); ?></td>
				<td><?php echo ($row["email_msgs"] == 1 ? "Yes" : "No"); ?></td>
				<td><?php echo ($row["approved"] == 1 ? "Yes" : "No"); ?></td>
				<td><?php echo ($row["admin"] == 1 ? "Yes" : "No"); ?></td>
				<td align="right">
					<a href="users.php?action=edit&userid=<?php echo $row["userid"]; ?>">Edit</a>
					/
					<a onClick="return confirmDelete('<?php echo jsEscape($row["fullname"]); ?>');" href="users.php?action=delete&userid=<?php echo $row["userid"]; ?>">Delete</a>
					/
					<?php
					// we can't reset their password if their e-mail address isn't set.
					if ($row["email"] != "") {
						?>
						<a href="users.php?action=reset&userid=<?php echo $row["userid"]; ?>&email=<?php echo urlencode($row["email"]); ?>">Reset Pwd</a>
						<?php
					}
					else
						echo "Reset Pwd";
					?>
				</td>
			</tr>
			<?php
		}
		mysql_free_result($users);
		?>
	</table>
</p>
<p>
	<a href="users.php">Add a new user</a> / <a href="index.php">Back to main</a>
</p>
<form name="users" method="get" action="users.php">	
	<?php 
	if ($action == "edit" || (isset($haserror) && $action == "update")) {
		?>
		<input type="hidden" name="userid" value="<?php echo $_GET["userid"]; ?>">
		<input type="hidden" name="action" value="update">
		<?php
	}
	else if ($action == "" || (isset($haserror) && $action == "insert")) {
		?>
		<input type="hidden" name="action" value="insert">
		<?php
	}
	?>
	<div align="center">
		<table class="partbox">
			<tr class="partboxtitle">
				<td align="center" colspan="2"><?php echo ($action == "edit" ? "Edit User '" . $username . "'" : "Add New User"); ?></td>
			</tr>
			<tr valign="top">
				<td>Username</td>
				<td>
					<input name="username" type="text" value="<?php echo htmlspecialchars($username); ?>" maxlength="255" size="50"/>
					<?php
					if (isset($username_error)) {
						?>
						<br />
						<font color="red"><?php echo $username_error ?></font><?php
					}
					?>
				</td>
			</tr>
			<tr valign="top">
				<td>Fullname</td>
				<td>
					<input name="fullname" type="text" value="<?php echo htmlspecialchars($fullname); ?>" maxlength="255" size="50"/>
					<?php
					if (isset($fullname_error)) {
						?>
						<br />
						<font color="red"><?php echo $fullname_error ?></font>
						<?php
					}
					?>
				</td>
			</tr>
			<tr valign="top">
				<td>E-Mail</td>
				<td>
					<input name="email" type="text" value="<?php echo htmlspecialchars($email); ?>" maxlength="255" size="50"/>
					<?php
					if (isset($email_error)) {
						?>
						<br />
						<font color="red"><?php echo $email_error ?></font>
						<?php
					}
					?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="email_msgs" <?php if ($email_msgs == 1) echo "CHECKED"; ?>>E-mail messages
					&nbsp;
					<input type="checkbox" name="approved" <?php if ($approved == 1) echo "CHECKED"; ?>>Approved
					&nbsp;
					<input type="checkbox" name="admin" <?php if ($userisadmin == 1) echo "CHECKED"; ?>>Administrator
				</td>
			</tr>
		</table>
	</div>
	<p>
		<div align="center">
			<input type="submit" value="<?php if ($action == "" || $action == "insert") echo "Add"; else echo "Update"; ?>"/>
			<input type="button" value="Cancel" onClick="document.location.href='users.php';">
		</div>
	</p>
</form>
</body>
</html>
