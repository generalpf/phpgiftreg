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

if (isset($_POST["action"]) && $_POST["action"] == "signup") {
	$username = $_POST["username"];
	$fullname = $_POST["fullname"];
	$email = $_POST["email"];
	$familyid = $_POST["familyid"];
		
	// make sure that username isn't taken.
	$stmt = $smarty->dbh()->prepare("SELECT userid FROM {$opt["table_prefix"]}users WHERE username = ?");
	$stmt->bindParam(1, $username, PDO::PARAM_STR);
	$stmt->execute();
	if ($stmt->fetch()) {
		$error = "The username '" . $username . "' is already taken.  Please choose another.";
	}
	else {
		// generate a password and insert the row.
		// NOTE: if approval is required, this password will be replaced
		// when the account is approved.
		$pwd = generatePassword($opt);

		$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}users(username,fullname,password,email,approved,initialfamilyid) VALUES(?, ?, {$opt["password_hasher"]}(?), ?, ?, ?)");
		$stmt->bindParam(1, $username, PDO::PARAM_STR);
		$stmt->bindParam(2, $fullname, PDO::PARAM_STR);
		$stmt->bindParam(3, $pwd, PDO::PARAM_STR);
		$stmt->bindParam(4, $email, PDO::PARAM_STR);
		$stmt->bindValue(5, !$opt["newuser_requires_approval"], PDO::PARAM_BOOL);
		$stmt->bindParam(6, $familyid, PDO::PARAM_INT);
		$stmt->execute();
			
		if ($opt["newuser_requires_approval"]) {
			// send the e-mails to the administrators.
			$stmt = $smarty->dbh()->prepare("SELECT fullname, email FROM {$opt["table_prefix"]}users WHERE admin = 1 AND email IS NOT NULL");
			$stmt->execute();
			while ($row = $stmt->fetch()) {
				mail(
					$row["email"],
					"Gift Registry approval request for " . $fullname,
					$fullname . " <" . $email . "> would like you to approve him/her for access to the Gift Registry.",
					"From: {$opt["email_from"]}\r\nReply-To: {$opt["email_reply_to"]}\r\nX-Mailer: {$opt["email_xmailer"]}\r\n"
				) or die("Mail not accepted for " . $row["email"]);
			}
		}
		else {
			// we don't require approval, 
			// so immediately send them their initial password.
			// also, join them up to their initial family (if requested).
			if ($familyid != NULL) {
				$stmt = $smarty->dbh()->prepare("SELECT userid FROM {$opt["table_prefix"]}users WHERE username = ?");
				$stmt->bindParam(1, $username, PDO::PARAM_STR);
				$stmt->execute();
				if ($row = $stmt->fetch()) {
					$userid = $row["userid"];
			
					$stmt = $smarty->dbh()->prepare("INSERT INTO {$opt["table_prefix"]}memberships(userid,familyid) VALUES(?, ?)");
					$stmt->bindParam(1, $userid, PDO::PARAM_INT);
					$stmt->bindParam(2, $familyid, PDO::PARAM_INT);
					$stmt->execute();
				}

				mail(
					$email,
					"Gift Registry account created",
					"Your Gift Registry account was created.\r\n" . 
						"Your username is $username and your password is $pwd.",
					"From: {$opt["email_from"]}\r\nReply-To: {$opt["email_reply_to"]}\r\nX-Mailer: {$opt["email_xmailer"]}\r\n"
				) or die("Mail not accepted for $email");	
			}
		}
	}
}

$stmt = $smarty->dbh()->prepare("SELECT familyid, familyname FROM {$opt["table_prefix"]}families ORDER BY familyname");
$stmt->execute();
$families = array();
while ($row = $stmt->fetch()) {
	$families[] = $row;
}

if (count($families) == 1) {
	// default the family to the single family we have.
	$familyid = $families[0]["familyid"];
}
$smarty->assign('families', $families);
$smarty->assign('username', $username);
$smarty->assign('fullname', $fullname);
$smarty->assign('email', $email);
$smarty->assign('familyid', $familyid);
$smarty->assign('familycount', count($families));
$smarty->assign('action', $_POST["action"]);
if (isset($error)) {
	$smarty->assign('error', $error);
}
$smarty->display('signup.tpl');
?>
