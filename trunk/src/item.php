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

// for security, let's make sure that if an itemid was passed in, it belongs
// to $userid.  all operations on this page should only be performed by
// the item's owner.
if (isset($_REQUEST["itemid"]) && $_REQUEST["itemid"] != "") {
	$rs = mysql_query("SELECT * FROM {$OPT["table_prefix"]}items WHERE userid = $userid AND itemid = " . (int) $_REQUEST["itemid"]) or die("Could not query: " . mysql_error());
	if (mysql_num_rows($rs) == 0) {
		echo "Nice try! (That's not your item.)";
		exit;
	}
	mysql_free_result($rs);
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

		if (!get_magic_quotes_gpc()) {
			$description = addslashes($description);
			$price = addslashes($price);
			$source = addslashes($source);
			$url = addslashes($url);
			$category = addslashes($category);
			$ranking = addslashes($ranking);
			$comment = addslashes($comment);
		}

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
			deleteImageForItem((int) $_REQUEST["itemid"]);
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
			$temp_name = tempnam($upload_dir . "/" . $OPT["image_subdir"],"");
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
		/* find out if this item is bought or reserved. */
		$query = "SELECT a.userid, a.quantity, a.bought, i.description FROM {$OPT["table_prefix"]}allocs a INNER JOIN {$OPT["table_prefix"]}items i ON i.itemid = a.itemid WHERE a.itemid = " . (int) $_REQUEST["itemid"];
		$rs = mysql_query($query) or die("Could not query: " . mysql_error());
		while ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
			$buyerid = $row["userid"];
			$quantity = $row["quantity"];
			$bought = $row["bought"];
			sendMessage($userid,
					$buyerid,
					addslashes("\"" . mysql_escape_string($row["description"]) . "\" that you " . (($bought == 1) ? "bought" : "reserved") . " $quantity of for {$_SESSION["fullname"]} has been deleted.  Check your reservation/purchase to ensure it's still needed."));
		}
		mysql_free_result($rs);
		deleteImageForItem((int) $_REQUEST["itemid"]);
		$query = "DELETE FROM {$OPT["table_prefix"]}items WHERE itemid = " . (int) $_REQUEST["itemid"];
		mysql_query($query) or die("Could not query: " . mysql_error());
		stampUser($userid);
		header("Location: " . getFullPath("index.php?message=Item+deleted."));
		exit;
	}
	else if ($action == "edit") {
		$query = "SELECT description, price, source, category, url, ranking, comment, quantity, image_filename FROM {$OPT["table_prefix"]}items WHERE itemid = " . (int) $_REQUEST["itemid"];
		$rs = mysql_query($query) or die("Could not query: " . mysql_error());
		if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
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
		mysql_free_result($rs);
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
			$query = "INSERT INTO {$OPT["table_prefix"]}items(userid,description,price,source,category,url,ranking,comment,quantity" . ($image_base_filename != "" ? ",image_filename" : "") . ") " .
						"VALUES($userid,'$description',$price,'$source'," . (($category == "") ? "NULL" : "'$category'") . "," . (($url == "") ? "NULL" : "'$url'") . ",$ranking," . (($comment == "") ? "NULL" : "'$comment'") . ",$quantity" . ($image_base_filename != "" ? ",'$image_base_filename'" : "") . ")";
			mysql_query($query) or die("Could not query: " . mysql_error());
			stampUser($userid);
			header("Location: " . getFullPath("index.php"));
			exit;
		}
	}
	else if ($action == "update") {
		if (!$haserror) {
			// TODO: if the quantity is updated, send a message to everyone who has an allocation for it.
			$query = "UPDATE {$OPT["table_prefix"]}items SET " .
					"description = '$description', " .
					"price = $price, " .
					"source = '$source', " .
					"category = " . (($category == "") ? "NULL" : "'$category'") . ", " .
					"url = " . (($url == "") ? "NULL" : "'$url'") . ", " .
					"ranking = $ranking, " .
					"comment = " . (($comment == "") ? "NULL" : "'$comment'") . ", " . 
					"quantity = $quantity " .
					($image_base_filename != "" ? ", image_filename = '$image_base_filename' " : "") .
					"WHERE itemid = " . (int) $_REQUEST["itemid"];
			mysql_query($query) or die("Could not query: " . mysql_error());
			stampUser($userid);
			header("Location: " . getFullPath("index.php"));
			exit;		
		}
	}
	else {
		echo "Unknown verb.";
		exit;
	}
}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Gift Registry - Edit Item</title>
<link href="styles.css" type="text/css" rel="stylesheet" />
</head>
<body onLoad="document.item.description.focus();">
<?php
if ($OPT["show_helptext"]) {
	?>
	<p><div class="helptext">
		Helpful hints:
		<ul>
			<li>Include a URL copied &amp; pasted from the address bar of your browser so that potential buyers can see exactly what you want.</li>
			<li>If the item description and URL can't describe exactly what you want, use the <strong>Comment</strong> area to mention anything you feel is necessary.  It doesn't mean the shopper has to buy the item from that website.</li>
			<li>If you don't know the price of the item, simply enter <strong>0</strong>.</li>
			<li>Try not to set all your items at the same ranking level.  When someone is shopping for you, they'll rely on the ranking to know what you want the most.  If you don't think there are enough levels, or the descriptions aren't adequate, ask an administrator to add or change them.</li>
			<?php
			if ($OPT["allow_multiples"] == 1) {
				?>
				<li>The quantity field indicates the number of that item that you want.  Once that many are bought or reserved, no more will be available.  If you have no limit on how many you want, enter 999 (for example).</li>
				<?php
			}
			?>
		</ul>
	</div></p>
	<?php
}
?>
<form name="item" method="POST" action="item.php" enctype="multipart/form-data">	
	<?php 
	if ($action == "edit" || (isset($haserror) && $action == "update")) {
		?>
		<input type="hidden" name="itemid" value="<?php echo (int) $_REQUEST["itemid"]; ?>">
		<input type="hidden" name="action" value="update">
		<?php
	}
	else if ($action == "add" || (isset($haserror) && $action == "insert")) {
		?>
		<input type="hidden" name="action" value="insert">
		<?php
	}
	?>
	<div align="center">
		<table class="partbox">
			<tr valign="top">
				<td>Description</td>
				<td>
					<input name="description" type="text" value="<?php echo htmlspecialchars(stripslashes($description)); ?>" maxlength="255" size="50"/>
					<?php
					if (isset($description_error)) {
						?><br /><font color="red"><?php echo $description_error ?></font><?php
					}
					?>
				</td>
			</tr>
			<tr valign="top">
				<td>Category</td>
				<td>
					<select name="category">
						<option value="" <?php if ($category == NULL) echo "SELECTED"; ?>>Uncategorized</option>
						<?php
						$rs = mysql_query("SELECT categoryid, category FROM {$OPT["table_prefix"]}categories ORDER BY category");
						while ($row = mysql_fetch_assoc($rs)) {
							echo "<option value=\"" . $row["categoryid"] . "\"" . (($category == $row["categoryid"]) ? " SELECTED" : "") . ">" . $row["category"] . "</option>\n";
						}
						mysql_free_result($rs);
						?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<td>Price (<?php echo $OPT["currency_symbol"]; ?>)</td>
				<td>
					<input name="price" type="text" value="<?php echo stripslashes($price); ?>" />
					<?php
					if (isset($price_error)) {
						?><br /><font color="red"><?php echo $price_error ?></font><?php
					}
					?>
				</td>
			</tr>
			<tr valign="top">
				<td>Store/Retailer</td>
				<td>
					<input name="source" type="text" value="<?php echo htmlspecialchars(stripslashes($source)); ?>" maxlength="255" size="50"/>
					<?php
					if (isset($source_error)) {
						?><br /><font color="red"><?php echo $source_error ?></font><?php
					}
					?>
				</td>
			</tr>
			<tr valign="top">
				<td>Ranking</td>
				<td>
					<?php
					$query = "SELECT ranking, title FROM {$OPT["table_prefix"]}ranks ORDER BY rankorder";
					$ranks = mysql_query($query) or die("Could not query: " . mysql_error());
					?>
					<select name="ranking" size="<?php echo mysql_num_rows($ranks); ?>">
						<?php
						while ($row = mysql_fetch_array($ranks,MYSQL_ASSOC)) {
							?>
							<option value="<?php echo $row["ranking"]; ?>" <?php if ($row["ranking"] == $ranking) echo "SELECTED"; ?>><?php echo $row["title"]; ?></option>
							<?php
						}
						mysql_free_result($ranks);
						?>
					</select>
					<?php
					if (isset($ranking_error)) {
						?><br /><font color="red"><?php echo $ranking_error ?></font><?php
					}
					?>
				</td>
			</tr>
			<?php
			if ($OPT["allow_multiples"] == 1) {
				?>
				<tr valign="top">
					<td>Quantity<br /></td>
					<td>
						<input name="quantity" type="text" value="<?php echo $quantity; ?>" maxlength="3" size="3"/>
						<?php
						if (isset($quantity_error)) {
							?><br /><font color="red"><?php echo $quantity_error ?></font><?php
						}
						?>
					</td>
				</tr>
				<?php
			}
			else {
				?>
				<input type="hidden" name="quantity" value="1">
				<?php
			}
			?>
			<tr valign="top">
				<td>URL<br /><i>(optional)</i></td>
				<td>
					<input name="url" type="text" value="<?php echo htmlspecialchars(stripslashes($url)); ?>" maxlength="255" size="50"/>
					<?php
					if (isset($url_error)) {
						?><br /><font color="red"><?php echo $url_error ?></font><?php
					}
					?>
				</td>
			</tr>
			<?php
			if ($OPT["allow_images"]) {
				?>
				<tr valign="top">
					<td>Image<br /><i>(optional)</i></td>
					<td>
						<table border="0" cellpadding="2" cellspacing="2">
							<?php
							if ($image_filename == "") {
								?>
								<tr>
									<td><input type="radio" name="image" value="none" CHECKED /></td>
									<td>No image.</td>
								</tr>
								<tr valign="top">
									<td rowspan="2"><input type="radio" name="image" value="upload" /></td>
									<td>Upload image:</td>
								</tr>
								<tr valign="top">
									<td><input type="file" name="imagefile" /></td>
								</tr>
								<?php
							}
							else {
								?>
								<tr>
									<td><input type="radio" name="image" value="remove" /></td>
									<td>Remove existing image.</td>
								</tr>
								<tr>
									<td><input type="radio" name="image" value="keep" CHECKED /></td>
									<td>Keep existing image.</td>
								</tr>
								<tr valign="top">
									<td rowspan="2"><input type="radio" name="image" value="replace" /></td>
									<td>Replace existing image:</td>
								</tr>
								<tr valign="top">
									<td><input type="file" name="imagefile" /></td>
								</tr>
								<?php
							}
							?>
						</table>
					</td>
				</tr>
				<?php
			}
			?>
			<tr valign="top">
				<td>Comment<br /><i>(optional)</i></td>
				<td>
					<textarea name="comment" rows="5" cols="40"><?php echo htmlspecialchars(stripslashes($comment)); ?></textarea>
				</td>
			</tr>
		</table>
	</div>
	<p>
		<div align="center">
			<input type="submit" value="Save"/>
			<input type="button" value="Cancel" onClick="document.location.href='index.php';">
		</div>
	</p>
</form>
</body>
</html>
