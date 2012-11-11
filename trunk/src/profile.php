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

$query = "SELECT fullname, email, email_msgs, comment FROM {$OPT["table_prefix"]}users WHERE userid = " . $userid;
$rs = mysql_query($query) or die("You don't exist: " . mysql_error());
$row = mysql_fetch_array($rs, MYSQL_ASSOC);
$fullname = $row['fullname'];
$email = $row['email'];
$email_msgs = $row['email_msgs'];
$comment = $row['comment'];
mysql_free_result($rs);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('fullname', $fullname);
$smarty->assign('email', $email);
$smarty->assign('email_msgs', $email_msgs);
$smarty->assign('comment', $comment);
$smarty->assign('opt', $OPT);
$smarty->display('profile.tpl');
