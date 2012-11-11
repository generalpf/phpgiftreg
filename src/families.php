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
	$query = "SELECT familyname FROM {$OPT["table_prefix"]}families WHERE familyid = " . $_GET["familyid"];
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$familyname = htmlspecialchars($row["familyname"]);
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

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Manage Families</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body>
<?php
if (isset($message)) {
    echo "<span class=\"message\">" . $message . "</span>";
}

$query = "SELECT f.familyid, familyname, COUNT(userid) AS members " .
			"FROM {$OPT["table_prefix"]}families f " .
			"LEFT OUTER JOIN {$OPT["table_prefix"]}memberships m ON m.familyid = f.familyid " .
			"GROUP BY f.familyid " .
			"ORDER BY familyname";
$families = mysql_query($query) or die("Could not query: " . mysql_error());
if ($OPT["show_helptext"]) {
	?>
	<p class="helptext">
		Here you can specify families that will use your gift registry.  Members may belong to one or more family circles.<br />
		After adding a new family, click Edit to add members to it.
	</p>
	<?php
}
?>
<p>
	<table class="partbox" cellspacing="0" cellpadding="2">
		<tr class="partboxtitle">
			<td colspan="3" align="center">Families</td>
		</tr>
		<tr>
			<th class="colheader">Family</th>
			<th class="colheader"># Members</th>
			<th>&nbsp;</th>
		</tr>
		<?php
		$i = 0;
		while ($row = mysql_fetch_array($families,MYSQL_ASSOC)) {
			?>
			<tr class="<?php echo (!($i++ % 2)) ? "evenrow" : "oddrow" ?>">
				<td><?php echo htmlspecialchars($row["familyname"]); ?></td>
				<td align="right"><?php echo htmlspecialchars($row["members"]); ?></td>
				<td align="right">
					<a href="families.php?action=edit&familyid=<?php echo $row["familyid"]; ?>">Edit</a>
					/
					<a href="families.php?action=delete&familyid=<?php echo $row["familyid"]; ?>">Delete</a>
				</td>
			</tr>
			<?php
		}
		mysql_free_result($families);
		?>
	</table>
</p>
<p>
	<a href="families.php">Add a new family</a> / <a href="index.php">Back to main</a>
</p>
<form name="family" method="get" action="families.php">	
	<?php 
	if ($action == "edit" || (isset($haserror) && $action == "update")) {
		?>
		<input type="hidden" name="familyid" value="<?php echo $_GET["familyid"]; ?>">
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
				<td align="center" colspan="2"><?php echo ($action == "edit" ? "Edit Family '" . $familyname . "'" : "Add New Family"); ?></td>
			</tr>
			<TR valign="top">
				<TD>Family</TD>
				<TD>
					<input name="familyname" type="text" value="<?php echo $familyname; ?>" maxlength="255" size="50"/>
					<?php
					if (isset($familyname_error)) {
						?><br /><font color="red"><?php echo $familyname_error ?></font><?php
					}
					?>
				</TD>
			</TR>
			<tr>
				<td colspan="2">
					<div align="center">
						<input type="submit" value="<?php if ($action == "" || $action == "insert") echo "Add"; else echo "Update"; ?>"/>
						<input type="button" value="Cancel" onClick="document.location.href='families.php';">
					</div>
				</td>
			</tr>
		</table>
	</div>
</form>

<?php 
if ($action == "edit")
{
	?>
	<br />
	<form name="membership" method="get" action="families.php">	
		<input type="hidden" name="familyid" value="<?php echo $_GET["familyid"]; ?>">
		<input type="hidden" name="action" value="members">
		<div align="center">
			<TABLE class="partbox">
				<tr class="partboxtitle">
					<td align="center">Members of '<?php echo $familyname; ?>'</td>
				</tr>
				<tr>
					<td align="center">
						<?php
						$query = "SELECT u.userid, u.fullname, m.familyid FROM {$OPT["table_prefix"]}users u " .
									"LEFT OUTER JOIN {$OPT["table_prefix"]}memberships m ON m.userid = u.userid AND m.familyid = " . $_GET["familyid"] . " " .
									"ORDER BY u.fullname";
						$nonmembers = mysql_query($query) or die("Could not query: " . mysql_error());
						?>
						<p class="helptext">Hold CTRL while clicking to select multiple users.</p>
						<select name="members[]" size="10" multiple>
							<?php
							while ($row = mysql_fetch_array($nonmembers,MYSQL_ASSOC)) {
								echo "<option value=\"" . $row["userid"] . "\"";
								if ($row["familyid"] != "")
									echo " SELECTED";
								echo ">" . $row["fullname"] . "</value>\r\n";
							}
							mysql_free_result($nonmembers);
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<div align="center">
							<input type="submit" value="Save"/>
							<input type="button" value="Cancel" onClick="document.location.href='families.php';">
						</div>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
	?>
</form>
</body>
</html>
