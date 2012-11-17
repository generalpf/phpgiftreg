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

$query = "SELECT userid, username, fullname, email, email_msgs, approved, admin FROM {$OPT["table_prefix"]}users ORDER BY username";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$users = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$users[] = $row;
}
mysql_free_result($rs);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('action', $action);
$smarty->assign('username', $username);
if (isset($username_error)) {
	$smarty->assign('username_error', $username_error);
}
$smarty->assign('fullname', $fullname);
if (isset($fullname_error)) {
	$smarty->assign('fullname_error', $fullname_error);
}
$smarty->assign('email', $email);
if (isset($email_error)) {
	$smarty->assign('email_error', $email_error);
}
$smarty->assign('email_msgs', $email_msgs);
$smarty->assign('approved', $approved);
$smarty->assign('userisadmin', $userisadmin);
$smarty->assign('haserror', $haserror);
$smarty->assign('users', $users);
if (isset($message)) {
	$smarty->assign('message', $message);
}
$smarty->assign('userid', $userid);
$smarty->assign('isadmin', $_SESSION["admin"]);
$smarty->assign('opt', $OPT);
$smarty->display('users.tpl');
?>
