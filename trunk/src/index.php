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

if (!empty($_GET["message"])) {
	$message = $_GET["message"];
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
		$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}messages SET isread = 1 WHERE messageid = ?");
		$stmt->bindValue(1, (int) $_GET["messageid"], PDO::PARAM_INT);
		$stmt->execute();
	}
	else if ($action == "approve") {
		$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}shoppers SET pending = 0 WHERE shopper = ? AND mayshopfor = ?");
		$stmt->bindValue(1, (int) $_GET["shopper"], PDO::PARAM_INT);
		$stmt->bindParam(2, $userid, PDO::PARAM_INT);
		$stmt->execute();
		sendMessage($userid,(int) $_GET["shopper"],$_SESSION["fullname"] . " has approved your request to shop for him/her.", $smarty->dbh(), $smarty->opt());
	}
	else if ($action == "decline") {
		$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}shoppers WHERE shopper = ? AND mayshopfor = ?"); 
		$stmt->bindValue(1, (int) $_GET["shopper"], PDO::PARAM_INT);
		$stmt->bindParam(2, $userid, PDO::PARAM_INT);
		$stmt->execute();
		sendMessage($userid,(int) $_GET["shopper"],$_SESSION["fullname"] . " has declined your request to shop for him/her.", $smarty->dbh(), $smarty->opt());
	}
	else if ($action == "request") {
		$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}shoppers(shopper,mayshopfor,pending) VALUES(?, ?, ?)");
		$stmt->bindParam(1, $userid, PDO::PARAM_INT);
		$stmt->bindValue(2, (int) $_GET["shopfor"], PDO::PARAM_INT);
		$stmt->bindValue(3, $opt["shop_requires_approval"], PDO::PARAM_BOOL);
		$stmt->execute();
		if ($opt["shop_requires_approval"]) {
			sendMessage($userid,(int) $_GET["shopfor"],$_SESSION["fullname"] . " has requested to shop for you.  Please approve or decline this request.", $smarty->dbh(), $smarty->opt());
		}
	}
	else if ($action == "cancel") {
		// this works for either cancelling a request or "unshopping" for a user.
		$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}shoppers WHERE shopper = ? AND mayshopfor = ?");
		$stmt->bindParam(1, $userid, PDO::PARAM_INT);
		$stmt->bindValue(2, (int) $_GET["shopfor"], PDO::PARAM_INT);
		$stmt->execute();
	}
	else if ($action == "subscribe") {
		// ensure the current user can shop for that user first.
		$stmt = $smarty->dbh()->prepare("SELECT pending FROM shoppers WHERE shopper = ? AND mayshopfor = ?");
		$stmt->bindParam(1, $userid, PDO::PARAM_INT);
		$stmt->bindValue(2, (int) $_GET["shoppee"], PDO::PARAM_INT);
		$stmt->execute();
		if ($row = $stmt->fetch()) {
			if ($row["pending"]) {
				die("You aren't allowed to shop for that user yet.");
			}
		}
		else {
			die("You aren't allowed to shop for that user.");
		}

		$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}subscriptions(publisher, subscriber) VALUES(?, ?)");
		$stmt->bindValue(1, (int) $_GET["shoppee"], PDO::PARAM_INT);
		$stmt->bindParam(2, $userid, PDO::PARAM_INT);
		$stmt->execute();
	}
	else if ($action == "unsubscribe") {
		$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}subscriptions WHERE publisher = ? AND subscriber = ?");
		$stmt->bindValue(1, (int) $_GET["shoppee"], PDO::PARAM_INT);
		$stmt->bindParam(2, $userid, PDO::PARAM_INT);
		$stmt->execute();
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
$stmt = $smarty->dbh()->prepare("SELECT itemid, description, c.category, price, url, rendered, comment, image_filename FROM {$opt["table_prefix"]}items i LEFT OUTER JOIN {$opt["table_prefix"]}categories c ON c.categoryid = i.category LEFT OUTER JOIN {$opt["table_prefix"]}ranks r ON r.ranking = i.ranking WHERE userid = ? ORDER BY " . $sortby);
$stmt->bindParam(1, $userid, PDO::PARAM_INT);
$stmt->execute();
$myitems_count = 0;
$myitems = array();
for ($i = 0; $i < $offset; $i++, ++$myitems_count) {
	$row = $stmt->fetch();
}
$i = 0;
while ($i++ < $opt["items_per_page"] && $row = $stmt->fetch()) {
	$row['price'] = formatPrice($row['price'], $opt);
	$myitems[] = $row;
	++$myitems_count;
}
while ($stmt->fetch()) {
	++$myitems_count;
}

$stmt = $smarty->dbh()->prepare("SELECT u.userid, u.fullname, u.comment, u.list_stamp, ISNULL(sub.subscriber) AS is_unsubscribed, COUNT(i.itemid) AS itemcount " .
			"FROM {$opt["table_prefix"]}shoppers s " .
			"INNER JOIN {$opt["table_prefix"]}users u ON u.userid = s.mayshopfor " .
			"LEFT OUTER JOIN {$opt["table_prefix"]}items i ON u.userid = i.userid " .
			"LEFT OUTER JOIN {$opt["table_prefix"]}subscriptions sub ON sub.publisher = u.userid AND sub.subscriber = ? " .
			"WHERE s.shopper = ? " .
				"AND pending = 0 " .
			"GROUP BY u.userid, u.fullname, u.list_stamp " .
			"ORDER BY u.fullname");
$stmt->bindParam(1, $userid, PDO::PARAM_INT);
$stmt->bindParam(2, $userid, PDO::PARAM_INT);
$stmt->execute();
$shoppees = array();
while ($row = $stmt->fetch()) {
	if ($row['list_stamp'] == 0) {
		$row['list_stamp'] = '-';
	}
	else {
		$listStampDate = new DateTime($row['list_stamp']);
		$row['list_stamp'] = $listStampDate->format($opt["date_format"]);
	}
	$shoppees[] = $row;
}

$stmt = $smarty->dbh()->prepare("SELECT DISTINCT u.userid, u.fullname, s.pending " .
			"FROM {$opt["table_prefix"]}memberships mymem " .
			"INNER JOIN {$opt["table_prefix"]}memberships others " .
				"ON others.familyid = mymem.familyid AND others.userid <> ? " .
			"INNER JOIN {$opt["table_prefix"]}users u " .
				"ON u.userid = others.userid " .
			"LEFT OUTER JOIN {$opt["table_prefix"]}shoppers s " .
				"ON s.mayshopfor = others.userid AND s.shopper = ? " .
			"WHERE mymem.userid = ? " .
				"AND (s.pending IS NULL OR s.pending = 1) " .
				"AND u.approved = 1 " .
			"ORDER BY u.fullname");
$stmt->bindParam(1, $userid, PDO::PARAM_INT);
$stmt->bindParam(2, $userid, PDO::PARAM_INT);
$stmt->bindParam(3, $userid, PDO::PARAM_INT);
$stmt->execute();
$prospects = array();
while ($row = $stmt->fetch()) {
	$prospects[] = $row;
}
					
$stmt = $smarty->dbh()->prepare("SELECT messageid, u.fullname, message, created " .
			"FROM {$opt["table_prefix"]}messages m " .
			"INNER JOIN {$opt["table_prefix"]}users u ON u.userid = m.sender " .
			"WHERE m.recipient = ? " .
				"AND m.isread = 0 " .
			"ORDER BY created DESC");
$stmt->bindParam(1, $userid, PDO::PARAM_INT);
$stmt->execute();
$messages = array();
while ($row = $stmt->fetch()) {
	$createdDateTime = new DateTime($row['created']);
	$row['created'] = $createdDateTime->format($opt["date_format"]);
	$messages[] = $row;
}

$query = "SELECT CONCAT(YEAR(CURDATE()),'-',MONTH(eventdate),'-',DAYOFMONTH(eventdate)) AS DateThisYear, " .
				"TO_DAYS(CONCAT(YEAR(CURDATE()),'-',MONTH(eventdate),'-',DAYOFMONTH(eventdate))) AS ToDaysDateThisYear, " .
				"CONCAT(YEAR(CURDATE()) + 1,'-',MONTH(eventdate),'-',DAYOFMONTH(eventdate)) AS DateNextYear, " .
				"TO_DAYS(CONCAT(YEAR(CURDATE()) + 1,'-',MONTH(eventdate),'-',DAYOFMONTH(eventdate))) AS ToDaysDateNextYear, " .
				"TO_DAYS(CURDATE()) AS ToDaysToday, " .
				"TO_DAYS(eventdate) AS ToDaysEventDate, " .
				"e.userid, u.fullname, description, eventdate, recurring, s.pending " .
			"FROM {$opt["table_prefix"]}events e " .
			"LEFT OUTER JOIN {$opt["table_prefix"]}users u ON u.userid = e.userid " .
			"LEFT OUTER JOIN {$opt["table_prefix"]}shoppers s ON s.mayshopfor = e.userid AND s.shopper = ? ";
if ($opt["show_own_events"])
	$query .= "WHERE (pending = 0 OR pending IS NULL)";
else
	$query .= "WHERE (e.userid <> ? OR e.userid IS NULL) AND (pending = 0 OR pending IS NULL)";
$query .= "ORDER BY u.fullname";
$stmt = $smarty->dbh()->prepare($query);
$stmt->bindParam(1, $userid, PDO::PARAM_INT);
if (!$opt["show_own_events"])
	$stmt->bindParam(2, $userid, PDO::PARAM_INT);
$stmt->execute();
$events = array();
while ($row = $stmt->fetch()) {
	$event_fullname = $row["fullname"];
	$days_left = -1;
	if (!$row["recurring"] && (($row["ToDaysEventDate"] - $row["ToDaysToday"]) >= 0) && (($row["ToDaysEventDate"] - $row["ToDaysToday"]) <= $opt["event_threshold"])) {
		$days_left = $row["ToDaysEventDate"] - $row["ToDaysToday"];
		$event_date = new DateTime($row["eventdate"]);
	}
	else if ($row["recurring"] && (($row["ToDaysDateThisYear"] - $row["ToDaysToday"]) >= 0) && (($row["ToDaysDateThisYear"] - $row["ToDaysToday"]) <= $opt["event_threshold"])) {
		$days_left = $row["ToDaysDateThisYear"] - $row["ToDaysToday"];
		$event_date = new DateTime($row["DateThisYear"]);
	}
	else if ($row["recurring"] && (($row["ToDaysDateNextYear"] - $row["ToDaysToday"]) >= 0) && (($row["ToDaysDateNextYear"] - $row["ToDaysToday"]) <= $opt["event_threshold"])) {
		$days_left = $row["ToDaysDateNextYear"] - $row["ToDaysToday"];
		$event_date = new DateTime($row["DateNextYear"]);
	}
	if ($days_left >= 0) {
		$thisevent = array(
			'fullname' => $event_fullname,
			'eventname' => $row['description'],
			'daysleft' => $days_left,
			'date' => $event_date->format($opt["date_format"])
		);
		$events[] = $thisevent;
	}
}
					
function compareEvents($a, $b) {
	if ($a["daysleft"] == $b["daysleft"])
		return 0;
	else
		return ($a["daysleft"] > $b["daysleft"]) ? 1 : -1;
}
					
// i couldn't figure out another way to do this, so here goes.
// sort() wanted to sort based on the array keys, which were 0..n - 1, so that was useless.
usort($events, "compareEvents");

if ($opt["shop_requires_approval"]) {
	$query = "SELECT u.userid, u.fullname " .
				"FROM {$opt["table_prefix"]}shoppers s " .
				"INNER JOIN {$opt["table_prefix"]}users u ON u.userid = s.shopper " .
				"WHERE s.mayshopfor = ? " .
					"AND s.pending = 1 " .
				"ORDER BY u.fullname";
	$stmt = $smarty->dbh()->prepare($query);
	$stmt->bindParam(1, $userid, PDO::PARAM_INT);
	$stmt->execute();
	$pending = array();
	while ($row = $stmt->fetch()) {
		$pending[] = $row;
	}
}

if (($_SESSION["admin"] == 1) && $opt["newuser_requires_approval"]) {
	$query = "SELECT userid, fullname, email, approved, initialfamilyid, familyname " .
				"FROM {$opt["table_prefix"]}users u " .
				"LEFT OUTER JOIN {$opt["table_prefix"]}families f ON f.familyid = u.initialfamilyid " .
				"WHERE approved = 0 " . 
				"ORDER BY fullname";
	$stmt = $smarty->dbh()->prepare($query);
	$stmt->execute();
	$approval = array();
	while ($row = $stmt->fetch()) {
		$approval[] = $row;
	}
}

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
if (isset($pending)) {
	$smarty->assign('pending', $pending);
}
if (isset($approval)) {
	$smarty->assign('approval', $approval);
}
$smarty->assign('userid', $userid);
$smarty->display('home.tpl');
?>
