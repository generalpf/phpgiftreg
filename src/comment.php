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

if ($_GET["itemid"] != "")
	$rs = mysql_query("SELECT comment FROM {$OPT["table_prefix"]}items WHERE itemid = " . $_GET["itemid"]) or die("Could not query: " . mysql_error());
else if ($_GET["userid"] != "")
	$rs = mysql_query("SELECT comment FROM {$OPT["table_prefix"]}users WHERE userid = " . $_GET["userid"]) or die("Could not query: " . mysql_error());
else
	die("No comment required.");
$row = mysql_fetch_array($rs);
$comment = $row["comment"];
mysql_free_result($rs);

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Comment</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body class="comment">
<?php echo str_replace("\r\n","<br />",htmlspecialchars($comment)); ?>
</body>
</html>
