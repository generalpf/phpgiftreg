<?php
/// This program is free software; you can redistribute it and/or modify
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

$error = "";

if (isset($_POST["action"])) {
	if ($_POST["action"] == "forgot") {
		$username = $_POST["username"];
		if (!get_magic_quotes_gpc()) {
			$username = addslashes($username);
		}
		
		// make sure that username is valid 
		$query = "SELECT email FROM {$OPT["table_prefix"]}users WHERE username = '$username'";
		$rs = mysql_query($query) or die("Could not query: " . mysql_error());
		if (mysql_num_rows($rs) == 0) {
			$error = "The username '" . stripslashes($username) . "' could not be found.";
			mysql_free_result($rs);
		}
		else {
			$row = mysql_fetch_array($rs,MYSQL_ASSOC);
			$email = $row["email"];
			mysql_free_result($rs);
			
			if ($email == "")
				$error = "The username '" . stripslashes($username) . "' does not have an e-mail address, so the password could not be sent.";
			else {
				$pwd = generatePassword();
				$query = "UPDATE {$OPT["table_prefix"]}users SET password = {$OPT["password_hasher"]}('$pwd') WHERE username = '$username'";
				mysql_query($query) or die("Could not query: " . mysql_error());
				mail(
					$email,
					"Gift Registry password reset",
					"Your Gift Registry account information:\r\n" . 
						"Your username is '" . $username . "' and your new password is '$pwd'.",
					"From: {$GLOBALS["OPT"]["email_from"]}\r\nReply-To: {$OPT["email_reply_to"]}\r\nX-Mailer: {$GLOBALS["OPT"]["email_xmailer"]}\r\n"
				) or die("Mail not accepted for $email");	
			}
		}
	}
}

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
if (isset($error) && $error != "") {
	$smarty->assign('error', $error);
}
$smarty->assign('action', $_POST["action"]);
$smarty->assign('username', $username);
$smarty->assign('opt', $OPT);
$smarty->display('forgot.tpl');
?>
