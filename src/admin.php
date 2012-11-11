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
else if ($_SESSION["admin"] != 1) {
	echo "You don't have admin privileges.";
	exit;
}
else {
	$userid = $_SESSION["userid"];
}

$action = $_GET["action"];
if ($action == "approve") {
	$pwd = generatePassword();
	if ($_GET["familyid"] != "") {
		$query = "INSERT INTO {$OPT["table_prefix"]}memberships(userid,familyid) VALUES(" . $_GET["userid"] . "," . $_GET["familyid"] . ")";
		mysql_query($query) or die("Could not query: " . mysql_error());
	}
	$query = "UPDATE {$OPT["table_prefix"]}users SET approved = 1, password = {$OPT["password_hasher"]}('$pwd') WHERE userid = " . $_GET["userid"];
	mysql_query($query) or die("Could not query: " . mysql_error());
	
	// send the e-mails
	$query = "SELECT username, email FROM {$OPT["table_prefix"]}users WHERE userid = " . $_GET["userid"];
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		mail(
			$row["email"],
			"Gift Registry application approved",
			"Your Gift Registry application was approved by " . $_SESSION["fullname"] . ".\r\n" . 
				"Your username is " . $row["username"] . " and your password is $pwd.",
			"From: {$OPT["email_from"]}\r\nReply-To: {$OPT["email_reply_to"]}\r\nX-Mailer: {$OPT["email_xmailer"]}\r\n"
		) or die("Mail not accepted for " . $row["email"]);	
	}
	mysql_free_result($rs);
	header("Location: " . getFullPath("index.php"));
	exit;
}
else if ($action == "reject") {
	// send the e-mails
	$query = "SELECT email FROM {$OPT["table_prefix"]}users WHERE userid = " . $_GET["userid"];
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		mail(
			$row["email"],
			"Gift Registry application denied",
			"Your Gift Registry application was denied by " . $_SESSION["fullname"] . ".",
			"From: {$OPT["email_from"]}\r\nReply-To: {$OPT["email_reply_to"]}\r\nX-Mailer: {$OPT["email_xmailer"]}\r\n"
		) or die("Mail not accepted for " . $row["email"]);	
	}
	mysql_free_result($rs);
	
	$query = "DELETE FROM {$OPT["table_prefix"]}users WHERE userid = " . $_GET["userid"];
	mysql_query($query) or die("Could not query: " . mysql_error());
	
	header("Location: " . getFullPath("index.php"));
	exit;
}
?>
