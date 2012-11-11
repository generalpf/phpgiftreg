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

$action = "";
if (!empty($_GET["action"])) {
	$action = $_GET["action"];
	
	if ($action == "send") {
		$msg = $_GET["msg"];
		if (!get_magic_quotes_gpc())
			$msg = addslashes($msg);

		for ($i = 0; $i < count($_GET["recipients"]); $i++)
			sendMessage($userid,(int) $_GET["recipients"][$i],$msg);
		
		header("Location: " . getFullPath("index.php?message=Your+message+has+been+sent+to+" . count($_GET["recipients"]) . "+recipient(s)."));
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
<title>Gift Registry - Compose a Message</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body>
<form name="message" method="get" action="message.php">
	<input type="hidden" name="action" value="send">
	<div align="center">
		<TABLE class="partbox">
			<TR valign="top">
				<TD>
					<b>Recipients</b><br />
					<i>(Hold CTRL while clicking to<br />select multiple names.)</i>
				</TD>
				<TD>
					<?php
					$query = "SELECT u.userid, u.fullname " .
									"FROM {$OPT["table_prefix"]}shoppers s " .
									"INNER JOIN {$OPT["table_prefix"]}users u ON u.userid = s.mayshopfor " .
									"WHERE s.shopper = " . $userid . " " .
									"AND pending = 0 " .
									"ORDER BY u.fullname";
					$recipients = mysql_query($query) or die("Could not query: " . mysql_error());
					?>
					<select name="recipients[]" size="<?php echo mysql_num_rows($recipients) ?>" MULTIPLE>
						<?php
						while ($row = mysql_fetch_array($recipients,MYSQL_ASSOC)) {
							?>
							<option value="<?php echo $row["userid"] ?>"><?php echo $row["fullname"] ?></option>
							<?php
						}
						?>
					</select>
				</TD>
			</TR>
			<TR valign="top">
				<TD colspan="2">
					<b>Message</b><br />
					<textarea name="msg" rows="5" cols="40"></textarea>
				</TD>
			</TR>
		</TABLE>
	</div>
	<p>
		<div align="center">
			<input type="submit" value="Send Message"/>
			<input type="button" value="Cancel" onClick="document.location.href='index.php';">
		</div>
	</p>
</form>
</body>
</html>
