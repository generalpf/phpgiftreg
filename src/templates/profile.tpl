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
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<script src="js/jquery.validate.min.js"></script>
	<script src="js/giftreg.js"></script>

	<script language="JavaScript" type="text/javascript">
		$(document).ready(function() {
			$("#changepwdform").validate({
				highlight: validate_highlight,
				success: validate_success,
				rules: {
					newpwd: {
						required: true,
						maxlength: 50
					},
					confpwd: {
						required: true,
						maxlength: 50,
						equalTo: "#newpwd"
					}
				},
				messages: {
					newpwd: {
						required: "Password is required.",
						maxlength: "Password must be 50 characters or less."
					},
					confpwd: {
						required: "Confirmation is required.",
						maxlength: "Confirmation must be 50 characters or less.",
						equalTo: "Passwords don't match."
					}
				}
			});
			$("#profileform").validate({
				highlight: validate_highlight,
				success: validate_success,
				rules: {
					fullname: {
						required: true,
						maxlength: 50
					},
					email: {
						required: true,
						maxlength: 255,
						email: true
					}
				},
				messages: {
					fullname: {
						required: "Full name is required.",
						maxlength: "Full name must be 50 characters or less."
					},
					email: {
						required: "E-mail address is required.",
						maxlength: "E-mail address must be 255 characters or less.",
						email: "E-mail address must be a valid address."
					}
				}
			});
		});
	</script>
	</head>
<body>
	{include file='navbar.tpl' isadmin=$isadmin}

	<div class="container" style="padding-top: 60px;">
		<div class="row">
			<div class="span8 offset2">
<form name="changepwdform" id="changepwdform" action="profile.php" method="POST" class="well form-horizontal">
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
<form name="profileform" id="profileform" action="profile.php" method="POST" class="well form-horizontal">
	<input type="hidden" name="action" value="save">
	<fieldset>
		<legend>Update Profile</legend>
		<div class="control-group">
			<label class="control-label" for="fullname">Full name</label>
			<div class="controls">
				<input type="text" id="fullname" name="fullname" class="input-xlarge" value="{$fullname|escape:'htmlall'}">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="email">E-mail address</label>
			<div class="controls">
				<input type="text" id="email" name="email" class="input-xlarge" value="{$email|escape:'htmlall'}">
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
				<textarea id="comment" name="comment" rows="5" cols="40">{$comment|escape:'htmlall'}</textarea>
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
