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
if (!empty($_GET["message"])) {
    $message = strip_tags($_GET["message"]);
}

$action = empty($_GET["action"]) ? "" : $_GET["action"];

if ($action == "insert" || $action == "update") {
	/* validate the data. */
	$familyname = trim($_GET["familyname"]);
	if (!get_magic_quotes_gpc())
		$familyname = addslashes($familyname);
		
	$haserror = false;
	if ($familyname == "") {
		$haserror = true;
		$familyname_error = "A family name is required.";
	}
}

if ($action == "delete") {
	/* first, delete all memberships for this family. */
	$query = "DELETE FROM {$OPT["table_prefix"]}memberships WHERE familyid = " . addslashes($_GET["familyid"]);
	mysql_query($query) or die("Could not query: " . mysql_error());
	$query = "DELETE FROM {$OPT["table_prefix"]}families WHERE familyid = " . addslashes($_GET["familyid"]);
	mysql_query($query) or die("Could not query: " . mysql_error());
	header("Location: " . getFullPath("families.php?message=Family+deleted."));
	exit;
}
else if ($action == "edit") {
	$query = "SELECT familyname FROM {$OPT["table_prefix"]}families WHERE familyid = " . addslashes($_GET["familyid"]);
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$familyname = $row["familyname"];
	}
	mysql_free_result($rs);
}
else if ($action == "") {
	$familyname = "";
}
else if ($action == "insert") {
	if (!$haserror) {
		$query = "INSERT INTO {$OPT["table_prefix"]}families(familyid,familyname) " .
					"VALUES(NULL,'$familyname')";
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("families.php?message=Family+added."));
		exit;
	}
}
else if ($action == "update") {
	if (!$haserror) {
		$query = "UPDATE {$OPT["table_prefix"]}families " .
					"SET familyname = '$familyname' " .
					"WHERE familyid = " . addslashes($_GET["familyid"]);
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("families.php?message=Family+updated."));
		exit;		
	}
}
else if ($action == "members") {
	$members = $_GET["members"];
	/* first, delete all memberships for this family. */
	$query = "DELETE FROM {$OPT["table_prefix"]}memberships WHERE familyid = " . addslashes($_GET["familyid"]);
	mysql_query($query) or die("Could not query: " . mysql_error());
	/* now add them back. */
	foreach ($members as $userid) {
		$query = "INSERT INTO {$OPT["table_prefix"]}memberships(userid,familyid) VALUES(" . addslashes($userid) . "," . addslashes($_GET["familyid"]) . ")";
		mysql_query($query) or die("Could not query: " . mysql_error());
	}
	header("Location: " . getFullPath("families.php?message=Members+changed."));
	exit;
}
else {
	echo "Unknown verb.";
	exit;
}

$query = "SELECT f.familyid, familyname, COUNT(userid) AS members " .
			"FROM {$OPT["table_prefix"]}families f " .
			"LEFT OUTER JOIN {$OPT["table_prefix"]}memberships m ON m.familyid = f.familyid " .
			"GROUP BY f.familyid " .
			"ORDER BY familyname";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$families = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$families[] = $row;
}
mysql_free_result($rs);

if ($action == "edit") {
	$query = "SELECT u.userid, u.fullname, m.familyid FROM {$OPT["table_prefix"]}users u " .
				"LEFT OUTER JOIN {$OPT["table_prefix"]}memberships m ON m.userid = u.userid AND m.familyid = " . addslashes($_GET["familyid"]) . " " .
				"ORDER BY u.fullname";
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	$nonmembers = array();
	while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
		$nonmembers[] = $row;
	}
	mysql_free_result($rs);
}

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('action', $action);
$smarty->assign('haserror', $haserror);
if (isset($familyname_error)) {
	$smarty->assign('familyname_error', $familyname_error);
}
$smarty->assign('families', $families);
$smarty->assign('familyid', $_GET["familyid"]);
$smarty->assign('familyname', $familyname);
if (isset($nonmembers)) {
	$smarty->assign('nonmembers', $nonmembers);
}
if (isset($message)) {
	$smarty->assign('message', $message);
}
$smarty->assign('isadmin', $_SESSION["admin"]);
$smarty->assign('opt', $OPT);
$smarty->display('families.tpl');
?>
