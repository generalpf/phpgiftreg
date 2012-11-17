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

if (empty($_GET["sort"]))
	$sort = "source";
else
	$sort = $_GET["sort"];
	
switch($sort) {
	case "category":
		$sortby = "category, source, price";
		break;
	case "description":
		$sortby = "description, price";
		break;
	case "ranking":
		$sortby = "rankorder DESC, source, price";
		break;
	case "source":
		$sortby = "source, category, rankorder DESC";
		break;
	case "price":
		$sortby = "quantity * price, category, source";
		break;
	default:
		$sortby = "rankorder DESC, source, price";
}

$query = "SELECT description, source, price, i.comment, i.quantity, i.quantity * i.price AS total, rendered, c.category " .
			"FROM {$OPT["table_prefix"]}items i " .
			"INNER JOIN {$OPT["table_prefix"]}users u ON u.userid = i.userid " .
			"INNER JOIN {$OPT["table_prefix"]}ranks r ON r.ranking = i.ranking " .
			"LEFT OUTER JOIN {$OPT["table_prefix"]}categories c ON c.categoryid = i.category " .
			"WHERE u.userid = " . $_SESSION["userid"] . " " .
			"ORDER BY $sortby";
$rs = mysql_query($query) or die("Could not query $query: " . mysql_error());
$shoplist = array();
$totalprice = 0;
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$totalprice += $row["total"];
	if ($row["quantity"] == 1)
		$row["price"] = formatPrice($row["price"]);
	else
		$row["price"] = $row["quantity"] . " @ " . formatPrice($row["price"]) . " = " . formatPrice($row["total"]);
	$shoplist[] = $row;
}
$itemcount = mysql_num_rows($rs);
mysql_free_result($rs);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('shoplist', $shoplist);
$smarty->assign('totalprice', formatPrice($totalprice));
$smarty->assign('itemcount', $itemcount);
$smarty->assign('userid', $userid);
$smarty->assign('isadmin', $_SESSION["admin"]);
$smarty->assign('opt', $OPT);
$smarty->display('mylist.tpl');
?>

