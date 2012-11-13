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

$query = "SELECT u.userid, u.fullname " .
			"FROM {$OPT["table_prefix"]}shoppers s " .
			"INNER JOIN {$OPT["table_prefix"]}users u ON u.userid = s.shopper " .
			"WHERE s.mayshopfor = " . $userid . " " .
				"AND pending = 0 " .
			"ORDER BY u.fullname";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$buyers = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$buyers[] = $row;
}
mysql_free_result($buyers);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('buyers', $buyers);
$smarty->assign('quantity', $quantity);
$smarty->assign('itemid', $itemid);
$smarty->assign('userid', $userid);
$smarty->assign('opt', $OPT);
$smarty->display('receive.tpl');
