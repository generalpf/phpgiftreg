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
	$query = "SELECT title, rendered FROM {$OPT["table_prefix"]}ranks WHERE ranking = " . $_GET["ranking"];
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$title = htmlspecialchars($row["title"]);
		$rendered = htmlspecialchars($row["rendered"]);
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

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Manage Ranks</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body>
<?php
if (isset($message)) {
    echo "<span class=\"message\">" . $message . "</span>";
}

$query = "SELECT ranking, title, rendered, rankorder " .
			"FROM {$OPT["table_prefix"]}ranks ";
$query .= " ORDER BY rankorder";
$ranks = mysql_query($query) or die("Could not query: " . mysql_error());
?>
<p>
	<table class="partbox" cellspacing="0" cellpadding="2">
		<tr class="partboxtitle">
			<td colspan="4" align="center">Ranks</td>
		</tr>
		<tr>
			<th class="colheader">Title</th>
			<th class="colheader">Rendered HTML</th>
			<th class="colheader">Rank Order</th>
		</tr>
		<?php
		$i = 0;
		while ($row = mysql_fetch_array($ranks,MYSQL_ASSOC)) {
			?>
			<tr class="<?php echo (!($i++ % 2)) ? "evenrow" : "oddrow" ?>">
				<td><?php echo htmlspecialchars($row["title"]); ?></td>
				<td><?php echo $row["rendered"]; ?></td>
				<td><?php echo $row["rankorder"]; ?></td>
				<td align="right">
					<a href="ranks.php?action=edit&ranking=<?php echo $row["ranking"]; ?>">Edit</a>
					/
					<a href="ranks.php?action=delete&ranking=<?php echo $row["ranking"]; ?>">Delete</a>
					/
					<a href="ranks.php?action=promote&ranking=<?php echo $row["ranking"]; ?>&rankorder=<?php echo $row["rankorder"]; ?>">Promote</a>
					/
					<a href="ranks.php?action=demote&ranking=<?php echo $row["ranking"]; ?>&rankorder=<?php echo $row["rankorder"]; ?>">Demote</a>
				</td>
			</tr>
			<?php
		}
		mysql_free_result($ranks);
		?>
	</table>
</p>
<p>
	<a href="ranks.php">Add a new rank</a> / <a href="index.php">Back to main</a>
</p>
<form name="rank" method="get" action="ranks.php">	
	<?php 
	if ($action == "edit" || (isset($haserror) && $action == "update")) {
		?>
		<input type="hidden" name="ranking" value="<?php echo $_GET["ranking"]; ?>">
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
				<td align="center" colspan="2"><?php echo ($action == "edit" ? "Edit Rank '" . $title . "'" : "Add New Rank"); ?></td>
			</tr>
			<TR valign="top">
				<TD>Title</TD>
				<TD>
					<input name="title" type="text" value="<?php echo $title; ?>" maxlength="255" size="50"/>
					<?php
					if (isset($title_error)) {
						?><br /><font color="red"><?php echo $title_error; ?></font><?php
					}
					?>
				</TD>
			</TR>
			<TR valign="top">
				<TD>HTML</TD>
				<TD>
					<textarea name="rendered" rows="4" cols="40"><?php echo $rendered; ?></textarea>
					<?php
					if (isset($rendered_error)) {
						?><br /><font color="red"><?php echo $rendered_error; ?></font><?php
					}
					?>
				</TD>
			</TR>
		</TABLE>
	</div>
	<P>
		<div align="center">
			<input type="submit" value="<?php if ($action == "" || $action == "insert") echo "Add"; else echo "Update"; ?>"/>
			<input type="button" value="Cancel" onClick="document.location.href='ranks.php';">
		</div>
	</P>
</form>
<table border="1">
</table>
</body>
</html>
