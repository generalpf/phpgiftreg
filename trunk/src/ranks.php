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

$action = $_GET["action"];

if ($action == "insert" || $action == "update") {
	/* validate the data. */
	$title = trim($_GET["title"]);
	$rendered = trim($_GET["rendered"]);
	if (!get_magic_quotes_gpc()) {
		$title = addslashes($title);
		$rendered = addslashes($rendered);
	}
		
	$haserror = false;
	if ($title == "") {
		$haserror = true;
		$title_error = "A title is required.";
	}
	if ($rendered == "") {
		$haserror = true;
		$rendered_error = "HTML is required.";
	}
}

if ($action == "delete") {
	/* first, NULL all ranking FKs for items that use this rank. */
	$query = "UPDATE {$OPT["table_prefix"]}items SET ranking = NULL WHERE ranking = " . addslashes($_GET["ranking"]);
	mysql_query($query) or die("Could not query: " . mysql_error());
	$query = "DELETE FROM {$OPT["table_prefix"]}ranks WHERE ranking = " . addslashes($_GET["ranking"]);
	mysql_query($query) or die("Could not query: " . mysql_error());
	header("Location: " . getFullPath("ranks.php?message=Rank+deleted."));
	exit;
}
else if ($action == "promote") {
	$query = "UPDATE {$OPT["table_prefix"]}ranks SET rankorder = rankorder + 1 WHERE rankorder = " . addslashes($_GET["rankorder"]) . " - 1";
	mysql_query($query) or die("Could not query: " . mysql_error());
	$query = "UPDATE {$OPT["table_prefix"]}ranks SET rankorder = rankorder - 1 WHERE ranking = " . addslashes($_GET["ranking"]);
	mysql_query($query) or die("Could not query: " . mysql_error());
	header("Location: " . getFullPath("ranks.php?message=Rank+promoted."));
	exit;
}
else if ($action == "demote") {
    $query = "UPDATE {$OPT["table_prefix"]}ranks SET rankorder = rankorder - 1 WHERE rankorder = " . addslashes($_GET["rankorder"]) . " + 1";
    mysql_query($query) or die("Could not query: " . mysql_error());
    $query = "UPDATE {$OPT["table_prefix"]}ranks SET rankorder = rankorder + 1 WHERE ranking = " . addslashes($_GET["ranking"]);
	mysql_query($query) or die("Could not query: " . mysql_error());
    header("Location: " . getFullPath("ranks.php?message=Rank+demoted."));
    exit;
}
else if ($action == "edit") {
	$query = "SELECT title, rendered FROM {$OPT["table_prefix"]}ranks WHERE ranking = " . addslashes($_GET["ranking"]);
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$title = $row["title"];
		$rendered = $row["rendered"];
	}
	mysql_free_result($rs);
}
else if ($action == "") {
	$title = "";
	$rendered = "";
}
else if ($action == "insert") {
	if (!$haserror) {
		/* first determine the highest rankorder and add one. */
		$query = "SELECT MAX(rankorder) as maxrankorder FROM {$OPT["table_prefix"]}ranks";
		$rs = mysql_query($query) or die("Could not query: " . mysql_error());
		if ($row = mysql_fetch_array($rs,MYSQL_ASSOC))
			$rankorder = $row["maxrankorder"] + 1;
		mysql_free_result($rs);
		$query = "INSERT INTO {$OPT["table_prefix"]}ranks(title,rendered,rankorder) " .
					"VALUES('$title','$rendered',$rankorder)";
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("ranks.php?message=Rank+added."));
		exit;
	}
}
else if ($action == "update") {
	if (!$haserror) {
		$query = "UPDATE {$OPT["table_prefix"]}ranks " .
					"SET title = '$title', rendered = '$rendered' " .
					"WHERE ranking = " . addslashes($_GET["ranking"]);
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("ranks.php?message=Rank+updated."));
		exit;		
	}
}
else {
	echo "Unknown verb.";
	exit;
}

$query = "SELECT ranking, title, rendered, rankorder " .
			"FROM {$OPT["table_prefix"]}ranks " .
			"ORDER BY rankorder";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$ranks = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$ranks[] = $row;
}
mysql_free_result($rs);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('action', $action);
$smarty->assign('ranks', $ranks);
if (isset($message)) {
	$smarty->assign('message', $message);
}
$smarty->assign('title', $title);
if (isset($title_error)) {
	$smarty->assign('title_error', $title_error);
}
$smarty->assign('rendered', $rendered);
if (isset($rendered_error)) {
	$smarty->assign('rendered_error', $rendered_error);
}
$smarty->assign('ranking', $_GET["ranking"]);
$smarty->assign('haserror', $haserror);
$smarty->assign('isadmin', $_SESSION["admin"]);
$smarty->assign('opt', $OPT);
$smarty->display('ranks.tpl');
?>
