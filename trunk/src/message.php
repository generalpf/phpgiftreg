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

$action = empty($_GET["action"]) ? "" : $_GET["action"];

if ($action == "send") {
	$msg = $_GET["msg"];

	for ($i = 0; $i < count($_GET["recipients"]); $i++)
		sendMessage($userid, (int) $_GET["recipients"][$i], $msg, $smarty->dbh(), $smarty->opt());
		
	header("Location: " . getFullPath("index.php?message=Your+message+has+been+sent+to+" . count($_GET["recipients"]) . "+recipient(s)."));
	exit;
}

try {
	$stmt = $smarty->dbh()->prepare("SELECT u.userid, u.fullname " .
			"FROM {$opt["table_prefix"]}shoppers s " .
			"INNER JOIN {$opt["table_prefix"]}users u ON u.userid = s.mayshopfor " .
			"WHERE s.shopper = ? " .
				"AND pending = 0 " .
			"ORDER BY u.fullname");
	$stmt->bindParam(1, $userid, PDO::PARAM_INT);
	$stmt->execute();
	$recipients = array();
	$rcount = 0;
	while ($row = $stmt->fetch()) {
		$recipients[] = $row;
		++$rcount;
	}

	$smarty->assign('recipients', $recipients);
	$smarty->assign('rcount', $rcount);
	$smarty->assign('userid', $userid);
	$smarty->display('message.tpl');
}
catch (PDOException $e) {
	die("sql exception: " . $e->getMessage());
}
?>
