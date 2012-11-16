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
	
	if ($action == "send") {
		$msg = $_GET["msg"];
		if (!get_magic_quotes_gpc())
			$msg = addslashes($msg);

		for ($i = 0; $i < count($_GET["recipients"]); $i++)
			sendMessage($userid,(int) $_GET["recipients"][$i],$msg);
		
		header("Location: " . getFullPath("index.php?message=Your+message+has+been+sent+to+" . count($_GET["recipients"]) . "+recipient(s)."));
		exit;
	}
	else {
		echo "Unknown verb.";
		exit;
	}
}

$query = "SELECT u.userid, u.fullname " .
			"FROM {$OPT["table_prefix"]}shoppers s " .
			"INNER JOIN {$OPT["table_prefix"]}users u ON u.userid = s.mayshopfor " .
			"WHERE s.shopper = " . $userid . " " .
				"AND pending = 0 " .
			"ORDER BY u.fullname";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$recipients = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$recipients[] = $row;
}
$rcount = mysql_num_rows($rs);
mysql_free_result($rs);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('recipients', $recipients);
$smarty->assign('rcount', $rcount);
$smarty->assign('userid', $userid);
$smarty->assign('isadmin', $_SESSION["admin"]);
$smarty->assign('opt', $OPT);
$smarty->display('message.tpl');
?>
