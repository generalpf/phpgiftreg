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

require_once(dirname(__FILE__) . "/includes/funcLib.php");
require_once(dirname(__FILE__) . "/includes/MySmarty.class.php");
$smarty = new MySmarty();
$opt = $smarty->opt();

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
    $message = $_GET["message"];
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
	
	$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}shoppers WHERE shopper = ? OR mayshopfor = ?");
	$stmt->bindParam(1, $deluserid, PDO::PARAM_INT);
	$stmt->bindParam(2, $deluserid, PDO::PARAM_INT);
	$stmt->execute();
	
	// we can't leave messages with dangling senders, so delete those too.
	$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}messages WHERE sender = ? OR recipient = ?");
	$stmt->bindParam(1, $deluserid, PDO::PARAM_INT);
	$stmt->bindParam(2, $deluserid, PDO::PARAM_INT);
	$stmt->execute();

	$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}events WHERE userid = ?");
	$stmt->bindParam(1, $deluserid, PDO::PARAM_INT);
	$stmt->execute();
	
	$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}items WHERE userid = ?");
	$stmt->bindParam(1, $deluserid, PDO::PARAM_INT);
	$stmt->execute();

	$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}users WHERE userid = ?");
	$stmt->bindParam(1, $deluserid, PDO::PARAM_INT);
	$stmt->execute();
	
	header("Location: " . getFullPath("users.php?message=User+deleted."));
	exit;
}
else if ($action == "edit") {
	$stmt = $smarty->dbh()->prepare("SELECT username, fullname, email, email_msgs, approved, admin FROM {$opt["table_prefix"]}users WHERE userid = ?");
	$stmt->bindValue(1, (int) $_GET["userid"], PDO::PARAM_INT);
	$stmt->execute();
	if ($row = $stmt->fetch()) {
		$username = $row["username"];
		$fullname = $row["fullname"];
		$email = $row["email"];
		$email_msgs = $row["email_msgs"];
		$approved = $row["approved"];
		$userisadmin = $row["admin"];
	}
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
		$pwd = generatePassword($opt);
		$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}users(username,password,fullname,email,email_msgs,approved,admin) VALUES(?, {$opt["password_hasher"]}(?), ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $username, PDO::PARAM_STR);
		$stmt->bindParam(2, $pwd, PDO::PARAM_STR);
		$stmt->bindParam(3, $fullname, PDO::PARAM_STR);
		$stmt->bindParam(4, $email, PDO::PARAM_STR);
		$stmt->bindParam(5, $email_msgs, PDO::PARAM_BOOL);
		$stmt->bindParam(6, $approved, PDO::PARAM_BOOL);
		$stmt->bindParam(7, $userisadmin, PDO::PARAM_BOOL);
		$stmt->execute();

		mail(
			$email,
			"Gift Registry account created",
			"Your Gift Registry account was created.\r\n" . 
				"Your username is $username and your password is $pwd.",
			"From: {$opt["email_from"]}\r\nReply-To: {$opt["email_reply_to"]}\r\nX-Mailer: {$opt["email_xmailer"]}\r\n"
		) or die("Mail not accepted for $email");	
		header("Location: " . getFullPath("users.php?message=User+added+and+e-mail+sent."));
		exit;
	}
}
else if ($action == "update") {
	if (!$haserror) {
		$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}users SET " .
				"username = ?, " .
				"fullname = ?, " .
				"email = ?, " .
				"email_msgs = ?, " .
				"approved = ?, " . 
				"admin = ? " . 
				"WHERE userid = ?");
		$stmt->bindParam(1, $username, PDO::PARAM_STR);
		$stmt->bindParam(2, $fullname, PDO::PARAM_STR);
		$stmt->bindParam(3, $email, PDO::PARAM_STR);
		$stmt->bindParam(4, $email_msgs, PDO::PARAM_BOOL);
		$stmt->bindParam(5, $approved, PDO::PARAM_BOOL);
		$stmt->bindParam(6, $userisadmin, PDO::PARAM_BOOL);
		$stmt->bindValue(7, (int) $_GET["userid"], PDO::PARAM_INT);
		$stmt->execute();
		header("Location: " . getFullPath("users.php?message=User+updated."));
		exit;		
	}
}
else if ($action == "reset") {
	$resetuserid = $_GET["userid"];
	$resetemail = $_GET["email"];
	
	// generate a password and insert the row.
	$pwd = generatePassword($opt);
	$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}users SET password = {$opt["password_hasher"]}(?) WHERE userid = ?");
	$stmt->bindParam(1, $pwd, PDO::PARAM_STR);
	$stmt->bindParam(2, $resetuserid, PDO::PARAM_INT);
	$stmt->execute();
	mail(
		$resetemail,
		"Gift Registry password reset",
		"Your Gift Registry password was reset to $pwd.",
		"From: {$opt["email_from"]}\r\nReply-To: {$opt["email_reply_to"]}\r\nX-Mailer: {$opt["email_xmailer"]}\r\n"
	) or die("Mail not accepted for $email");
	header("Location: " . getFullPath("users.php?message=Password+reset."));
	exit;
}
else {
	echo "Unknown verb.";
	exit;
}

$stmt = $smarty->dbh()->prepare("SELECT userid, username, fullname, email, email_msgs, approved, admin FROM {$opt["table_prefix"]}users ORDER BY username");
$stmt->execute();
$users = array();
while ($row = $stmt->fetch()) {
	$users[] = $row;
}

$smarty->assign('action', $action);
$smarty->assign('edituserid', (int) $_GET["userid"]);
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
if (isset($haserror)) {
	$smarty->assign('haserror', $haserror);
}
$smarty->assign('users', $users);
if (isset($message)) {
	$smarty->assign('message', $message);
}
$smarty->assign('userid', $userid);
$smarty->display('users.tpl');
?>
