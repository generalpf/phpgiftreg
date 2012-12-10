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

// for security, let's make sure that if an itemid was passed in, it belongs
// to $userid.  all operations on this page should only be performed by
// the item's owner.
if (isset($_REQUEST["itemid"]) && $_REQUEST["itemid"] != "") {
	try {
		$stmt = $smarty->dbh()->prepare("SELECT * FROM {$opt["table_prefix"]}items WHERE userid = ? AND itemid = ?");
		$stmt->bindParam(1, $userid, PDO::PARAM_INT);
		$stmt->bindValue(2, (int) $_REQUEST["itemid"], PDO::PARAM_INT);
		$stmt->execute();
		if (!$stmt->fetch()) {
			die("Nice try! (That's not your item.)");
		}
	}
	catch (PDOException $e) {
		die("sql exception: " . $e->getMessage());
	}
}

$action = "";
if (!empty($_REQUEST["action"])) {
	$action = $_REQUEST["action"];
	
	if ($action == "insert" || $action == "update") {
		/* validate the data. */
		$description = trim($_REQUEST["description"]);
		$price = str_replace(",","",trim($_REQUEST["price"]));
		$source = trim($_REQUEST["source"]);
		$url = trim($_REQUEST["url"]);
		$category = trim($_REQUEST["category"]);
		$ranking = $_REQUEST["ranking"];
		$comment = $_REQUEST["comment"];
		$quantity = (int) $_REQUEST["quantity"];

		$haserror = false;
		if ($description == "") {
			$haserror = true;
			$description_error = "A description is required.";
		}
		if ($price == "" || !preg_match("/^\d*(\.\d{2})?$/i",$price)) {
			$haserror = true;
			$price_error = "Price format is not valid.<br />Price is required and must be a number, either accurate or approximate.<br />Do not enter the currency symbol.";
		}
		if ($source == "") {
			$haserror = true;
			$source_error = "A source is required (i.e., where it can be purchased).";
		}
		if ($url != "" && !preg_match("/^http(s)?:\/\/([^\/]+)/i",$url)) {
			$haserror = true;
			$url_error = "A well-formed URL is required in the format <i>http://www.somesite.net/somedir/somefile.html</i>.";
		}
		if ($ranking == "") {
			$haserror = true;
			$ranking_error = "A ranking is required.";
		}
		if ($quantity == "" || (int) $quantity < 1) {
			$haserror = true;
			$quantity_error = "A positive quantity is required.";
		}
	}

	if (!$haserror) {
		if ($_REQUEST["image"] == "remove" || $_REQUEST["image"] == "replace") {
			deleteImageForItem((int) $_REQUEST["itemid"], $smarty->dbh(), $smarty->opt());
		}
		if ($_REQUEST["image"] == "upload" || $_REQUEST["image"] == "replace") {
			/* TODO: verify that it's an image using $_FILES["imagefile"]["type"] */
			// what's the extension?
			$parts = pathinfo($_FILES["imagefile"]["name"]);
			$uploaded_file_ext = $parts['extension'];
			// what is full path to store images?  get it from the currently executing script.
			$parts = pathinfo($_SERVER["SCRIPT_FILENAME"]);
			$upload_dir = $parts['dirname'];
			// generate a temporary file in the configured directory.
			$temp_name = tempnam($upload_dir . "/" . $opt["image_subdir"],"");
			// unlink it, we really want an extension on that.
			unlink($temp_name);
			// here's the name we really want to use.  full path is included.
			$image_filename = $temp_name . "." . $uploaded_file_ext;
			// move the PHP temporary file to that filename.
			move_uploaded_file($_FILES["imagefile"]["tmp_name"],$image_filename);
			// the name we're going to record in the DB is the filename without the path.
			$image_base_filename = basename($image_filename);
		}
	}
	
	if ($action == "delete") {
		try {
			/* find out if this item is bought or reserved. */
			$stmt = $smarty->dbh()->prepare("SELECT a.userid, a.quantity, a.bought, i.description FROM {$opt["table_prefix"]}allocs a LEFT OUTER JOIN {$opt["table_prefix"]}items i ON i.itemid = a.itemid WHERE a.itemid = ?");
			$stmt->bindValue(1, (int) $_REQUEST["itemid"], PDO::PARAM_INT);
			$stmt->execute();
			while ($row = $stmt->fetch()) {
				$buyerid = $row["userid"];
				$quantity = $row["quantity"];
				$bought = $row["bought"];
				$description = $row["description"];	// need this for descriptions.
				if ($buyerid != null) {
					sendMessage($userid,
						$buyerid,
						$row["description"] . " that you " . (($bought == 1) ? "bought" : "reserved") . " $quantity of for {$_SESSION["fullname"]} has been deleted.  Check your reservation/purchase to ensure it's still needed.",
						$smarty->dbh(),
						$smarty->opt());
				}
			}
	
			deleteImageForItem((int) $_REQUEST["itemid"], $smarty->dbh(), $smarty->opt());

			$stmt = $smarty->dbh()->prepare("DELETE FROM {$opt["table_prefix"]}items WHERE itemid = ?");
			$stmt->bindValue(1, (int) $_REQUEST["itemid"], PDO::PARAM_INT);
			$stmt->execute();

			// TODO: are we leaking allocs records here?
		
			stampUser($userid, $smarty->dbh(), $smarty->opt());
			processSubscriptions($userid, $action, $description, $smarty->dbh(), $smarty->opt());

			header("Location: " . getFullPath("index.php?message=Item+deleted."));
			exit;
		}
		catch (PDOException $e) {
			die("sql exception: " . $e->getMessage());
		}
	}
	else if ($action == "edit") {
		$stmt = $smarty->dbh()->prepare("SELECT description, price, source, category, url, ranking, comment, quantity, image_filename FROM {$opt["table_prefix"]}items WHERE itemid = ?");
		$stmt->bindValue(1, (int) $_REQUEST["itemid"], PDO::PARAM_INT);
		$stmt->execute();

		if ($row = $stmt->fetch()) {
			$description = $row["description"];
			$price = number_format($row["price"],2,".",",");
			$source = $row["source"];
			$url = $row["url"];
			$category = $row["category"];
			$ranking = $row["ranking"];
			$comment = $row["comment"];
			$quantity = (int) $row["quantity"];
			$image_filename = $row["image_filename"];
		}
	}
	else if ($action == "add") {
		$description = "";
		$price = 0.00;
		$source = "";
		$url = "";
		$category = NULL;
		$ranking = NULL;
		$comment = "";
		$quantity = 1;
		$image_filename = "";
	}
	else if ($action == "insert") {
		if (!$haserror) {
			$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}items(userid,description,price,source,category,url,ranking,comment,quantity" . ($image_base_filename != "" ? ",image_filename" : "") . ") " .
				"VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?" . ($image_base_filename != "" ? ", ?)" : ")"));
			$stmt->bindParam(1, $userid, PDO::PARAM_INT);
			$stmt->bindParam(2, $description, PDO::PARAM_STR);
			$stmt->bindParam(3, $price);
			$stmt->bindParam(4, $source, PDO::PARAM_STR);
			$stmt->bindParam(5, $category, PDO::PARAM_INT);
			$stmt->bindParam(6, $url, PDO::PARAM_STR);
			$stmt->bindParam(7, $ranking, PDO::PARAM_INT);
			$stmt->bindParam(8, $comment, PDO::PARAM_STR);
			$stmt->bindParam(9, $quantity, PDO::PARAM_INT);
			if ($image_base_filename != "") {
				$stmt->bindParam(10, $image_base_filename, PDO::PARAM_STR);
			}
			$stmt->execute();
			
			stampUser($userid, $smarty->dbh(), $smarty->opt());
			processSubscriptions($userid, $action, $description, $smarty->dbh(), $smarty->opt());

			header("Location: " . getFullPath("index.php"));
			exit;
		}
	}
	else if ($action == "update") {
		if (!$haserror) {
			// TODO: if the quantity is updated, send a message to everyone who has an allocation for it.
			$stmt = $smarty->dbh()->prepare("UPDATE {$opt["table_prefix"]}items SET " .
					"description = ?, " .
					"price = ?, " .
					"source = ?, " .
					"category = ?, " .
					"url = ?, " .
					"ranking = ?, " .
					"comment = ?, " . 
					"quantity = ? " .
					($image_base_filename != "" ? ", image_filename = ? " : "") .
					"WHERE itemid = ?");
			$stmt->bindParam(1, $description, PDO::PARAM_STR);
			$stmt->bindParam(2, $price);
		    $stmt->bindParam(3, $source, PDO::PARAM_STR);
		    $stmt->bindParam(4, $category, PDO::PARAM_INT);
		    $stmt->bindParam(5, $url, PDO::PARAM_STR);
		    $stmt->bindParam(6, $ranking, PDO::PARAM_INT);
		    $stmt->bindParam(7, $comment, PDO::PARAM_STR);
		    $stmt->bindParam(8, $quantity, PDO::PARAM_INT);
		    if ($image_base_filename != "") {
				$stmt->bindParam(9, $image_base_filename, PDO::PARAM_STR);
				$stmt->bindValue(10, (int) $_REQUEST["itemid"], PDO::PARAM_INT);
			}
			else {
				$stmt->bindValue(9, (int) $_REQUEST["itemid"], PDO::PARAM_INT);
			}
			$stmt->execute();

			stampUser($userid, $smarty->dbh(), $smarty->opt());
			processSubscriptions($userid, $action, $description, $smarty->dbh(), $smarty->opt());

			header("Location: " . getFullPath("index.php"));
			exit;		
		}
	}
	else {
		echo "Unknown verb.";
		exit;
	}
}

$stmt = $smarty->dbh()->prepare("SELECT categoryid, category FROM {$opt["table_prefix"]}categories ORDER BY category");
$stmt->execute();
$categories = array();
while ($row = $stmt->fetch()) {
	$categories[] = $row;
}

$stmt = $smarty->dbh()->prepare("SELECT ranking, title FROM {$opt["table_prefix"]}ranks ORDER BY rankorder");
$stmt->execute();
$ranks = array();
while ($row = $stmt->fetch()) {
	$ranks[] = $row;
}

$smarty->assign('userid', $userid);
$smarty->assign('action', $action);
$smarty->assign('haserror', $haserror);
if (isset($_REQUEST['itemid'])) {
	$smarty->assign('itemid', (int) $_REQUEST['itemid']);
}
$smarty->assign('description', $description);
if (isset($descripton_error)) {
	$smarty->assign('description_error', $description_error);
}
$smarty->assign('category', $category);
if (isset($category_error)) {
	$smarty->assign('category_error', $category_error);
}
$smarty->assign('price', $price);
if (isset($price_error)) {
	$smarty->assign('price_error', $price_error);
}
$smarty->assign('source', $source);
if (isset($source_error)) {
	$smarty->assign('source_error', $source_error);
}
$smarty->assign('ranking', $ranking);
if (isset($ranking_error)) {
	$smarty->assign('ranking_error', $ranking_error);
}
$smarty->assign('quantity', $quantity);
if (isset($quantity_error)) {
	$smarty->assign('quantity_error', $quantity_error);
}
$smarty->assign('url', $url);
if (isset($url_error)) {
	$smarty->assign('url_error', $url_error);
}
$smarty->assign('image_filename', $image_filename);
$smarty->assign('comment', $comment);
$smarty->assign('categories', $categories);
$smarty->assign('ranks', $ranks);
$smarty->display('item.tpl');
?>
