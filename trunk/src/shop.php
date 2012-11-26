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

$action = "";
if (!empty($_GET["action"])) {
	$action = $_GET["action"];
	$itemid = (int) $_GET["itemid"];
	if ($action == "reserve") {
		adjustAllocQuantity($itemid,$userid,0,+1, $smarty->dbh(), $smarty->opt());
	}
	else if ($action == "purchase") {
		// decrement reserved.
		adjustAllocQuantity($itemid,$userid,0,-1, $smarty->dbh(), $smarty->opt());
		// increment purchased.
		adjustAllocQuantity($itemid,$userid,1,+1, $smarty->dbh(), $smarty->opt());
	}
	else if ($action == "return") {
		// increment reserved.
		adjustAllocQuantity($itemid,$userid,0,+1, $smarty->dbh(), $smarty->opt());
		// decrement purchased.
		adjustAllocQuantity($itemid,$userid,1,-1, $smarty->dbh(), $smarty->opt());
	}
	else if ($action == "release") {
		adjustAllocQuantity($itemid,$userid,0,-1, $smarty->dbh(), $smarty->opt());
	}
	else if ($action == "copy") {
		/* 
		can't do this because MySQL 3.x doesn't seem to support it (at least the version i was using).
		$query = "INSERT INTO items(userid,description,price,source,url,category) SELECT $userid, description, price, source, url, category FROM items WHERE itemid = " . $_GET["itemid"];
		*/
		/* TODO: copy the image too? */
		$stmt = $smarty->dbh()->prepare("SELECT userid, description, price, source, url, category, comment FROM {$opt["table_prefix"]}items WHERE itemid = ?");
		$stmt->bindParam(1, $itemid, PDO::PARAM_INT);
		$stmt->execute();
		if ($row = $stmt->fetch()) {
			$desc = $row["description"];
			$source = $row["source"];
			$url = $row["url"];
			$comment = $row["comment"];
			$price = (float) $row["price"];
			$cat = (int) $row["category"];
		
			$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}items(userid,description,price,source,url,comment,category,ranking,quantity) VALUES(?, ?, ?, ?, ?, ?, ?, 1, 1");
			$stmt->bindParam(1, $userid, PDO::PARAM_INT);
			$stmt->bindParam(2, $desc, PDO::PARAM_STR);
			$stmt->bindParam(3, $price);
			$stmt->bindParam(4, $source, PDO::PARAM_STR);
			$stmt->bindParam(5, $url, PDO::PARAM_STR);
			$stmt->bindParam(6, $comment, PDO::PARAM_STR);
			$stmt->bindParam(7,	$cat, PDO::PARAM_INT);
			$stmt->execute();
		
			stampUser($userid, $smarty->dbh(), $smarty->opt());

			$message = "Added '" . $desc . "' to your gift list.";
		}
	}
}

$shopfor = (int) $_GET["shopfor"];
if ($shopfor == $userid) {
	echo "Nice try! (You can't shop for yourself.)";
	exit;
}
$stmt = $smarty->dbh()->prepare("SELECT * FROM {$opt["table_prefix"]}shoppers WHERE shopper = ? AND mayshopfor = ? AND pending = 0");
$stmt->bindParam(1, $userid, PDO::PARAM_INT);
$stmt->bindParam(2, $shopfor, PDO::PARAM_INT);
$stmt->execute();
if (!($stmt->fetch())) {
	echo "Nice try! (You can't shop for someone who hasn't approved it.)";
	exit;
}

if (!isset($_GET["sort"])) {
	$sortby = "rankorder DESC, description";
}
else {
	$sort = $_GET["sort"];
	switch ($sort) {
		case "ranking":
			$sortby = "rankorder DESC, description";
			break;
		case "description":
			$sortby = "description";
			break;
		case "source":
			$sortby = "source, rankorder DESC, description";
			break;
		case "price":
			$sortby = "price, rankorder DESC, description";
			break;
		case "url":
			$sortby = "url, rankorder DESC, description";
			break;
		case "status":
			$sortby = "reservedid DESC, boughtid DESC, rankorder DESC, description";
			break;
		case "category":
			$sortby = "c.category, rankorder DESC, description";
			break;
		default:
			$sortby = "rankorder DESC, description";
	}
}

/* here's what we're going to do: we're going to pull back the shopping list along with any alloc record
	for those items with a quantity of 1.  if the item's quantity > 1 we'll query alloc when we
	get to that record.  the theory is that most items will have quantity = 1 so we'll make the least
	number of trips. */
$stmt = $smarty->dbh()->prepare("SELECT i.itemid, description, price, source, c.category, url, image_filename, " .
		"ub.fullname AS bfullname, ub.userid AS boughtid, " .
		"ur.fullname AS rfullname, ur.userid AS reservedid, " .
		"rendered, i.comment, i.quantity " .
	"FROM {$opt["table_prefix"]}items i " .
	"LEFT OUTER JOIN {$opt["table_prefix"]}categories c ON c.categoryid = i.category " .
	"LEFT OUTER JOIN {$opt["table_prefix"]}ranks r ON r.ranking = i.ranking " .
	"LEFT OUTER JOIN {$opt["table_prefix"]}allocs a ON a.itemid = i.itemid AND i.quantity = 1 " .	// only join allocs for single-quantity items.
	"LEFT OUTER JOIN {$opt["table_prefix"]}users ub ON ub.userid = a.userid AND a.bought = 1 " .
	"LEFT OUTER JOIN {$opt["table_prefix"]}users ur ON ur.userid = a.userid AND a.bought = 0 " .
	"WHERE i.userid = $shopfor " .
	"ORDER BY " . $sortby);
$stmt->bindParam(1, $shopfor, PDO::PARAM_INT);
$stmt->execute();
$shoprows = array();
while ($row = $stmt->fetch()) {
	$row['price'] = formatPrice($row['price'], $opt);
	if ($row['quantity'] > 1) {
		// check the allocs table to see what has been allocated.
		$avail = $row['quantity'];
		$substmt = $smarty->dbh()->prepare("SELECT a.quantity, a.bought, a.userid, " .
					"ub.fullname AS bfullname, ub.userid AS boughtid, " .
					"ur.fullname AS rfullname, ur.userid AS reservedid " .
				"FROM {$opt["table_prefix"]}allocs a " .
				"LEFT OUTER JOIN {$opt["table_prefix"]}users ub ON ub.userid = a.userid AND a.bought = 1 " .
				"LEFT OUTER JOIN {$opt["table_prefix"]}users ur ON ur.userid = a.userid AND a.bought = 0 " .
				"WHERE a.itemid = ? " .
				"ORDER BY a.bought, a.quantity");
		$substmt->bindValue(1, $row['itemid'], PDO::PARAM_INT);
		$substmt->execute();
		$ibought = 0;
		$ireserved = 0;
		$itemallocs = array();
		while ($allocrow = $substmt->fetch()) {
			if ($allocrow['bfullname'] != '') {
				if ($allocrow['boughtid'] == $userid) {
					$ibought += $allocrow['quantity'];
					$itemallocs[] = ($allocrow['quantity'] . " bought by you.");
				}
				else {
					if (!$opt["anonymous_purchasing"]) {
						$itemallocs[] = ($allocrow['quantity'] . " bought by " . $allocrow['bfullname'] . ".");
					}
					else {
						$itemallocs[] = ($allocrow['quantity'] . " bought.");
					}
				}
			}
			else {
				if ($allocrow['reservedid'] == $userid) {
					$ireserved += $allocrow['quantity'];
					$itemallocs[] = ($allocrow['quantity'] . " reserved by you.");
				}
				else {
					if (!$opt["anonymous_purchasing"]) {
						$itemallocs[] = ($allocrow['quantity'] . " reserved by " . $allocrow['rfullname'] . ".");
					}
					else {
						$itemallocs[] = ($allocrow['quanitity'] . " reserved.");
					}
				}
			}
			$avail -= $allocrow['quantity'];
		}
		$row['allocs'] = $itemallocs;
		$row['avail'] = $avail;
		$row['ibought'] = $ibought;
		$row['ireserved'] = $ireserved;
	}
	$shoprows[] = $row;
}

/* okay, I *would* retrieve the shoppee's fullname from the items recordset,
	except that I wouldn't get it if he had no items, so I *could* LEFT OUTER
	JOIN, but then it would complicate the iteration logic, so let's just
	hit the DB again. */
$stmt = $smarty->dbh()->prepare("SELECT fullname FROM {$opt["table_prefix"]}users WHERE userid = ?");
$stmt->bindParam(1, $shopfor, PDO::PARAM_INT);
$stmt->execute();
if ($row = $stmt->fetch()) {
	$ufullname = $row["fullname"];
}

$smarty->assign('ufullname', $ufullname);
$smarty->assign('shopfor', $shopfor);
$smarty->assign('shoprows', $shoprows);
$smarty->assign('userid', $userid);
if (isset($message)) {
	$smarty->assign('message', $message);
}
$smarty->display('shop.tpl');
?>
