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

/* for some reason, $OPT isn't accessible from here, even when config.php is
	explicitly included.  however, $GLOBALS["OPT"] works fine. */

function getFullPath($url) {
	$fp = $_SERVER["SERVER_PORT"] == "443" ? "https://" : "http://";
	$fp .= $_SERVER["HTTP_HOST"];
	$dir = dirname($_SERVER["PHP_SELF"]);
	if ($dir != "/")
		$fp .= $dir;
	$fp .= "/" . $url;
	return $fp;
}

function jsEscape($s) {
	return str_replace("\"","\\u0022",str_replace("'","\\'",str_replace("\r\n","\\r\\n",$s)));
}

function adjustAllocQuantity($itemid, $userid, $bought, $adjust) {
	$howmany = getExistingQuantity($itemid,$userid,$bought);
	if ($howmany == 0) {
		if ($adjust < 0) {
			// can't subtract anything from 0.
			return 0;
		}
		else {
			$query = "INSERT INTO {$GLOBALS["OPT"]["table_prefix"]}allocs(itemid,userid,bought,quantity) " .
					"VALUES($itemid,$userid,$bought,$adjust)";
			mysql_query($query) or die("Could not query: " . mysql_error());
			return $howmany;
		}
	}
	else {
		/* figure out the real amount to adjust by, in case someone claims to have
			received 3 of something from a buyer when they only bought 2. */
		if ($adjust < 0) {
			if (abs($adjust) > $howmany)
				$actual = -$howmany;
			else
				$actual = $adjust;
		}
		else {
			$actual = $adjust;
		}
		
		if ($howmany + $actual == 0)
			$query = "DELETE FROM {$GLOBALS["OPT"]["table_prefix"]}allocs WHERE itemid = $itemid AND userid = $userid AND bought = $bought";
		else
			$query = "UPDATE {$GLOBALS["OPT"]["table_prefix"]}allocs " .
					"SET quantity = quantity + $actual " .	// because "quantity + -5" is okay.
					"WHERE itemid = $itemid AND userid = $userid AND bought = $bought";
		
		mysql_query($query) or die("Could not query: " . mysql_error());
		return $actual;
	}
}

function getExistingQuantity($itemid, $userid, $bought) {
	$query = "SELECT quantity FROM {$GLOBALS["OPT"]["table_prefix"]}allocs WHERE bought = $bought AND userid = $userid AND itemid = $itemid";
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	$row = mysql_fetch_array($rs,MYSQL_ASSOC);
	if (!$row)
		return 0;
	else {
		$qty = $row["quantity"];
		mysql_free_result($rs);
		return $qty;
	}
}

function sendMessage($sender, $recipient, $message) {
	// assumes $message has already been slashed.
	$query = "INSERT INTO {$GLOBALS["OPT"]["table_prefix"]}messages(sender,recipient,message,created) " .
			"VALUES($sender,$recipient,'$message','" . strftime("%Y-%m-%d") . "')";
	mysql_query($query) or die("Could not query: " . mysql_error());
	
	// determine if e-mail must be sent.
	$query = "SELECT ur.email_msgs, ur.email AS remail, us.fullname, us.email AS semail FROM {$GLOBALS["OPT"]["table_prefix"]}users ur " .
			"INNER JOIN {$GLOBALS["OPT"]["table_prefix"]}users us ON us.userid = $sender " .
			"WHERE ur.userid = $recipient";
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	$row = mysql_fetch_array($rs,MYSQL_ASSOC);
	if (!$row) die("Recipient does not exist.");
	if ($row["email_msgs"] == 1) {
		mail(
			$row["remail"],
			"Gift Registry message from " . $row["fullname"],
			$row["fullname"] . " <" . $row["semail"] . "> sends:\r\n" . stripslashes($message),
			"From: {$GLOBALS["OPT"]["email_from"]}\r\nReply-To: " . $row["semail"] . "\r\nX-Mailer: {$GLOBALS["OPT"]["email_xmailer"]}\r\n"
		) or die("Mail not accepted for " . $row["remail"]);
	}
	mysql_free_result($rs);
}

function generatePassword() {
	//* borrowed from hitech-password.php - a PHP Message board script
	//* (c) Hitech Scripts 2003
	//* For more information, visit http://www.hitech-scripts.com
	//* modified for phpgiftreg by Chris Clonch
	mt_srand((double) microtime() * 1000000);
	$newstring = "";
	if ($GLOBALS["OPT"]["password_length"] > 0) {
		while(strlen($newstring) < $GLOBALS["OPT"]["password_length"]) {
			switch (mt_rand(1,3)) {
				case 1: $newstring .= chr(mt_rand(48,57)); break;  // 0-9
				case 2: $newstring .= chr(mt_rand(65,90)); break;  // A-Z
				case 3: $newstring .= chr(mt_rand(97,122)); break; // a-z
			}
		}
	}
	return $newstring;
}

function formatPrice($price) {
	if ($price == 0.0 && $GLOBALS["OPT"]["hide_zero_price"])
		return "&nbsp;";
	else
		return $GLOBALS["OPT"]["currency_symbol"] . number_format($price,2,".",",");
}

function stampUser($userid) {
	$query = "UPDATE {$GLOBALS["OPT"]["table_prefix"]}users SET list_stamp = NOW() WHERE userid = $userid";
	mysql_query($query) or die("Could not query: " . mysql_error());
}

function deleteImageForItem($itemid) {
	$query = "SELECT image_filename FROM {$GLOBALS["OPT"]["table_prefix"]}items WHERE itemid = $itemid";
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		if ($row["image_filename"] != "") {
			unlink($GLOBALS["OPT"]["image_subdir"] . "/" . $row["image_filename"]);
		}
	}
	mysql_free_result($rs);
	$query = "UPDATE {$GLOBALS["OPT"]["table_prefix"]}items SET image_filename = NULL WHERE itemid = $itemid";
	mysql_query($query) or die("Could not query: " . mysql_error());
}

function fixForJavaScript($s) {
	$s = htmlentities($s);
	$s = str_replace("'","\\'",$s);
	$s = str_replace("\r\n","<br />",$s);
	$s = str_replace("\n","<br />",$s);
	return $s;
}
?>
