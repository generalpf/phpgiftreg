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

$action = "";
if (!empty($_POST["action"])) {
	$action = $_POST["action"];
	
	if ($action == "changepwd") {
		$newpwd = $_POST["newpwd"];

		try {
			$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}users SET password = {$opt["password_hasher"]}(?) WHERE userid = ?");
			$stmt->bindParam(1, $newpwd, PDO::PARAM_STR);
			$stmt->bindParam(2, $userid, PDO::PARAM_INT);

			$stmt->execute();

			header("Location: " . getFullPath("index.php?message=Password+changed."));
			exit;
		}
		catch (PDOException $e) {
			die("sql exception: " . $e->getMessage());
		}
	}
	else if ($action == "save") {
		$fullname = $_POST["fullname"];
		$email = $_POST["email"];
		$comment = $_POST["comment"];
		$email_msgs = ($_POST["email_msgs"] == "on" ? 1 : 0);

		try {
			$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}users SET fullname = ?, email = ?, email_msgs = ?, comment = ? WHERE userid = ?");
			$stmt->bindParam(1, $fullname, PDO::PARAM_STR);
			$stmt->bindParam(2, $email, PDO::PARAM_STR);
			$stmt->bindParam(3, $email_msgs, PDO::PARAM_BOOL);
			$stmt->bindParam(4, $comment, PDO::PARAM_STR);
			$stmt->bindParam(5, $userid, PDO::PARAM_INT);
		
			$stmt->execute();

			$_SESSION["fullname"] = $fullname;

			header("Location: " . getFullPath("index.php?message=Profile+updated."));
			exit;
		}
		catch (PDOException $e) {
			die("sql exception: " . $e->getMessage());
		}
	}
	else {
		die("Unknown verb.");
	}
}

try {
	$stmt = $smarty->dbh()->prepare("SELECT fullname, email, email_msgs, comment FROM {$opt["table_prefix"]}users WHERE userid = ?");
	$stmt->bindParam(1, $userid, PDO::PARAM_INT);

	$stmt->execute();
	if ($row = $stmt->fetch()) {
		$smarty->assign('fullname', $row["fullname"]);
		$smarty->assign('email', $row["email"]);
		$smarty->assign('email_msgs', $row["email_msgs"]);
		$smarty->assign('comment', $row["comment"]);
		$smarty->display('profile.tpl');
	}
	else {
		die("You don't exist.");
	}
}
catch (PDOException $e) {
	die("sql exception: " . $e->getMessage());
}
?>
