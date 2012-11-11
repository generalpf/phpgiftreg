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

// for security, let's make sure that if an eventid was passed in, it belongs
// to $userid (or is a system event and the user is an admin).
// all operations on this page should only be performed by the event's owner.
if (isset($_GET["eventid"]) && $_GET["eventid"] != "") {
	$query = "SELECT * FROM {$OPT["table_prefix"]}events WHERE eventid = " . $_GET["eventid"] . " AND ";
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
	$eventdate = mktime(0,0,0,$_GET["month"],$_GET["day"],$_GET["century"] . $_GET["year"]);	// may not assemble a good date.
	$recurring = (strtoupper($_GET["recurring"]) == "ON" ? 1 : 0);
	$systemevent = (strtoupper($_GET["system"]) == "ON" ? 1 : 0);
	if (!get_magic_quotes_gpc())
		$description = addslashes($description);
		
	$haserror = false;
	if ($description == "") {
		$haserror = true;
		$description_error = "A description is required.";
	}
	if ($eventdate < 0) {
		$haserror = true;
		$eventdate_error = "Date is out of range for this server.";
	}
	if (!checkdate($_GET["month"],$_GET["day"],$_GET["century"] . $_GET["year"])) {
		$haserror = true;
		$eventdate_error = "Invalid date.  (Check that the day of the month exists.)";
	}
}

if ($action == "delete") {
	$query = "DELETE FROM {$OPT["table_prefix"]}events WHERE eventid = " . $_GET["eventid"];
	mysql_query($query) or die("Could not query: " . mysql_error());
	header("Location: " . getFullPath("event.php?message=Event+deleted."));
	exit;
}
else if ($action == "edit") {
	$query = "SELECT description, eventdate, recurring, userid FROM {$OPT["table_prefix"]}events WHERE eventid = " . $_GET["eventid"];
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$description = htmlspecialchars($row["description"]);
		$eventdate = strtotime($row["eventdate"]);
		$recurring = $row["recurring"];
		$systemevent = ($row["userid"] == "");
	}
	mysql_free_result($rs);
}
else if ($action == "") {
	$description = "";
	$eventdate = time();
	$recurring = 1;
	$systemevent = 0;
}
else if ($action == "insert") {
	if (!$haserror) {
		$query = "INSERT INTO {$OPT["table_prefix"]}events(userid,description,eventdate,recurring) " .
					"VALUES(" . ($systemevent ? "NULL" : $userid) . ",'$description','" . strftime("%Y-%m-%d",$eventdate) . "',$recurring)";
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
				"eventdate = '" . strftime("%Y-%m-%d",$eventdate) . "', " .
				"recurring = $recurring " . 
				"WHERE eventid = " . $_GET["eventid"];
		mysql_query($query) or die("Could not query: " . mysql_error());
		header("Location: " . getFullPath("event.php?message=Event+updated."));
		exit;		
	}
}
else {
	echo "Unknown verb.";
	exit;
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Manage Events</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body>
<?php
if (isset($message)) {
    echo "<span class=\"message\">" . $message . "</span>";
}

$query = "SELECT eventid, userid, description, eventdate, recurring " .
			"FROM {$OPT["table_prefix"]}events " .
			"WHERE userid = $userid";
if ($_SESSION["admin"] == 1)
	$query .= " OR userid IS NULL";		// add in system events
$query .= " ORDER BY userid, eventdate";
$events = mysql_query($query) or die("Could not query: " . mysql_error());

if ($OPT["show_helptext"]) {
	?>
	<p class="helptext">
		Here you can specify events <strong>of your own</strong>, like your birthday or your anniversary.  When the event occurs within <?php echo $OPT["event_threshold"]; ?> days, an event reminder will appear in the display of everyone who shops for you.
		<?php if ($_SESSION["admin"] == 1) echo "<strong>System events</strong> are events which belong to no one -- like Christmas -- and will appear on everyone's display."; ?>
		Marking an item as <strong>Recurring yearly</strong> will cause them to show up year after year.
	</p>
	<?php
}
?>
<p>
	<table class="partbox" width="100%" cellspacing="0">
		<tr class="partboxtitle">
			<td colspan="<?php echo 4 + $_SESSION["admin"]; ?>" align="center">Events</td>
		</tr>
		<tr>
			<th class="colheader">Event date</th>
			<th class="colheader">Description</th>
			<th class="colheader">Recurring?</th>
			<?php 
			if ($_SESSION["admin"] == 1) {
				?>
				<th class="colheader">System event?</th>
				<?php
			}
			?>
			<th>&nbsp;</th>
		</tr>
		<?php
		$i = 0;
		while ($row = mysql_fetch_array($events,MYSQL_ASSOC)) {
			?>
			<tr class="<?php echo (!($i++ % 2)) ? "evenrow" : "oddrow" ?>">
				<td><?php echo strftime("%B %d, %Y",strtotime($row["eventdate"])); ?></td>
				<td><?php echo htmlspecialchars($row["description"]); ?></td>
				<td><?php echo ($row["recurring"] == 1 ? "Yes" : "No"); ?></td>
				<?php
				if ($_SESSION["admin"] == 1) {
					?>
					<td><?php echo ($row["userid"] == "" ? "Yes" : "No"); ?></td>
					<?php
				}
				?>
				<td align="right">
					<a href="event.php?action=edit&eventid=<?php echo $row["eventid"]; ?>">Edit</a>
					/
					<a href="event.php?action=delete&eventid=<?php echo $row["eventid"]; ?>">Delete</a>
				</td>
			</tr>
			<?php
		}
		mysql_free_result($events);
		?>
	</table>
</p>
<p>
	<a href="event.php">Add a new event</a> / <a href="index.php">Back to main</a>
</p>
<form name="event" method="get" action="event.php">	
	<?php 
	if ($action == "edit" || (isset($haserror) && $action == "update")) {
		?>
		<input type="hidden" name="eventid" value="<?php echo $_GET["eventid"]; ?>">
		<input type="hidden" name="action" value="update">
		<?php
	}
	else if ($action == "" || (isset($haserror) && $action == "insert")) {
		?>
		<input type="hidden" name="action" value="insert">
		<?php
	}
	?>
	<div align="center">
		<TABLE class="partbox">
			<tr class="partboxtitle">
				<td align="center" colspan="2"><?php echo ($action == "edit" ? "Edit Event '" . $description . "'" : "Add New Event"); ?></td>
			</tr>
			<TR valign="top">
				<TD>Description</TD>
				<TD>
					<input name="description" type="text" value="<?php echo htmlspecialchars(stripslashes($description)); ?>" maxlength="255" size="50"/>
					<?php
					if (isset($description_error)) {
						?><br /><font color="red"><?php echo $description_error ?></font><?php
					}
					?>
				</TD>
			</TR>
			<?php
			$dateparts = getdate($eventdate);
			$eventmonth = $dateparts["mon"];
			$eventday = $dateparts["mday"];
			$eventyear = $dateparts["year"];
			?>
			<tr valign="top">
				<td>Date</td>
				<td>
					<select name="month">
						<?php
						for ($i = 1; $i <= 12; $i++) {
							?>
							<option value="<?php echo $i; ?>" <?php if ((!isset($eventdate_error) && ($i == $eventmonth)) || (isset($eventdate_error) && ($i == $_GET["month"]))) echo "SELECTED"; ?>><?php echo strftime("%B",mktime(0,0,0,$i,1,2000)); ?></option>
							<?php
						}
						?>
					</select>
					<select name="day">
						<?php
						for ($i = 1; $i <= 31; $i++) {
							?>
							<option value="<?php echo $i; ?>" <?php if ((!isset($eventdate_error) && ($i == $eventday)) || (isset($eventdate_error) && ($i == $_GET["day"]))) echo "SELECTED"; ?>><?php echo str_pad($i,2,"0",STR_PAD_LEFT); ?></option>
							<?php
						}
						?>
					</select>
					,
					<select name="century">
						<option value="19" <?php if ((!isset($eventdate_error) && $eventyear < 2000) || (isset($eventdate_error) && $_GET["century"] == "19")) echo "SELECTED"; ?>>19</option>
						<option value="20" <?php if ((!isset($eventdate_error) && $eventyear >= 2000) || (isset($eventdate_error) && $_GET["century"] == "20")) echo "SELECTED"; ?>>20</option>
					</select>
					<select name="year">
						<?php
						for ($i = 0; $i < 100; $i++) {
							?>
							<option value="<?php echo str_pad($i,2,"0",STR_PAD_LEFT); ?>" <?php if (!isset($eventdate_error) && ($eventyear % 100 == $i) || (isset($eventdate_error) && $i == $_GET["year"])) echo "SELECTED"; ?>><?php echo str_pad($i,2,"0",STR_PAD_LEFT); ?></option>
							<?php
						}
						?>
					</select>
					<?php
	 				if (isset($eventdate_error)) {
						?><br /><font color="red"><?php echo $eventdate_error ?></font><?php
					}
					?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="recurring" <?php if ($recurring == 1) echo "CHECKED"; ?>>Recurring yearly
					&nbsp;
					<?php
					if ($_SESSION["admin"] == 1) {
						?>
						<input type="checkbox" name="system" <?php if ($systemevent == 1) echo "CHECKED"; ?>>System event
						<?php
					}
					else {
						echo "&nbsp;";
					}
					?>
				</td>
			</tr>
		</TABLE>
	</div>
	<P>
		<div align="center">
			<input type="submit" value="<?php if ($action == "" || $action == "insert") echo "Add"; else echo "Update"; ?>"/>
			<input type="button" value="Cancel" onClick="document.location.href='event.php';">
		</div>
	</P>
</form>
</body>
</html>
