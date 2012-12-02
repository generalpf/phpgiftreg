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

if (isset($_GET["eventid"])) {
	$eventid = $_GET["eventid"];
}

// for security, let's make sure that if an eventid was passed in, it belongs
// to $userid (or is a system event and the user is an admin).
// all operations on this page should only be performed by the event's owner.
if (isset($eventid)) {
	try {
		$query = "SELECT * FROM {$opt["table_prefix"]}events WHERE eventid = ? AND ";
		if ($_SESSION["admin"] == 1)
			$query .= "(userid = ? OR userid IS NULL)";
		else
			$query .= "userid = ?";
		$stmt = $smarty->dbh()->prepare($query);
		$stmt->bindParam(1, $eventid, PDO::PARAM_INT);
		$stmt->bindParam(2, $userid, PDO::PARAM_INT);

		$stmt->execute();
		if (!$stmt->fetch())
			die("Nice try! (That's not your event.)");
	}
	catch (PDOException $e) {
		die("sql exception: " . $e->getMessage());
	}
}

$action = isset($_GET["action"]) ? $_GET["action"] : "";

if ($action == "insert" || $action == "update") {
	/* validate the data. */
	$description = trim($_GET["description"]);
	try {
		$eventdate = new DateTime($_GET["eventdate"]);
	}
	catch (Exception $e) {
		$eventdate = FALSE;
	}
	$recurring = (strtoupper($_GET["recurring"]) == "ON" ? 1 : 0);
	$systemevent = (strtoupper($_GET["systemevent"]) == "ON" ? 1 : 0);
		
	$haserror = false;
	if ($description == "") {
		$haserror = true;
		$description_error = "A description is required.";
	}
	if ($eventdate == FALSE) {
		$haserror = true;
		$eventdate_error = "Date is out of range for this server.";
	}
}

if ($action == "delete") {
	try {
		$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}events WHERE eventid = ?");
		$stmt->bindParam(1, $eventid, PDO::PARAM_INT);

		$stmt->execute();

		header("Location: " . getFullPath("event.php?message=Event+deleted."));
		exit;
	}
	catch (PDOException $e) {
		die("sql exception: " . $e->getMessage());
	}
}
else if ($action == "edit") {
	try {
		$stmt = $smarty->dbh()->prepare("SELECT description, eventdate, recurring, userid FROM {$opt["table_prefix"]}events WHERE eventid = ?");
		$stmt->bindParam(1, $eventid, PDO::PARAM_INT);
		
		$stmt->execute();

		// we know this will work, see above.
		$row = $stmt->fetch();
		$description = $row["description"];
		$eventdate = new DateTime($row["eventdate"]);
		$recurring = $row["recurring"];
		$systemevent = ($row["userid"] == "");
	}
	catch (PDOException $e) {
		die("sql exception: " . $e->getMessage());
	}
}
else if ($action == "") {
	$description = "";
	$eventdate = new DateTime();
	$recurring = 1;
	$systemevent = 0;
}
else if ($action == "insert") {
	if (!$haserror) {
		try {
			$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}events(userid,description,eventdate,recurring) VALUES(?, ?, ?, ?)");
			$stmt->bindValue(1, $systemevent ? NULL : $userid, PDO::PARAM_BOOL);
			$stmt->bindParam(2, $description, PDO::PARAM_STR);
			$stmt->bindValue(3, $eventdate->format("Y-m-d"), PDO::PARAM_STR);
			$stmt->bindParam(4, $recurring, PDO::PARAM_BOOL);

			$stmt->execute();
		
			header("Location: " . getFullPath("event.php?message=Event+added."));
			exit;
		}
		catch (PDOException $e) {
			die("sql exception: " . $e->getMessage());
		}
	}
}
else if ($action == "update") {
	if (!$haserror) {
		try {
			$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}events SET " .
				"userid = ?, " .
				"description = ?, " .
				"eventdate = ?, " .
				"recurring = ? " . 
				"WHERE eventid = ?");
			$stmt->bindValue(1, $systemevent ? NULL : $userid, PDO::PARAM_BOOL);
			$stmt->bindParam(2, $description, PDO::PARAM_STR);
			$stmt->bindValue(3, $eventdate->format("Y-m-d"), PDO::PARAM_STR);
			$stmt->bindParam(4, $recurring, PDO::PARAM_BOOL);
			$stmt->bindParam(5, $eventid, PDO::PARAM_INT);

			$stmt->execute();

			header("Location: " . getFullPath("event.php?message=Event+updated."));
			exit;
		}
		catch (PDOException $e) {
			die("sql exception: " . $e->getMessage());
		}
	}
}
else {
	die("Unknown verb.");
}

try {
	$query = "SELECT eventid, userid, description, eventdate, recurring " .
			"FROM {$opt["table_prefix"]}events " .
			"WHERE userid = ?";
	if ($_SESSION["admin"] == 1)
		$query .= " OR userid IS NULL";		// add in system events
	$query .= " ORDER BY userid, eventdate";
	$stmt = $smarty->dbh()->prepare($query);
	$stmt->bindParam(1, $userid, PDO::PARAM_INT);

	$stmt->execute();

	$events = array();
	while ($row = $stmt->fetch()) {
		try {
			$eventDateTime = new DateTime($row['eventdate']);
		}
		catch (Exception $e) {
			die("There was an error with an event with datetime " . $row['eventdate']);
		}
		$row['eventdate'] = $eventDateTime->format($opt["date_format"]);
		$events[] = $row;
	}

	if (isset($message)) {
		$smarty->assign('message', $message);
	}
	$smarty->assign('action', $action);
	$smarty->assign('haserror', $haserror);
	$smarty->assign('events', $events);
	$smarty->assign('eventdate', $eventdate->format($opt["date_format"]));
	if (isset($eventdate_error)) {
		$smarty->assign('eventdate_error', $eventdate_error);
	}
	$smarty->assign('description', $description);
	if (isset($description_error)) {
		$smarty->assign('description_error', $description_error);
	}
	$smarty->assign('recurring', $recurring);
	$smarty->assign('systemevent', $systemevent);
	if (isset($eventid)) {
		$smarty->assign('eventid', $eventid);
	}
	$smarty->assign('userid', $userid);
	$smarty->display('event.tpl');
}
catch (PDOException $e) {
	die("sql exception: " . $e->getMessage());
}
?>
