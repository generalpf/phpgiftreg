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
	$category = trim($_GET["category"]);
	if (!get_magic_quotes_gpc())
		$category = addslashes($category);
		
	$haserror = false;
	if ($category == "") {
		$haserror = true;
		$category_error = "A category is required.";
	}
}

if ($action == "delete") {
	/* first, NULL all category FKs for items that use this category. */
	$query = "UPDATE {$OPT["table_prefix"]}items SET category = NULL WHERE category = " . addslashes($_GET["categoryid"]);
	mysql_query($query) or die("Could not query: " . mysql_error());
	$query = "DELETE FROM {$OPT["table_prefix"]}categories WHERE categoryid = " . addslashes($_GET["categoryid"]);
	mysql_query($query) or die("Could not query: " . mysql_error());
	header("Location: " . getFullPath("categories.php?message=Category+deleted."));
	exit;
}
else if ($action == "edit") {
	$query = "SELECT category FROM {$OPT["table_prefix"]}categories WHERE categoryid = " . addslashes($_GET["categoryid"]);
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
		$category = $row["category"];
	}
	mysql_free_result($rs);
}
else if ($action == "") {
	$category = "";
}
else if ($action == "insert") {
	if (!$haserror) {
		$query = "INSERT INTO {$OPT["table_prefix"]}categories(categoryid,category) " .
					"VALUES(NULL,'$category')";
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("categories.php?message=Category+added."));
		exit;
	}
}
else if ($action == "update") {
	if (!$haserror) {
		$query = "UPDATE {$OPT["table_prefix"]}categories " .
					"SET category = '$category' " .
					"WHERE categoryid = " . addslashes($_GET["categoryid"]);
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("categories.php?message=Category+updated."));
		exit;		
	}
}
else {
	echo "Unknown verb.";
	exit;
}

$query = "SELECT c.categoryid, c.category, COUNT(itemid) AS itemsin " .
			"FROM {$OPT["table_prefix"]}categories c " .
			"LEFT OUTER JOIN {$OPT["table_prefix"]}items i ON i.category = c.categoryid " .
			"GROUP BY c.categoryid, category " .
			"ORDER BY category";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$categories = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$categories[] = $row;
}
mysql_free_result($rs);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('action', $action);
$smarty->assign('categories', $categories);
$smarty->assign('categoryid', addslashes($_GET["categoryid"]));
if (isset($message)) {
	$smarty->assign('message', $message);
}
$smarty->assign('category', $category);
if (isset($category_error)) {
	$smarty->assign('category_error', $category_error);
}
$smarty->assign('haserror', $haserror);
$smarty->assign('isadmin', $_SESSION["admin"]);
$smarty->assign('opt', $OPT);
$smarty->display('categories.tpl');
?>
