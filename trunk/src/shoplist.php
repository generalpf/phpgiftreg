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

if (empty($_GET["sort"]))
	$sort = "source";
else
	$sort = $_GET["sort"];
	
switch($sort) {
	case "recipient":
		$sortby = "fullname, source, price";
		break;
	case "description":
		$sortby = "description, price";
		break;
	case "ranking":
		$sortby = "rankorder DESC, source, price";
		break;
	case "source":
		$sortby = "source, fullname, rankorder DESC";
		break;
	case "price":
		$sortby = "a.quantity * i.price, fullname, source";
		break;
	default:
		$sortby = "source, fullname, rankorder DESC";
}

try {
	// not worried about sql injection here since $sortby is a function of $sort, which falls through.
	$stmt = $smarty->dbh()->prepare("SELECT description, source, price, i.comment, a.quantity, a.quantity * i.price AS total, rendered, fullname " .
				"FROM {$opt["table_prefix"]}items i " .
				"INNER JOIN {$opt["table_prefix"]}users u ON u.userid = i.userid " .
				"INNER JOIN {$opt["table_prefix"]}ranks r ON r.ranking = i.ranking " .
				"INNER JOIN {$opt["table_prefix"]}allocs a ON a.userid = ? AND a.itemid = i.itemid AND bought = 0 " .
				"ORDER BY " . $sortby);
	$stmt->bindParam(1, $userid, PDO::PARAM_INT);

	$stmt->execute();
	$shoplist = array();
	$totalprice = 0;
	$itemcount = 0;
	while ($row = $stmt->fetch()) {
		$totalprice += $row["total"];
		++$itemcount;
		if ($row["quantity"] == 1) {
			$row["price"] = formatPrice($row["price"], $opt);
		}
		else {
			$row["price"] = $row["quantity"] . " @ " . formatPrice($row["price"], $opt) . " = " . formatPrice($row["total"], $opt);
		}
		$shoplist[] = $row;
	}

	$smarty->assign('shoplist', $shoplist);
	$smarty->assign('totalprice', formatPrice($totalprice, $opt));
	$smarty->assign('itemcount', $itemcount);
	$smarty->assign('userid', $userid);
	$smarty->display('shoplist.tpl');
}
catch (PDOException $e) {
	die("sql exception: " . $e->getMessage());
}
?>
