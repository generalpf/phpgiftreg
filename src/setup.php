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

$query = "SELECT COUNT(*) AS user_count FROM {$OPT["table_prefix"]}users";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$row = mysql_fetch_array($rs,MYSQL_ASSOC);
$user_count = $row["user_count"];
mysql_free_result($rs);
if ($user_count != 0) {
	echo "Database has already been set up.";
	exit;
}

if (isset($_POST["action"])) {
	if ($_POST["action"] == "setup") {
		$username = $_POST["username"];
		$fullname = $_POST["fullname"];
		$pwd = $_POST["pwd"];
		$email = $_POST["email"];
		$familyname = $_POST["familyname"];
		if (!get_magic_quotes_gpc()) {
			$username = addslashes($username);
			$fullname = addslashes($fullname);
			$pwd = addslashes($pwd);
			$email = addslashes($email);
			$familyname = addslashes($familyname);
		}

		// 1. create the family.
		$query = "INSERT INTO {$OPT["table_prefix"]}families(familyname) VALUES('$familyname')";
		mysql_query($query) or die("Could not query: " . mysql_error());
						         
		// 2. get the familyid.
		$query = "SELECT familyid FROM {$OPT["table_prefix"]}families";
		$rs = mysql_query($query) or die("Could not query: " . mysql_error());
		$row = mysql_fetch_assoc($rs) or die("Could not query: " . mysql_error());
		$familyid = $row["familyid"];
		mysql_free_result($rs);

		// 3. insert the user.
		$query = "INSERT INTO {$OPT["table_prefix"]}users(username,fullname,password,email,approved,admin,initialfamilyid) VALUES('$username','$fullname',{$OPT["password_hasher"]}('$pwd'),'$email',1,1,$familyid)";
		mysql_query($query) or die("Could not query: " . mysql_error());

		// 4. get the userid.
		$query = "SELECT userid FROM {$OPT["table_prefix"]}users";
		$rs = mysql_query($query) or die("Could not query: " . mysql_error());
		$row = mysql_fetch_assoc($rs) or die("Could not query: " . mysql_error());
		$userid = $row["userid"];
		mysql_free_result($rs);

		// 5. create the membership.
		$query = "INSERT INTO {$OPT["table_prefix"]}memberships(userid,familyid) VALUES($userid,$familyid)";
		mysql_query($query) or die("Could not query: " . mysql_error());
	}
}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Setup</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
<script language="JavaScript" type="text/javascript">
	function validateSetup() {
		field = document.setup.username;
		if (field == null || field == undefined || !field.value.match("\\S")) {
			alert("You must supply a username.");
			field.focus();
			return false;
		}
		
		field = document.setup.fullname;
		if (field == null || field == undefined || !field.value.match("\\S")) {
			alert("You must supply your full name.");
			field.focus();
			return false;
		}

		field = document.setup.pwd;
		if (field == null || field == undefined || !field.value.match("\\S")) {
			alert("You must supply your password.");
			field.focus();
			return false;
		}
		if (field.value != document.setup.confirmpwd.value) {
			alert("Passwords do not match.");
			field.focus();
			return false;
		}
		
		field = document.setup.email;
		if (!field.value.match("\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*")) {
			alert("The e-mail address '" + field.value + "' is not a valid address.");
			field.focus();
			return false;
		}

		field = document.setup.familyname;
		if (field == null || field == undefined || !field.value.match("\\S")) {
			alert("You must specify the name of the default/initial family.");
			field.focus();
			return false;
		}
		
		return true;
	}
</script>
</head>
<body>
<?php
if (isset($_POST["action"]) && $_POST["action"] == "setup") {
	// success!
	?>
	<p>
		Thank you for setting up the Gift Registry.  You may now <a href="login.php">login</a> and begin!
	</p>
	<p>
		Below are your configuration values.  If you would like to change anything, edit config.php.  Each value's purpose is described in config.php.
	</p>
	<table border="1" cellpadding="2" cellspacing="2">
		<?php
		foreach ($OPT as $key => $value) {
			?>
			<tr>
				<td><?php echo $key; ?></td>
				<td><?php echo $value; ?></td>
			</tr>
			<?php
		}
		?>
	</table>
	<?php
}
else {
	// check their image_subdir for writeability.
	echo "<p>";
	$parts = pathinfo($_SERVER["SCRIPT_FILENAME"]);
	$image_dir = $parts['dirname'] . "/" . $OPT["image_subdir"];
	$writeable = is_writable($image_dir);
	if ($writeable) {
		echo "<font color=\"green\">$image_dir is writeable, images can be uploaded.</font>";
	}
	else {
		echo "<font color=\"red\">$image_dir is NOT writeable, images cannot be uploaded.  Either chmod this directory to allow the web server to write to it, or disable image uploading in config.php.</font>";
	}
	echo "</p>";
	?>
	<form name="setup" method="post" action="setup.php">	
		<input type="hidden" name="action" value="setup">
		<div align="center">
			<table cellpadding="3" class="partbox" width="50%">
				<tr>
					<td colspan="2" class="partboxtitle" align="center">Set Up the Gift Registry</td>
				</tr>
				<tr>
					<td colspan="2">
						<p>
							This installation of the Gift Registry is not complete.  Please create the initial administrator user, name the default family, then click Submit.
						</p>
					</td>
				</tr>
				<tr>
					<td>Admin username</td>
					<td>
						<input name="username" size="20" maxlength="20" type="text" value="<?php if (isset($_POST["username"])) echo htmlspecialchars(stripslashes($_POST["username"])); ?>"/>
					</td>
				</tr>
				<tr>
					<td>Admin password</td>
					<td>
						<input name="pwd" size="20" maxlength="50" type="password" />
					</td>
				</tr>
				<tr>
					<td>Confirm admin password</td>
					<td>
						<input name="confirmpwd" size="20" maxlength="50" type="password" />
					</td>
				</tr>
				<tr>
					<td>Admin full name</td>
					<td>
						<input name="fullname" size="30" maxlength="50" type="text" value="<?php if (isset($_POST["fullname"])) echo htmlspecialchars(stripslashes($_POST["fullname"])); ?>" />
					</td>
				</tr>
				<tr>
					<td>Admin e-mail address</td>
					<td>
						<input name="email" size="30" maxlength="255" type="text" value="<?php if (isset($_POST["email"])) echo $_POST["email"]; ?>" />
					</td>
				</tr>
				<tr>
					<td>Default/initial family name</td>
					<td>
						<input name="familyname" size="50" maxlength="255" type="text" value="<?php if (isset($_POST["familyname"])) echo $_POST["familyname"]; ?>" />
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="submit" value="Submit" onClick="return validateSetup();" />
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
