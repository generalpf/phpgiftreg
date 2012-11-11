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

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Shopping List</title>
<script language="JavaScript">
	function printPage() {
		window.print();
	}
</script>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body>
<p class="pagetitle">Gift Registry - Wish List for <?php echo $_SESSION["fullname"]; ?></p>
<?php
if ($OPT["show_helptext"]) {
	?>
	<p>
		<div class="helptext">
			<ul>
				<li>You can click the column headers to sort by that attribute.</li>
				<li>Once you've bought or decided not to buy an item, remember to return to the recipient's gift lists and mark it accordingly.</li>
				<li><strong>Please login to the Gift Registry site to get the most recent version of this list.</strong></li>
				<li>For better printing results, please change your print orientation to "Landscape" mode.</li>
			</ul>
		</div>
	</p>
	<?php
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
$shoplist = mysql_query($query) or die("Could not query $query: " . mysql_error());
?>
<p>
	<table class="partbox" width="100%" cellspacing="0">
		<!--<tr class="partboxtitle">
			<td colspan="5" align="center">Wish List for <?php echo $_SESSION["fullname"]; ?></td>
		</tr>-->
		<tr>
			<th class="colheader"><a href="mylist.php?sort=ranking">Ranking</a></th>
			<th class="colheader"><a href="mylist.php?sort=source">Source</a></th>
			<th class="colheader"><a href="mylist.php?sort=description">Description</a></th>
			<th class="colheader"><a href="mylist.php?sort=category">Category</a></th>
			<th class="rcolheader"><a href="mylist.php?sort=price">Price</a></th>
		</tr>
		<?php
		$i = 0;
		$totalprice = 0;
		while ($row = mysql_fetch_array($shoplist,MYSQL_ASSOC)) {
			$totalprice += $row["total"];
			?>
			<tr class="<?php echo (!($i++ % 2)) ? "evenrow" : "oddrow" ?>">
				<td nowrap><?php echo $row["rendered"]; ?></td>
				<td><?php echo $row["source"]; ?></td>
				<td><?php echo $row["description"]; ?></td>
				<td nowrap><?php echo $row["category"]; ?></td>
				<td align="right">
					<?php
					if ($row["quantity"] == 1)
						echo formatPrice($row["price"]);
					else
						echo $row["quantity"] . "&nbsp;@&nbsp;" . formatPrice($row["price"]) . "&nbsp;=&nbsp;" . formatPrice($row["total"]);
					?>
				</td>
			</tr>
			<?php
			if ($row["comment"] != "") {
				?>
				<tr class="<?php echo (($i % 2) ? "evenrow" : "oddrow"); ?>">
					<td>&nbsp;</td>
					<td colspan="3">
						<i><?php echo str_replace("\r\n","<br />",$row["comment"]); ?></i>
					</td>
					<td>&nbsp;</td>
				</tr>
				<?php
			}
		}
		?>
	</table>
</p>
<p align="right">
	<?php echo mysql_num_rows($shoplist) . " item(s), {$OPT["currency_symbol"]}$totalprice total."; ?>
</p>
<?php
mysql_free_result($shoplist);
?>
<p>
	<a onClick="printPage()" href="#">Send to printer</a>&nbsp;/&nbsp;<a href="index.php">Back to Main</a>
</p>
</body>
</html>
