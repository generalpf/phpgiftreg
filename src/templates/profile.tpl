{*
This program is free software; you can redistribute it and/or modify
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*}

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Gift Registry - Update Profile</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
	<script language="JavaScript" type="text/javascript">
		function confirmPassword() {
			var theForm = document.forms["changepwd"];
			if (theForm.newpwd.value != theForm.confpwd.value) {
				alert("Passwords don't match.");
				return false;
			}
			return true;
		}
		function validateProfile() {
			var theForm = document.forms["profile"];
			if (!theForm.fullname.value.match("\\S")) {
				alert("A full name is required.");
				theForm.fullname.focus();
				return false;
			}
			if (!theForm.email.value.match("\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*")) {
				alert("The e-mail address '" + theForm.email.value + "' is not a valid address.");
				theForm.email.focus();
				return false;
			}
			return true;
		}
	</script>
	</head>
<body>
	<div class="container">
		<div class="row">
			<div class="span8 offset2">
<form name="changepwd" action="profile.php" method="POST" onSubmit="return confirmPassword();" class="well form-horizontal">
	<input type="hidden" name="action" value="changepwd">
	<fieldset>
		<legend>Change Password</legend>
		<div class="control-group">
			<label class="control-label" for="newpwd">New password</label>
			<div class="controls">
				<input type="password" id="newpwd" name="newpwd" class="input-xlarge">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="confpwd">Confirm password</label>
			<div class="controls">
				<input type="password" id="confpwd" name="confpwd" class="input-xlarge">
			</div>
		</div>
		<div class="form-actions">
			<button type="submit" class="btn btn-primary">Change Password</button>
			<button type="button" class="btn" onclick="document.location.href='index.php';">Cancel</button>
		</div>
	</fieldset>
</form>
			</div>
		</div>
		<div class="row">
			<div class="span8 offset2">
<form name="profile" action="profile.php" method="POST" onSubmit="return validateProfile();" class="well form-horizontal">
	<input type="hidden" name="action" value="save">
	<fieldset>
		<legend>Update Profile</legend>
		<div class="control-group">
			<label class="control-label" for="fullname">Full name</label>
			<div class="controls">
				<input type="text" id="fullname" name="fullname" class="input-xlarge" value="{$fullname}">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="email">E-mail address</label>
			<div class="controls">
				<input type="text" id="email" name="email" class="input-xlarge" value="{$email}">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="email_msgs">Copy on msg</label>
			<div class="controls">
				<input type="checkbox" id="email_msgs" name="email_msgs" {if $email_msgs}CHECKED{/if}>
				E-mail me a copy of every message
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="comment">Comments / shipping address / etc. (optional)</label>
			<div class="controls">
				<textarea id="comment" name="comment" rows="5" cols="40">{$comment}</textarea>
			</div>
		</div>
		<div class="form-actions">
			<button type="submit" class="btn btn-primary">Update Profile</button>
			<button type="button" class="btn" onclick="document.location.href='index.php';">Cancel</button>
		</div>
	</fieldset>
</form>
		</div>
	</div>
</div>
</body>
</html>
