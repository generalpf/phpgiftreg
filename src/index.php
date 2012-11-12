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

if (!empty($_GET["message"])) {
	$message = strip_tags($_GET["message"]);
}

/* if we've got `page' on the query string, set the session page indicator. */
if (isset($_GET["offset"])) {
	$_SESSION["offset"] = $_GET["offset"];
	$offset = $_GET["offset"];
}
else if (isset($_SESSION["offset"])) {
	$offset = $_SESSION["offset"];
}
else {
	$offset = 0;
}

if (!empty($_GET["action"])) {
	$action = $_GET["action"];
	if ($action == "ack") {
		$query = "UPDATE {$OPT["table_prefix"]}messages SET isread = 1 WHERE messageid = " . (int) $_GET["messageid"];
		mysql_query($query) or die("Could not query: " . mysql_error());
	}
	else if ($action == "approve") {
		$query = "UPDATE {$OPT["table_prefix"]}shoppers SET pending = 0 WHERE shopper = " . (int) $_GET["shopper"] . " AND mayshopfor = $userid";
		mysql_query($query) or die("Could not query: " . mysql_error());
		sendMessage($userid,(int) $_GET["shopper"],addslashes($_SESSION["fullname"] . " has approved your request to shop for him/her."));
	}
	else if ($action == "decline") {
		$query = "DELETE FROM {$OPT["table_prefix"]}shoppers WHERE shopper = " . (int) $_GET["shopper"] . " AND mayshopfor = $userid";
		mysql_query($query) or die("Could not query: " . mysql_error());
		sendMessage($userid,(int) $_GET["shopper"],addslashes($_SESSION["fullname"] . " has declined your request to shop for him/her."));
	}
	else if ($action == "request") {
		$query = "INSERT INTO {$OPT["table_prefix"]}shoppers(shopper,mayshopfor,pending) VALUES($userid," . (int) $_GET["shopfor"] . ",{$OPT["shop_requires_approval"]})";
		mysql_query($query) or die("Could not query: " . mysql_error());
		if ($OPT["shop_requires_approval"]) {
			sendMessage($userid,(int) $_GET["shopfor"],addslashes($_SESSION["fullname"] . " has requested to shop for you.  Please approve or decline this request."));
		}
	}
	else if ($action == "cancel") {
		// this works for either cancelling a request or "unshopping" for a user.
		$query = "DELETE FROM {$OPT["table_prefix"]}shoppers WHERE shopper = " . $userid . " AND mayshopfor = " . (int) $_GET["shopfor"];
		mysql_query($query) or die("Could not query: " . mysql_error());
	}
}

if (!empty($_GET["mysort"]))
	$_SESSION["mysort"] = $_GET["mysort"];
	
if (!isset($_SESSION["mysort"])) {
	$sortby = "rankorder DESC, i.description";
	$_SESSION["mysort"] = "ranking";
}
else {
	switch ($_SESSION["mysort"]) {
		case "ranking":
			$sortby = "rankorder DESC, i.description";
			break;
		case "description":
			$sortby = "i.description";
			break;
		case "price":
			$sortby = "price, rankorder DESC, i.description";
			break;
		case "category":
			$sortby = "c.category, rankorder DESC, i.description";
			break;
		default:
			$sortby = "rankorder DESC, i.description";
	}
}
$query = "SELECT itemid, description, c.category, price, url, rendered, comment, image_filename FROM {$OPT["table_prefix"]}items i LEFT OUTER JOIN {$OPT["table_prefix"]}categories c ON c.categoryid = i.category LEFT OUTER JOIN {$OPT["table_prefix"]}ranks r ON r.ranking = i.ranking WHERE userid = " . $userid . " ORDER BY $sortby";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$myitems_count = mysql_num_rows($rs);
$myitems = array();
for ($i = 0; $i < $offset; $i++) {
	$row = mysql_fetch_array($rs, MYSQL_ASSOC);
}
$i = 0;
while ($i++ < $OPT["items_per_page"] && $row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$row['price'] = formatPrice($row['price']);
	$myitems[] = $row;
}
mysql_free_result($rs);

$query = "SELECT u.userid, u.fullname, u.comment, u.list_stamp, COUNT(i.itemid) AS itemcount " .
			"FROM {$OPT["table_prefix"]}shoppers s " .
			"INNER JOIN {$OPT["table_prefix"]}users u ON u.userid = s.mayshopfor " .
			"LEFT OUTER JOIN {$OPT["table_prefix"]}items i ON u.userid = i.userid " .
			"WHERE s.shopper = " . $userid . " " .
				"AND pending = 0 " .
			"GROUP BY u.userid, u.fullname, u.list_stamp " .
			"ORDER BY u.fullname";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$shoppees = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$row['list_stamp'] = ($row['list_stamp == 0'] ? '-' : strftime("%B %d, %Y", strtotime($row['list_stamp'])));
	$shoppees[] = $row;
}
mysql_free_result($rs);

$query = "SELECT DISTINCT u.userid, u.fullname, s.pending " .
			"FROM {$OPT["table_prefix"]}memberships mymem " .
			"INNER JOIN {$OPT["table_prefix"]}memberships others " .
				"ON others.familyid = mymem.familyid AND others.userid <> " . $userid . " " .
			"INNER JOIN {$OPT["table_prefix"]}users u " .
				"ON u.userid = others.userid " .
			"LEFT OUTER JOIN {$OPT["table_prefix"]}shoppers s " .
				"ON s.mayshopfor = others.userid AND s.shopper = " . $userid . " " .
			"WHERE mymem.userid = " . $userid . " " .
				"AND (s.pending IS NULL OR s.pending = 1) " .
				"AND u.approved = 1 " .
			"ORDER BY u.fullname";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$prospects = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$prospects[] = $row;
}
mysql_free_result($rs);
					
$query = "SELECT messageid, u.fullname, message, created " .
			"FROM {$OPT["table_prefix"]}messages m " .
			"INNER JOIN {$OPT["table_prefix"]}users u ON u.userid = m.sender " .
			"WHERE m.recipient = " . $userid . " " .
				"AND m.isread = 0 " .
			"ORDER BY created DESC";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$messages = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$row['created'] = strftime("%a, %b %d", strtotime($row['created']));
	$messages[] = $row;
}
mysql_free_result($rs);
				
$query = "SELECT CONCAT(YEAR(CURDATE()),'-',MONTH(eventdate),'-',DAYOFMONTH(eventdate)) AS DateThisYear, " .
				"TO_DAYS(CONCAT(YEAR(CURDATE()),'-',MONTH(eventdate),'-',DAYOFMONTH(eventdate))) AS ToDaysDateThisYear, " .
				"CONCAT(YEAR(CURDATE()) + 1,'-',MONTH(eventdate),'-',DAYOFMONTH(eventdate)) AS DateNextYear, " .
				"TO_DAYS(CONCAT(YEAR(CURDATE()) + 1,'-',MONTH(eventdate),'-',DAYOFMONTH(eventdate))) AS ToDaysDateNextYear, " .
				"TO_DAYS(CURDATE()) AS ToDaysToday, " .
				"TO_DAYS(eventdate) AS ToDaysEventDate, " .
				"e.userid, u.fullname, description, eventdate, recurring, s.pending " .
			"FROM {$OPT["table_prefix"]}events e " .
			"LEFT OUTER JOIN {$OPT["table_prefix"]}users u ON u.userid = e.userid " .
			"LEFT OUTER JOIN {$OPT["table_prefix"]}shoppers s ON s.mayshopfor = e.userid AND s.shopper = $userid ";
if ($OPT["show_own_events"])
	$query .= "WHERE (pending = 0 OR pending IS NULL)";
else
	$query .= "WHERE (e.userid <> $userid OR e.userid IS NULL) AND (pending = 0 OR pending IS NULL)";
$query .= "ORDER BY u.fullname";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$events = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$event_fullname = $row["fullname"];
	$days_left = -1;
	if (!$row["recurring"] && (($row["ToDaysEventDate"] - $row["ToDaysToday"]) >= 0) && (($row["ToDaysEventDate"] - $row["ToDaysToday"]) <= $OPT["event_threshold"])) {
		$days_left = $row["ToDaysEventDate"] - $row["ToDaysToday"];
		$event_date = strtotime($row["eventdate"]);
	}
	else if ($row["recurring"] && (($row["ToDaysDateThisYear"] - $row["ToDaysToday"]) >= 0) && (($row["ToDaysDateThisYear"] - $row["ToDaysToday"]) <= $OPT["event_threshold"])) {
		$days_left = $row["ToDaysDateThisYear"] - $row["ToDaysToday"];
		$event_date = strtotime($row["DateThisYear"]);
	}
	else if ($row["recurring"] && (($row["ToDaysDateNextYear"] - $row["ToDaysToday"]) >= 0) && (($row["ToDaysDateNextYear"] - $row["ToDaysToday"]) <= $OPT["event_threshold"])) {
		$days_left = $row["ToDaysDateNextYear"] - $row["ToDaysToday"];
		$event_date = strtotime($row["DateNextYear"]);
	}
	if ($days_left >= 0) {
		$thisevent = array(
			'fullname' => $event_fullname,
			'eventname' => $row['description'],
			'daysleft' => $days_left,
			'date' => strftime("%B %d, %Y", $event_date)
		);
		$events[] = $thisevent;
	}
}
mysql_free_result($rs);
					
function compareEvents($a, $b) {
	if ($a[0] == $b[0])
		return 0;
	else
		return ($a > $b) ? 1 : -1;
}
					
// i couldn't figure out another way to do this, so here goes.
// sort() wanted to sort based on the array keys, which were 0..n - 1, so that was useless.
usort($events, "compareEvents");

if ($OPT["shop_requires_approval"]) {
	$query = "SELECT u.userid, u.fullname " .
				"FROM {$OPT["table_prefix"]}shoppers s " .
				"INNER JOIN {$OPT["table_prefix"]}users u ON u.userid = s.shopper " .
				"WHERE s.mayshopfor = " . $userid . " " .
					"AND s.pending = 1 " .
				"ORDER BY u.fullname";
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	$pending = array();
	while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
		$pending[] = $row;
	}
	mysql_free_result($rs);
}

if (($_SESSION["admin"] == 1) && $OPT["newuser_requires_approval"]) {
	$query = "SELECT userid, fullname, email, approved, initialfamilyid, familyname " .
				"FROM {$OPT["table_prefix"]}users u " .
				"LEFT OUTER JOIN {$OPT["table_prefix"]}families f ON f.familyid = u.initialfamilyid " .
				"WHERE approved = 0 " . 
				"ORDER BY fullname";
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	$approval = array();
	while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
		$approval[] = $row;
	}
	mysql_free_result($rs);
}

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('fullname', $_SESSION['fullname']);
if (isset($message)) {
	$smarty->assign('message', $message);
}
$smarty->assign('myitems', $myitems);
$smarty->assign('myitems_count', $myitems_count);
$smarty->assign('offset', $offset);
$smarty->assign('shoppees', $shoppees);
$smarty->assign('prospects', $prospects);
$smarty->assign('messages', $messages);
$smarty->assign('events', $events);
$smarty->assign('pending', $pending);
$smarty->assign('approval', $approval);
$smarty->assign('userid', $userid);
$smarty->assign('isadmin', $_SESSION['admin']);
$smarty->assign('opt', $OPT);
$smarty->display('home.tpl');
?>
