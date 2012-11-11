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

if (isset($_GET["action"])) {
	if ($_GET["action"] == "logout") {
		session_start();
		session_destroy();
	}
}

if (!empty($_POST["username"])) {
	include "db.php";
	$username = $_POST["username"];
	$password = $_POST["password"];
	if (!get_magic_quotes_gpc()) {
		$username = addslashes($username);
		$password = addslashes($password);
	}

	$query = "SELECT userid, fullname, admin FROM {$OPT["table_prefix"]}users WHERE username = '$username' AND password = {$OPT["password_hasher"]}('$password') AND approved = 1";
	$rs = mysql_query($query) or die("Could not query: " . mysql_error());
	if ($row = mysql_fetch_array($rs,MYSQL_ASSOC)) {
		session_start();
		$_SESSION["userid"] = $row["userid"];
		$_SESSION["fullname"] = $row["fullname"];
		$_SESSION["admin"] = $row["admin"];
		header("Location: " . getFullPath("index.php"));
		mysql_free_result($rs);
		exit;
	}
}

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('username', $_POST['username']);
$smarty->assign('opt', $OPT);
$smarty->display('login.tpl');
