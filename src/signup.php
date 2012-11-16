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

if (isset($_POST["action"])) {
	if ($_POST["action"] == "signup") {
		$username = $_POST["username"];
		$fullname = $_POST["fullname"];
		$email = $_POST["email"];
		$familyid = $_POST["familyid"];
		if (!get_magic_quotes_gpc()) {
			$username = addslashes($username);
			$fullname = addslashes($fullname);
			$email = addslashes($email);
			$familyid = addslashes($familyid);
		}
		if ($familyid == "")
			$familyid = "NULL";
		
		// make sure that username isn't taken.
		$query = "SELECT userid FROM {$OPT["table_prefix"]}users WHERE username = '$username'";
		$rs = mysql_query($query) or die("Could not query: " . mysql_error());
		if (mysql_num_rows($rs) > 0) {
			$error = "The username '" . stripslashes($username) . "' is already taken.  Please choose another.";
			mysql_free_result($rs);
		}
		else {
			mysql_free_result($rs);
			
			// generate a password and insert the row.
			// NOTE: if approval is required, this password will be replaced
			// when the account is approved.
			$pwd = generatePassword();
			$query = "INSERT INTO {$OPT["table_prefix"]}users(username,fullname,password,email,approved,initialfamilyid) VALUES('$username','$fullname',{$OPT["password_hasher"]}('$pwd'),'$email'," . ($OPT["newuser_requires_approval"] ? "0" : "1") . ",$familyid)";
			mysql_query($query) or die("Could not query: " . mysql_error());
			
			if ($OPT["newuser_requires_approval"]) {
				// send the e-mails to the administrators.
				$query = "SELECT fullname, email FROM {$OPT["table_prefix"]}users WHERE admin = 1 AND email IS NOT NULL";
				$rs = mysql_query($query) or die("Could not query: " . mysql_error());
				while ($row = mysql_fetch_assoc($rs)) {
					mail(
						$row["email"],
						"Gift Registry approval request for " . stripslashes($fullname),
						stripslashes($fullname) . " <" . stripslashes($email) . "> would like you to approve him/her for access to the Gift Registry.",
						"From: {$OPT["email_from"]}\r\nReply-To: {$OPT["email_reply_to"]}\r\nX-Mailer: {$OPT["email_xmailer"]}\r\n"
					) or die("Mail not accepted for " . $row["email"]);
				}
				mysql_free_result($rs);
			}
			else {
				// we don't require approval, 
				// so immediately send them their initial password.
				// also, join them up to their initial family (if requested).
				if ($familyid != "NULL") {
					$query = "SELECT userid FROM {$OPT["table_prefix"]}users WHERE username = '$username'";
					$rs = mysql_query($query) or die("Could not query: " . mysql_error());
					$row = mysql_fetch_assoc($rs);
					$userid = $row["userid"];
					mysql_free_result($rs);
			
					$query = "INSERT INTO {$OPT["table_prefix"]}memberships(userid,familyid) VALUES($userid,$familyid)";
					echo $query;
					mysql_query($query) or die("Could not query: " . mysql_error());					
				}

				mail(
					$email,
					"Gift Registry account created",
					"Your Gift Registry account was created.\r\n" . 
						"Your username is $username and your password is $pwd.",
					"From: {$OPT["email_from"]}\r\nReply-To: {$OPT["email_reply_to"]}\r\nX-Mailer: {$OPT["email_xmailer"]}\r\n"
				) or die("Mail not accepted for $email");	
			}
		}
		
	}
}
						
$query = "SELECT familyid, familyname FROM {$OPT["table_prefix"]}families ORDER BY familyname";
$rs = mysql_query($query) or die("Could not query: " . mysql_error());
$families = array();
while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
	$families[] = $row;
}
mysql_free_result($rs);

define('SMARTY_DIR',str_replace("\\","/",getcwd()).'/includes/Smarty-3.1.12/libs/');
require_once(SMARTY_DIR . 'Smarty.class.php');
$smarty = new Smarty();
$smarty->assign('families', $families);
$smarty->assign('username', $username);
$smarty->assign('fullname', $fullname);
$smarty->assign('email', $email);
$smarty->assign('familyid', $familyid);
$smarty->assign('action', $_POST["action"]);
if (isset($error)) {
	$smarty->assign('error', $error);
}
$smarty->assign('isadmin', $_SESSION['admin']);
$smarty->assign('opt', $OPT);
$smarty->display('signup.tpl');
?>
