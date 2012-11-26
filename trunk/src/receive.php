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

$action = (!empty($_GET["action"]) ? $_GET["action"] : "");
$itemid = (int) $_GET["itemid"];

// get details. is it our item? is this a single-quantity item?
try {
	$stmt = $smarty->dbh()->prepare("SELECT userid, quantity FROM {$opt["table_prefix"]}items WHERE itemid = ?");
	$stmt->bindParam(1, $itemid, PDO::PARAM_INT);
	$stmt->execute();
	if ($row = $stmt->fetch()) {
		if ($row["userid"] != $userid)
			die("That's not your item!");

		$quantity = $row["quantity"];
	}
	else {
		die("Item does not exist.");
	}

	stampUser($userid, $smarty->dbh(), $smarty->opt());

	if ($quantity == 1) {
		/* just delete the alloc and the item and get out.
			yes, it's possible the item was RESERVED, not PURCHASED. */
		deleteImageForItem($itemid, $smarty->dbh(), $smarty->opt());

		$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}allocs WHERE itemid = ?");
		$stmt->bindParam(1, $itemid, PDO::PARAM_INT);
		$stmt->execute();

		$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}items WHERE itemid = ?");
		$stmt->bindParam(1, $itemid, PDO::PARAM_INT);
		$stmt->execute();

		header("Location: " . getFullPath("index.php?message=Item+marked+as+received."));
		exit;
	}
	else if ($action == "receive") {
		// $actual will be a negative number, so let's flip it.
		$actual = -adjustAllocQuantity($itemid, (int) $_GET["buyer"], 1, -1 * (int) $_GET["quantity"], $smarty->dbh(), $smarty->opt());
	
		if ($actual < (int) $_GET["quantity"]) {
			// $userid didn't have that many bought, so some might have been reserved.
			$actual += -adjustAllocQuantity($itemid,(int) $_GET["buyer"],0,-1 * ((int) $_GET["quantity"] - $actual), $smarty->dbh(), $smarty->opt());
		}
	
		if ($actual == $quantity) {
			// now they're all gone.
			deleteImageForItem($itemid, $smarty->dbh(), $smarty->opt());
			$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}items WHERE itemid = ?");
			$stmt->bindParam(1, $itemid, PDO::PARAM_INT);
			$stmt->execute();
		}
		else {
			// decrement the item's desired quantity.
			$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}items SET quantity = quantity - ? WHERE itemid = ?");
			$stmt->bindParam(1, $actual, PDO::PARAM_INT);
			$stmt->bindParam(2, $itemid, PDO::PARAM_INT);
			$stmt->execute();
		}
	
		header("Location: " . getFullPath("index.php?message=Item+marked+as+received."));
		exit;
	}

	$stmt = $smarty->dbh()->prepare("SELECT u.userid, u.fullname " .
			"FROM {$opt["table_prefix"]}shoppers s " .
			"INNER JOIN {$opt["table_prefix"]}users u ON u.userid = s.shopper " .
			"WHERE s.mayshopfor = ? " .
				"AND pending = 0 " .
			"ORDER BY u.fullname");
	$stmt->bindParam(1, $userid, PDO::PARAM_INT);
	$stmt->execute();
	$buyers = array();
	while ($row = $stmt->fetch()) {
		$buyers[] = $row;
	}

	$smarty->assign('buyers', $buyers);
	$smarty->assign('quantity', $quantity);
	$smarty->assign('itemid', $itemid);
	$smarty->assign('userid', $userid);
	$smarty->display('receive.tpl');
}
catch (PDOException $e) {
	die("sql exception: " . $e->getMessage());
}
?>
