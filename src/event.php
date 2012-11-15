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

if (isset($_GET["eventid"])) {
	$eventid = (int) $_GET["eventid"];
}

// for security, let's make sure that if an eventid was passed in, it belongs
// to $userid (or is a system event and the user is an admin).
// all operations on this page should only be performed by the event's owner.
if (isset($eventid)) {
	$query = "SELECT * FROM {$OPT["table_prefix"]}events WHERE eventid = $eventid AND ";
	if ($_SESSION["admin"] == 1)
		$query .= "(userid = " . $_SESSION["userid"] . " OR userid IS NULL)";
	else
		$query .= "userid = " . $_SESSION["userid"];
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if (mysql_num_rows($rs) == 0) {
		echo "Nice try! (That's not your event.)";
		exit;
	}
	mysql_free_result($rs);
}

$action = isset($_GET["action"]) ? $_GET["action"] : "";

if ($action == "insert" || $action == "update") {
	/* validate the data. */
	$description = trim($_GET["description"]);
	$eventdate = $_GET["eventdate"];
	$ts = strtotime($eventdate);
	$recurring = (strtoupper($_GET["recurring"]) == "ON" ? 1 : 0);
	$systemevent = (strtoupper($_GET["system"]) == "ON" ? 1 : 0);
	if (!get_magic_quotes_gpc())
		$description = addslashes($description);
		
	$haserror = false;
	if ($description == "") {
		$haserror = true;
		$description_error = "A description is required.";
	}
	if ($ts < 0 || $ts == FALSE) {
		$haserror = true;
		$eventdate_error = "Date is out of range for this server.";
	}
}

if ($action == "delete") {
	$query = "DELETE FROM {$OPT["table_prefix"]}events WHERE eventid = $eventid";
	mysql_query($query) or die("Could not query: " . mysql_error());
	header("Location: " . getFullPath("event.php?message=Event+deleted."));
	exit;
}
else if ($action == "edit") {
	$query = "SELECT description, eventdate, recurring, userid FROM {$OPT["table_prefix"]}events WHERE eventid = $eventid";
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$description = $row["description"];
		$eventdate = $row["eventdate"];
		$recurring = $row["recurring"];
		$systemevent = ($row["userid"] == "");
	}
	mysql_free_result($rs);
}
else if ($action == "") {
	$description = "";
	$eventdate = date("m/d/Y");
	$recurring = 1;
	$systemevent = 0;
}
else if ($action == "insert") {
	if (!$haserror) {
		$query = "INSERT INTO {$OPT["table_prefix"]}events(userid,description,eventdate,recurring) " .
					"VALUES(" . ($systemevent ? "NULL" : $userid) . ",'$description','" . strftime("%Y-%m-%d", $ts) . "',$recurring)";
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("event.php?message=Event+added."));
		exit;
	}
}
else if ($action == "update") {
	if (!$haserror) {
		$query = "UPDATE {$OPT["table_prefix"]}events SET " .
				"userid = " . ($systemevent ? "NULL" : $userid) . ", " .
				"description = '$description', " .
				"eventdate = '" . strftime("%Y-%m-%d", $ts) . "', " .
				"recurring = $recurring " . 
				"WHERE eventid = $eventid";
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("event.php?message=Event+updated."));
		exit;		
	}
}
else {
	echo "Unknown verb.";
	exit;
}

$query = "SELECT eventid, userid, description, eventdate, recurring " .
			"FROM {$OPT["table_prefix"]}events " .
			"WHERE userid = $userid";
if ($_SESSION["admin"] == 1)
	$query .= " OR userid IS NULL";		// add in system events
$query .= " ORDER BY userid, eventdate";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$events = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$row['eventdate'] = strftime("%m/%d/%Y", strtotime($row['eventdate']));
	$events[] = $row;
}
mysql_free_result($events);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
if (isset($message)) {
	$smarty->assign('message', $message);
}
$smarty->assign('action', $action);
$smarty->assign('haserror', $haserror);
$smarty->assign('events', $events);
$smarty->assign('eventdate', strftime("%m/%d/%Y", strtotime($eventdate)));
if (isset($eventdate_error)) {
	$smarty->assign('eventdate_error', $eventdate_error);
}
$smarty->assign('description', $description);
if (isset($description_error)) {
	$smarty->assign('description_error', $description_error);
}
$smarty->assign('recurring', $recurring);
$smarty->assign('systemevent', $systemevent);
$smarty->assign('eventid', $eventid);
$smarty->assign('userid', $userid);
$smarty->assign('isadmin', $_SESSION['admin']);
$smarty->assign('opt', $OPT);
$smarty->display('event.tpl');
?>
