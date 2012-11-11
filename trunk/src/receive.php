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

$action = (!empty($_GET["action"]) ? $_GET["action"] : "");
$itemid = (int) $_GET["itemid"];

// get details.  is this a single-quantity item?
$query = "SELECT quantity FROM {$OPT["table_prefix"]}items WHERE itemid = $itemid";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$row = mysql_fetch_array($rs,MYSQL_ASSOC);
if (!$row) die("Item does not exist.");
$quantity = $row["quantity"];
mysql_free_result($rs);

stampUser($userid);

if ($quantity == 1) {
	/* just delete the alloc and the item and get out.
		yes, it's possible the item was RESERVED, not PURCHASED. */
	deleteImageForItem($itemid);
	$query = "DELETE FROM {$OPT["table_prefix"]}allocs WHERE itemid = $itemid";
	mysql_query($query) or die("Could not query: " . mysql_error());
	$query = "DELETE FROM {$OPT["table_prefix"]}items WHERE itemid = $itemid";
	mysql_query($query) or die("Could not query: " . mysql_error());
	header("Location: " . getFullPath("index.php?message=Item+marked+as+received."));
	exit;
}
else if ($action == "receive") {
	// $actual will be a negative number, so let's flip it.
	$actual = -adjustAllocQuantity($itemid,(int) $_GET["buyer"],1,-1 * (int) $_GET["quantity"]);
	
	if ($actual < (int) $_GET["quantity"]) {
		// $userid didn't have that many bought, so some might have been reserved.
		$actual += -adjustAllocQuantity($itemid,(int) $_GET["buyer"],0,-1 * ((int) $_GET["quantity"] - $actual));
	}
	
	if ($actual == $quantity) {
		// now they're all gone.
		deleteImageForItem($itemid);
		$query = "DELETE FROM {$OPT["table_prefix"]}items WHERE itemid = $itemid";
	}
	else {
		// decrement the item's desired quantity.
		$query = "UPDATE {$OPT["table_prefix"]}items SET quantity = quantity - $actual WHERE itemid = $itemid";
	}
	
	mysql_query($query) or die("Could not query: " . mysql_error());	

	header("Location: " . getFullPath("index.php?message=Item+marked+as+received."));
	exit;
}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Receive an Item</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body>
<form name="receiver" method="get" action="receive.php">
	<input type="hidden" name="action" value="receive">
	<input type="hidden" name="itemid" value="<?php echo $_GET["itemid"]; ?>">
	<div align="center">
		<TABLE class="partbox">
			<TR valign="top">
				<TD>
					<b>Select the buyer</b>
				</TD>
				<TD>
					<?php
					$query = "SELECT u.userid, u.fullname " .
									"FROM {$OPT["table_prefix"]}shoppers s " .
									"INNER JOIN {$OPT["table_prefix"]}users u ON u.userid = s.shopper " .
									"WHERE s.mayshopfor = " . $userid . " " .
									"AND pending = 0 " .
									"ORDER BY u.fullname";
					$buyers = mysql_query($query) or die("Could not query: " . mysql_error());
					?>
					<select name="buyer" size="<?php echo mysql_num_rows($buyers) ?>">
						<?php
						while ($row = mysql_fetch_array($buyers,MYSQL_ASSOC)) {
							?>
							<option value="<?php echo $row["userid"] ?>"><?php echo $row["fullname"] ?></option>
							<?php
						}
						?>
					</select>
				</TD>
			</TR>
			<tr valign="top">
				<td>
					<b>Quantity received<br />(maximum of <?php echo $quantity; ?>)</b>
				</td>
				<td>
					<input type="text" name="quantity" value="1" size="3" maxlength="3">
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<i>Once you have received all of an item, it will be deleted.</i>
				</td>
			</tr>
		</TABLE>
	</div>
	<p>
		<div align="center">
			<input type="submit" value="Receive Item"/>
			<input type="button" value="Cancel" onClick="document.location.href='index.php';">
		</div>
	</p>
</form>
</body>
</html>
