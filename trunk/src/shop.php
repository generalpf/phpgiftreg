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

$action = "";
if (!empty($_GET["action"])) {
	$action = $_GET["action"];
	$itemid = (int) $_GET["itemid"];
	if ($action == "reserve") {
		adjustAllocQuantity($itemid,$userid,0,+1);
	}
	else if ($action == "purchase") {
		// decrement reserved.
		adjustAllocQuantity($itemid,$userid,0,-1);
		// increment purchased.
		adjustAllocQuantity($itemid,$userid,1,+1);
	}
	else if ($action == "return") {
		// increment reserved.
		adjustAllocQuantity($itemid,$userid,0,+1);
		// decrement purchased.
		adjustAllocQuantity($itemid,$userid,1,-1);
	}
	else if ($action == "release") {
		adjustAllocQuantity($itemid,$userid,0,-1);
	}
	else if ($action == "copy") {
		/* 
		can't do this because MySQL 3.x doesn't seem to support it (at least the version i was using).
		$query = "INSERT INTO items(userid,description,price,source,url,category) SELECT $userid, description, price, source, url, category FROM items WHERE itemid = " . $_GET["itemid"];
		mysql_query($query) or die("Could not query: " . mysql_error());
		*/
		/* TODO: copy the image too? */
		$query = "SELECT userid, description, price, source, url, category, comment FROM {$OPT["table_prefix"]}items WHERE itemid = " . (int) $_GET["itemid"];
		$rs = mysql_query($query) or die("Could not query: " . mysql_error());
		$row = mysql_fetch_array($rs,MYSQL_ASSOC) or die("No item to copy.");
		$desc = mysql_escape_string($row["description"]);
		$source = mysql_escape_string($row["source"]);
		$url = mysql_escape_string($row["url"]);
		$comment = mysql_escape_string($row["comment"]);
		$price = (float) $row["price"];
		$cat = (int) $row["category"];
		mysql_free_result($rs);
		$query = "INSERT INTO {$OPT["table_prefix"]}items(userid,description,price,source,url,comment,category,ranking,quantity) VALUES($userid,'$desc','$price','$source'," . (($url == "") ? "NULL" : "'$url'") . "," . (($comment == "") ? "NULL" : "'$comment'") . "," . (($cat == "") ? "NULL" : $cat) . ",1,1)";
		mysql_query($query) or die("Could not query: $query " . mysql_error());
		stampUser($userid);
		$message = "Added '" . stripslashes($desc) . "' to your gift list.";
	}
}

$shopfor = (int) $_GET["shopfor"];
if ($shopfor == $userid) {
	echo "Nice try! (You can't shop for yourself.)";
	exit;
}
$rs = mysql_query("SELECT * FROM {$OPT["table_prefix"]}shoppers WHERE shopper = $userid AND mayshopfor = $shopfor AND pending = 0") or die("Could not query: " . mysql_error());
if (mysql_num_rows($rs) == 0) {
	echo "Nice try! (You can't shop for someone who hasn't approved it.)";
	exit;
}
mysql_free_result($rs);

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
$query = "SELECT i.itemid, description, price, source, c.category, url, image_filename, " .
		"ub.fullname AS bfullname, ub.userid AS boughtid, " .
		"ur.fullname AS rfullname, ur.userid AS reservedid, " .
		"rendered, i.comment, i.quantity " .
	"FROM {$OPT["table_prefix"]}items i " .
	"LEFT OUTER JOIN {$OPT["table_prefix"]}categories c ON c.categoryid = i.category " .
	"LEFT OUTER JOIN {$OPT["table_prefix"]}ranks r ON r.ranking = i.ranking " .
	"LEFT OUTER JOIN {$OPT["table_prefix"]}allocs a ON a.itemid = i.itemid AND i.quantity = 1 " .	// only join allocs for single-quantity items.
	"LEFT OUTER JOIN {$OPT["table_prefix"]}users ub ON ub.userid = a.userid AND a.bought = 1 " .
	"LEFT OUTER JOIN {$OPT["table_prefix"]}users ur ON ur.userid = a.userid AND a.bought = 0 " .
	"WHERE i.userid = $shopfor " .
	"ORDER BY $sortby";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());

$shoprows = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$row['price'] = formatPrice($row['price']);
	if ($row['quantity'] > 1) {
		// check the allocs table to see what has been allocated.
		$avail = $row['quantity'];
		$query = "SELECT a.quantity, a.bought, a.userid, " .
					"ub.fullname AS bfullname, ub.userid AS boughtid, " .
					"ur.fullname AS rfullname, ur.userid AS reservedid " .
				"FROM {$OPT["table_prefix"]}allocs a " .
				"LEFT OUTER JOIN {$OPT["table_prefix"]}users ub ON ub.userid = a.userid AND a.bought = 1 " .
				"LEFT OUTER JOIN {$OPT["table_prefix"]}users ur ON ur.userid = a.userid AND a.bought = 0 " .
				"WHERE a.itemid = " . $row['itemid'] . " " .
				"ORDER BY a.bought, a.quantity";
		$allocs = mysql_query($query) or die("Could not query: " . mysql_error());
		$ibought = 0;
		$ireserved = 0;
		$itemallocs = array();
		while ($allocrow = mysql_fetch_array($allocs, MYSQL_ASSOC)) {
			if ($allocrow['bfullname'] != '') {
				if ($allocrow['boughtid'] == $userid) {
					$ibought += $allocrow['quantity'];
					$itemallocs[] = ($allocrow['quantity'] . " bought by you.");
				}
				else {
					if (!$OPT["anonymous_purchasing"]) {
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
					if (!$OPT["anonymous_purchasing"]) {
						$itemallocs[] = ($allocrow['quantity'] . " reserved by " . $allocrow['rfullname'] . ".");
					}
					else {
						$itemallocs[] = ($allocrow['quanitity'] . " reserved.");
					}
				}
			}
			$avail -= $allocrow['quantity'];
		}
		mysql_free_result($allocs);
		$row['allocs'] = $itemallocs;
		$row['avail'] = $avail;
		$row['ibought'] = $ibought;
		$row['ireserved'] = $ireserved;
	}
	$shoprows[] = $row;
}
mysql_free_result($rs);

/* okay, I *would* retrieve the shoppee's fullname from the items recordset,
	except that I wouldn't get it if he had no items, so I *could* LEFT OUTER
	JOIN, but then it would complicate the iteration logic, so let's just
	hit the DB again. */
$query = "SELECT fullname FROM {$OPT["table_prefix"]}users WHERE userid = $shopfor";
$urs = mysql_query($query) or die("Could not query: " . mysql_error());
$ufullname = mysql_fetch_array($urs, MYSQL_ASSOC);
$ufullname = $ufullname["fullname"];
mysql_free_result($urs);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('ufullname', $ufullname);
$smarty->assign('shopfor', $shopfor);
$smarty->assign('shoprows', $shoprows);
$smarty->assign('userid', $userid);
$smarty->assign('isadmin', $_SESSION["admin"]);
$smarty->assign('opt', $OPT);
$smarty->display('shop.tpl');
?>
