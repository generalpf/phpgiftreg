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

function adjustAllocQuantity($itemid, $userid, $bought, $adjust, $dbh, $opt) {
	$howmany = getExistingQuantity($itemid, $userid, $bought, $dbh, $opt);
	if ($howmany == 0) {
		if ($adjust < 0) {
			// can't subtract anything from 0.
			return 0;
		}
		else {
			$stmt = $dbh->prepare("INSERT INTO {$opt["table_prefix"]}allocs(itemid,userid,bought,quantity) VALUES(?, ?, ?, ?)");
			$stmt->bindParam(1, $itemid, PDO::PARAM_INT);
			$stmt->bindParam(2, $userid, PDO::PARAM_INT);
			$stmt->bindParam(3, $bought, PDO::PARAM_BOOL);
			$stmt->bindParam(4, $adjust, PDO::PARAM_INT);
			$stmt->execute();
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
		
		if ($howmany + $actual == 0) {
			$stmt = $dbh->prepare("DELETE FROM {$opt["table_prefix"]}allocs WHERE itemid = ? AND userid = ? AND bought = ?");
			$stmt->bindParam(1, $itemid, PDO::PARAM_INT);
			$stmt->bindParam(2, $userid, PDO::PARAM_INT);
			$stmt->bindParam(3, $bought, PDO::PARAM_BOOL);
			$stmt->execute();
		}
		else {
			$stmt = $dbh->prepare("UPDATE {$opt["table_prefix"]}allocs " .
					"SET quantity = quantity + ? " .	// because "quantity + -5" is okay.
					"WHERE itemid = ? AND userid = ? AND bought = ?");
			$stmt->bindParam(1, $actual, PDO::PARAM_INT);
			$stmt->bindParam(2, $itemid, PDO::PARAM_INT);
			$stmt->bindParam(3, $userid, PDO::PARAM_INT);
			$stmt->bindParam(4, $bought, PDO::PARAM_BOOL);
			$stmt->execute();
		}
		return $actual;
	}
}

function getExistingQuantity($itemid, $userid, $bought, $dbh, $opt) {
	$stmt = $dbh->prepare("SELECT quantity FROM {$opt["table_prefix"]}allocs WHERE bought = ? AND userid = ? AND itemid = ?");
	$stmt->bindParam(1, $bought, PDO::PARAM_BOOL);
	$stmt->bindParam(2, $userid, PDO::PARAM_INT);
	$stmt->bindParam(3, $itemid, PDO::PARAM_INT);
	$stmt->execute();
	if ($row = $stmt->fetch()) {
		return $row["quantity"];
	}
	else {
		return 0;
	}
}

function processSubscriptions($publisher, $action, $itemdesc, $dbh, $opt) {
	// join the users table as a cheap way to get the guy's name without having to pass it in.
	$stmt = $dbh->prepare("SELECT subscriber, fullname FROM subscriptions sub INNER JOIN users u ON u.userid = sub.publisher WHERE publisher = ? AND (last_notified IS NULL OR DATE_ADD(last_notified, INTERVAL {$opt["notify_threshold_minutes"]} MINUTE) < NOW())");
	$stmt->bindParam(1, $publisher, PDO::PARAM_INT);
	$stmt->execute();

	$msg = "";
	while ($row = $stmt->fetch()) {
		if ($msg == "") {
			// same message for each user but we need the fullname from the first row before we can assemble it.
			if ($action == "insert") {
				$msg = $row["fullname"] . " has added the item \"$itemdesc\" to their list.";
			}
			else if ($action == "update") {
				$msg = $row["fullname"] . " has updated the item \"$itemdesc\" on their list.";
			}
			else if ($action == "delete") {
				$msg = $row["fullname"] . " has deleted the item \"$itemdesc\" from their list.";
			}
			$msg .= "\r\n\r\nYou are receiving this message because you are subscribed to their updates.  You will not receive another message for their updates for the next " . $opt["notify_threshold_minutes"] . " minutes.";
		}
		sendMessage($publisher, $row["subscriber"], $msg, $dbh, $opt);

		// stamp the subscription.
		$stmt2 = $dbh->prepare("UPDATE subscriptions SET last_notified = NOW() WHERE publisher = ? AND subscriber = ?");
		$stmt2->bindParam(1, $publisher, PDO::PARAM_INT);
		$stmt2->bindParam(2, $row["subscriber"], PDO::PARAM_INT);
		$stmt2->execute();
	}
}

function sendMessage($sender, $recipient, $message, $dbh, $opt) {
	$stmt = $dbh->prepare("INSERT INTO {$opt["table_prefix"]}messages(sender,recipient,message,created) VALUES(?, ?, ?, ?)");
	$stmt->bindParam(1, $sender, PDO::PARAM_INT);
	$stmt->bindParam(2, $recipient, PDO::PARAM_INT);
	$stmt->bindParam(3, $message, PDO::PARAM_STR);
	$stmt->bindValue(4, strftime("%Y-%m-%d"), PDO::PARAM_STR);
	$stmt->execute();
	
	// determine if e-mail must be sent.
	$stmt = $dbh->prepare("SELECT ur.email_msgs, ur.email AS remail, us.fullname, us.email AS semail FROM {$opt["table_prefix"]}users ur " .
			"INNER JOIN {$opt["table_prefix"]}users us ON us.userid = ? " .
			"WHERE ur.userid = ?");
	$stmt->bindParam(1, $sender, PDO::PARAM_INT);
	$stmt->bindParam(2, $recipient, PDO::PARAM_INT);
	$stmt->execute();
	if ($row = $stmt->fetch()) {
		if ($row["email_msgs"] == 1) {
			mail(
				$row["remail"],
				"Gift Registry message from " . $row["fullname"],
				$row["fullname"] . " <" . $row["semail"] . "> sends:\r\n" . $message,
				"From: {$opt["email_from"]}\r\nReply-To: " . $row["semail"] . "\r\nX-Mailer: {$opt["email_xmailer"]}\r\n"
			) or die("Mail not accepted for " . $row["remail"]);
		}
	}
	else {
		die("recipient doesn't exist");
	}
}

function generatePassword($opt) {
	//* borrowed from hitech-password.php - a PHP Message board script
	//* (c) Hitech Scripts 2003
	//* For more information, visit http://www.hitech-scripts.com
	//* modified for phpgiftreg by Chris Clonch
	mt_srand((double) microtime() * 1000000);
	$newstring = "";
	if ($opt["password_length"] > 0) {
		while(strlen($newstring) < $opt["password_length"]) {
			switch (mt_rand(1,3)) {
				case 1: $newstring .= chr(mt_rand(48,57)); break;  // 0-9
				case 2: $newstring .= chr(mt_rand(65,90)); break;  // A-Z
				case 3: $newstring .= chr(mt_rand(97,122)); break; // a-z
			}
		}
	}
	return $newstring;
}

function formatPrice($price, $opt) {
	if ($price == 0.0 && $opt["hide_zero_price"])
		return "&nbsp;";
	else
		return $opt["currency_symbol"] . number_format($price,2,".",",");
}

function stampUser($userid, $dbh, $opt) {
	$stmt = $dbh->prepare("UPDATE {$opt["table_prefix"]}users SET list_stamp = NOW() WHERE userid = ?");
	$stmt->bindParam(1, $userid, PDO::PARAM_INT);
	$stmt->execute();
}

function deleteImageForItem($itemid, $dbh, $opt) {
	$stmt = $dbh->prepare("SELECT image_filename FROM {$opt["table_prefix"]}items WHERE itemid = ?");
	$stmt->bindParam(1, $itemid, PDO::PARAM_INT);
	$stmt->execute();
	if ($row = $stmt->fetch()) {
		if ($row["image_filename"] != "") {
			unlink($opt["image_subdir"] . "/" . $row["image_filename"]);
		}

		$stmt = $dbh->prepare("UPDATE {$opt["table_prefix"]}items SET image_filename = NULL WHERE itemid = ?");
		$stmt->bindParam(1, $itemid, PDO::PARAM_INT);
		$stmt->execute();
	}
}

function fixForJavaScript($s) {
	$s = htmlentities($s);
	$s = str_replace("'","\\'",$s);
	$s = str_replace("\r\n","<br />",$s);
	$s = str_replace("\n","<br />",$s);
	return $s;
}
?>
