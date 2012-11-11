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
	$query = "SELECT category FROM {$OPT["table_prefix"]}categories WHERE categoryid = " . $_GET["categoryid"];
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$category = htmlspecialchars($row["category"]);
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

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Manage Categories</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body>
<?php
if (isset($message)) {
    echo "<span class=\"message\">" . $message . "</span>";
}

$query = "SELECT categoryid, category " .
			"FROM {$OPT["table_prefix"]}categories ";
$query .= " ORDER BY category";
$categories = mysql_query($query) or die("Could not query: " . mysql_error());
if ($OPT["show_helptext"]) {
	?>
	<p class="helptext">
		Here you can specify categories <strong>of your own</strong>, like &quot;Motorcycle stuff&quot; or &quot;Collectibles&quot;.  This will help you categorize your gifts.<br />
		Warning: deleting a category will uncategorize all items that used that category.
	</p>
	<?php
}
?>
<p>
	<table class="partbox" cellspacing="0" cellpadding="2">
		<tr class="partboxtitle">
			<td colspan="2" align="center">Categories</td>
		</tr>
		<tr>
			<th class="colheader">Category</th>
		</tr>
		<?php
		$i = 0;
		while ($row = mysql_fetch_array($categories,MYSQL_ASSOC)) {
			?>
			<tr class="<?php echo (!($i++ % 2)) ? "evenrow" : "oddrow" ?>">
				<td><?php echo htmlspecialchars($row["category"]); ?></td>
				<td align="right">
					<a href="categories.php?action=edit&categoryid=<?php echo $row["categoryid"]; ?>">Edit</a>
					/
					<a href="categories.php?action=delete&categoryid=<?php echo $row["categoryid"]; ?>">Delete</a>
				</td>
			</tr>
			<?php
		}
		mysql_free_result($categories);
		?>
	</table>
</p>
<p>
	<a href="categories.php">Add a new category</a> / <a href="index.php">Back to main</a>
</p>
<form name="category" method="get" action="categories.php">	
	<?php 
	if ($action == "edit" || (isset($haserror) && $action == "update")) {
		?>
		<input type="hidden" name="categoryid" value="<?php echo $_GET["categoryid"]; ?>">
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
				<td align="center" colspan="2"><?php echo ($action == "edit" ? "Edit Category '" . $category . "'" : "Add New Category"); ?></td>
			</tr>
			<TR valign="top">
				<TD>Category</TD>
				<TD>
					<input name="category" type="text" value="<?php echo $category; ?>" maxlength="255" size="50"/>
					<?php
					if (isset($category_error)) {
						?><br /><font color="red"><?php echo $category_error ?></font><?php
					}
					?>
				</TD>
			</TR>
		</TABLE>
	</div>
	<P>
		<div align="center">
			<input type="submit" value="<?php if ($action == "" || $action == "insert") echo "Add"; else echo "Update"; ?>"/>
			<input type="button" value="Cancel" onClick="document.location.href='categories.php';">
		</div>
	</P>
</form>
<table border="1">
</table>
</body>
</html>
