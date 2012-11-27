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

require_once(dirname(__FILE__) . "/includes/config.php");

$opt = getGlobalOptions();

function dbh($opt) {
	return new PDO($opt["pdo_connection_string"], $opt["pdo_username"], $opt["pdo_password"]);
}

$stmt = dbh($opt)->prepare("SELECT COUNT(*) AS user_count FROM {$opt["table_prefix"]}users");
$stmt->execute();
if ($row = $stmt->fetch()) {
	$user_count = $row["user_count"];
	if (false && $user_count != 0) {
		die("Database has already been set up.");
	}
}
else {
	die("Database has not been created.");
}

if (isset($_POST["action"])) {
	if ($_POST["action"] == "setup") {
		$username = $_POST["username"];
		$fullname = $_POST["fullname"];
		$pwd = $_POST["pwd"];
		$email = $_POST["email"];
		$familyname = $_POST["familyname"];

		// 1. create the family.
		$stmt = dbh($opt)->prepare("INSERT INTO {$opt["table_prefix"]}families(familyname) VALUES(?)");
		$stmt->bindParam(1, $familyname, PDO::PARAM_STR);
		$stmt->execute();
						         
		// 2. get the familyid.
		$stmt = dbh($opt)->prepare("SELECT MAX(familyid) AS familyid FROM {$opt["table_prefix"]}families");
		$stmt->execute();
		if ($row = $stmt->fetch()) {
			$familyid = $row["familyid"];
		}
		else die("No family was created.");

		// 3. insert the user.
		$stmt = dbh($opt)->prepare("INSERT INTO {$opt["table_prefix"]}users(username,fullname,password,email,approved,admin,initialfamilyid) VALUES(?, ?, {$opt["password_hasher"]}(?), ?, 1, 1, ?)");
		$stmt->bindParam(1, $username, PDO::PARAM_STR);
		$stmt->bindParam(2, $fullname, PDO::PARAM_STR);
		$stmt->bindParam(3, $pwd, PDO::PARAM_STR);
		$stmt->bindParam(4, $email, PDO::PARAM_STR);
		$stmt->bindParam(5, $familyid, PDO::PARAM_INT);
		$stmt->execute();

		// 4. get the userid.
		$stmt = dbh($opt)->prepare("SELECT MAX(userid) AS userid FROM {$opt["table_prefix"]}users");
		$stmt->execute();
		if ($row = $stmt->fetch()) {
			$userid = $row["userid"];
		}
		else die("No user was created.");

		// 5. create the membership.
		$stmt = dbh($opt)->prepare("INSERT INTO {$opt["table_prefix"]}memberships(userid,familyid) VALUES(?, ?)");
		$stmt->bindParam(1, $userid, PDO::PARAM_INT);
		$stmt->bindParam(2, $familyid, PDO::PARAM_INT);
		$stmt->execute();
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Gift Registry - Setup</title>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="js/jquery.validate.min.js"></script>
	<script language="JavaScript" type="text/javascript">
		$(document).ready(function() {
			$("#setupform").validate({
				rules: {
					"username": {
						required: true,
						maxlength: 20
					},
					"pwd": {
						required: true,
						maxlength: 50
					},
					"confirmpwd": {
						required: true,
						equalTo: "#pwd",
						maxlength: 50
					},
					"fullname": {
						required: true,
						maxlength: 50
					},
					"email": {
						required: true,
						maxlength: 255,
						email: true
					}
				},
				messages: {
					"username": {
						required: "The initial username is required.",
						maxlength: "The initial username must be 20 characters or less."
					},
					"pwd": {
						required: "The initial password is required.",
						maxlength: "The initial password must be 50 characters or less."
					},
					"confirmpwd": {
						required: "This value is required.",
						equalTo: "This value must match the initial password.",
						maxlength: "This value must be 50 characters or less."
					},
					"fullname": {
						required: "The full name is required.",
						maxlength: "The full name must be 50 characters or less."
					},
					"email": {
						required: "The e-mail address is required.",
						maxlength: "The e-mail address must be 255 characters or less.",
						email: "The e-mail address is invalid."
					}
				}
			});
		});
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
		foreach ($opt as $key => $value) {
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
	$image_dir = $parts['dirname'] . "/" . $opt["image_subdir"];
	$writeable = is_writable($image_dir);
	if ($writeable) {
		echo "<font color=\"green\">$image_dir is writeable, images can be uploaded.</font>";
	}
	else {
		echo "<font color=\"red\">$image_dir is NOT writeable, images cannot be uploaded.  Either chmod this directory to allow the web server to write to it, or disable image uploading in config.php.</font>";
	}
	echo "</p>";

	// check if Smarty works.
	echo "<p>Testing Smarty installation... ensure the result is OK.</p>";
	require_once(dirname(__FILE__) . "/includes/MySmarty.class.php");
	$smarty = new MySmarty();
	$smarty->testInstall();
	?>
	<form name="setupform" id="setupform" method="post" action="setup.php">	
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
						<input id="username" name="username" size="20" maxlength="20" type="text" value="<?php if (isset($_POST["username"])) echo htmlspecialchars($_POST["username"]); ?>"/>
					</td>
				</tr>
				<tr>
					<td>Admin password</td>
					<td>
						<input id="pwd" name="pwd" size="20" maxlength="50" type="password" />
					</td>
				</tr>
				<tr>
					<td>Confirm admin password</td>
					<td>
						<input id="confirmpwd" name="confirmpwd" size="20" maxlength="50" type="password" />
					</td>
				</tr>
				<tr>
					<td>Admin full name</td>
					<td>
						<input id="fullname" name="fullname" size="30" maxlength="50" type="text" value="<?php if (isset($_POST["fullname"])) echo htmlspecialchars($_POST["fullname"]); ?>" />
					</td>
				</tr>
				<tr>
					<td>Admin e-mail address</td>
					<td>
						<input id="email" name="email" size="30" maxlength="255" type="text" value="<?php if (isset($_POST["email"])) echo htmlspecialchars($_POST["email"]); ?>" />
					</td>
				</tr>
				<tr>
					<td>Default/initial family name</td>
					<td>
						<input id="familyname" name="familyname" size="50" maxlength="255" type="text" value="<?php if (isset($_POST["familyname"])) echo htmlspecialchars($_POST["familyname"]); ?>" />
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="submit" value="Submit" />
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
