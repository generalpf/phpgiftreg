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
else if ($_SESSION["admin"] != 1) {
	echo "You don't have admin privileges.";
	exit;
}
else {
	$userid = $_SESSION["userid"];
}
if (!empty($_GET["message"])) {
    $message = $_GET["message"];
}

$action = empty($_GET["action"]) ? "" : $_GET["action"];

if (!empty($_GET["familyid"]))
	$familyid = (int) $_GET["familyid"];

if ($action == "insert" || $action == "update") {
	/* validate the data. */
	$familyname = trim($_GET["familyname"]);
		
	$haserror = false;
	if ($familyname == "") {
		$haserror = true;
		$familyname_error = "A family name is required.";
	}
}

if ($action == "delete") {
	try {
		/* first, delete all memberships for this family. */
		$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}memberships WHERE familyid = ?");
		$stmt->bindParam(1, $familyid, PDO::PARAM_INT);
		$stmt->execute();

		$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}families WHERE familyid = ?");
		$stmt->bindValue(1, $familyid, PDO::PARAM_INT);
		$stmt->execute();
	
		header("Location: " . getFullPath("families.php?message=Family+deleted."));
		exit;
	}
	catch (PDOException $e) {
		die("sql exception: " . $e->getMessage());
	}
}
else if ($action == "edit") {
	try {
		$stmt = $smarty->dbh()->prepare("SELECT familyname FROM {$opt["table_prefix"]}families WHERE familyid = ?");
		$stmt->bindValue(1, $familyid, PDO::PARAM_INT);
		$stmt->execute();
		if ($row = $stmt->fetch()) {
			$familyname = $row["familyname"];
		}
		else {
			die("family doesn't exist.");
		}
	}
	catch (PDOException $e) {
		die("sql exception: " . $e->getMessage());
	}
}
else if ($action == "") {
	$familyname = "";
}
else if ($action == "insert") {
	if (!$haserror) {
		try {
			$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}families(familyid,familyname) VALUES(NULL, ?)");
			$stmt->bindParam(1, $familyname, PDO::PARAM_STR);
			$stmt->execute();
		}
		catch (PDOException $e) {
			die("sql exception: " . $e->getMessage());
		}
		
		header("Location: " . getFullPath("families.php?message=Family+added."));
		exit;
	}
}
else if ($action == "update") {
	if (!$haserror) {
		try {
			$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}families " .
					"SET familyname = ? " .
					"WHERE familyid = ?");
			$stmt->bindParam(1, $familyname, PDO::PARAM_STR);
			$stmt->bindValue(2, $familyid, PDO::PARAM_INT);
			$stmt->execute();
		}
		catch (PDOException $e) {
			die("sql exception: " . $e->getMessage());
		}
		
		header("Location: " . getFullPath("families.php?message=Family+updated."));
		exit;		
	}
}
else if ($action == "members") {
	$members = isset($_GET["members"]) ? $_GET["members"] : array();
	try {
		/* first, delete all memberships for this family. */
		$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}memberships WHERE familyid = ?");
		$stmt->bindValue(1, $familyid, PDO::PARAM_INT);
		$stmt->execute();

		/* now add them back. */
		foreach ($members as $userid) {
			$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}memberships(userid,familyid) VALUES(?, ?)");
			$stmt->bindParam(1, $userid, PDO::PARAM_INT);
			$stmt->bindParam(2, $familyid, PDO::PARAM_INT);
			$stmt->execute();
		}
	}
	catch (PDOException $e) {
		die("sql exception: " . $e->getMessage());
	}
	
	header("Location: " . getFullPath("families.php?message=Members+changed."));
	exit;
}
else {
	die("Unknown verb.");
}

try {
	$stmt = $smarty->dbh()->prepare("SELECT f.familyid, familyname, COUNT(userid) AS members " .
			"FROM {$opt["table_prefix"]}families f " .
			"LEFT OUTER JOIN {$opt["table_prefix"]}memberships m ON m.familyid = f.familyid " .
			"GROUP BY f.familyid " .
			"ORDER BY familyname");
	$stmt->execute();
	$families = array();
	while ($row = $stmt->fetch()) {
		$families[] = $row;
	}

	if ($action == "edit") {
		$stmt = $smarty->dbh()->prepare("SELECT u.userid, u.fullname, m.familyid FROM {$opt["table_prefix"]}users u " .
				"LEFT OUTER JOIN {$opt["table_prefix"]}memberships m ON m.userid = u.userid AND m.familyid = ? " .
				"ORDER BY u.fullname");
		$stmt->bindParam(1, $familyid, PDO::PARAM_INT);
		$stmt->execute();
		$nonmembers = array();
		while ($row = $stmt->fetch()) {
			$nonmembers[] = $row;
		}
	}

	$smarty->assign('action', $action);
	$smarty->assign('haserror', $haserror);
	if (isset($familyname_error)) {
		$smarty->assign('familyname_error', $familyname_error);
	}
	$smarty->assign('families', $families);
	$smarty->assign('familyid',	$familyid);
	$smarty->assign('familyname', $familyname);
	if (isset($nonmembers)) {
		$smarty->assign('nonmembers', $nonmembers);
	}
	if (isset($message)) {
		$smarty->assign('message', $message);
	}
	$smarty->display('families.tpl');
}
catch (PDOException $e) {
	die("sql exception: " . $e->getMessage());
}
?>
