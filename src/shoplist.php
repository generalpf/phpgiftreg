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
<title>Gift Registry - My Shopping List</title>
<script language="JavaScript" type="text/javascript">
	function printPage() {
		window.print();
	}
</script>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body>
<?php
if ($OPT["show_helptext"]) {
	?>
	<p class="helptext">
		This is a list of all items you have <strong>reserved</strong>.  Once you've bought or decided not to buy an item, remember to return to the recipient's gift lists and mark it accordingly.
	</p>
	<?php
}
?>
<p class="pagetitle">Gift Registry - My Shopping List</p>
<?php
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
	

$query = "SELECT description, source, price, i.comment, a.quantity, a.quantity * i.price AS total, rendered, fullname " .
			"FROM {$OPT["table_prefix"]}items i " .
			"INNER JOIN {$OPT["table_prefix"]}users u ON u.userid = i.userid " .
			"INNER JOIN {$OPT["table_prefix"]}ranks r ON r.ranking = i.ranking " .
			"INNER JOIN {$OPT["table_prefix"]}allocs a ON a.userid = $userid AND a.itemid = i.itemid AND bought = 0 " .
			"ORDER BY $sortby";
$shoplist = mysql_query($query) or die("Could not query $query: " . mysql_error());
?>
<p>
	<table class="partbox" width="100%" cellspacing="0">
		<tr class="partboxtitle">
			<td colspan="5" align="center">My Shopping List</td>
		</tr>
		<tr>
			<th class="colheader"><a href="shoplist.php?sort=recipient">Recipient</a></th>
			<th class="colheader"><a href="shoplist.php?sort=description">Description</a></th>
			<th class="colheader"><a href="shoplist.php?sort=ranking">Ranking</a></th>
			<th class="colheader"><a href="shoplist.php?sort=source">Source</a></th>
			<th class="rcolheader"><a href="shoplist.php?sort=price">Price</a></th>
		</tr>
		<?php
		$i = 0;
		$totalprice = 0;
		while ($row = mysql_fetch_array($shoplist,MYSQL_ASSOC)) {
			$totalprice += $row["total"];
			?>
			<tr class="<?php echo (!($i++ % 2)) ? "evenrow" : "oddrow" ?>">
				<td nowrap><?php echo $row["fullname"]; ?></td>
				<td><?php echo $row["description"]; ?></td>
				<td><?php echo $row["rendered"]; ?></td>
				<td><?php echo $row["source"]; ?></td>
				<td align="right">
					<?php
					if ($row["quantity"] == 1)
						echo formatPrice($row["price"]);
					else
						echo $row["quantity"] . " @ " . formatPrice($row["price"]) . " = " . formatPrice($row["total"]);
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
<p>
	<?php echo mysql_num_rows($shoplist) . " item(s), {$OPT["currency_symbol"]}$totalprice total."; ?>
</p>
<?php
mysql_free_result($shoplist);
?>
<p>
	<a onClick="printPage()" href="#">Send to printer</a>&nbsp;/&nbsp;<a href="index.php">Back to main</a>
</p>
</body>
</html>
