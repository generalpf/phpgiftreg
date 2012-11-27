{*
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
	<title>Gift Registry - Forgot Password</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<script src="js/jquery.validate.min.js"></script>
	<script src="js/giftreg.js"></script>

	<script language="JavaScript" type="text/javascript">
		$(document).ready(function() {
			$("#forgotform").validate({
				highlight: validate_highlight,
				success: validate_success,
				rules: {
					username: {
						required: true
					},
				},
				messages: {
					username: {
						required: "Username is required."
					}
				}
			});
		});
	</script>
</head>
<body>
	<div class="container" style="padding-top: 30px;">

	{if isset($action) && $action == "forgot" && $error == ""}
		<div class="row">
			<div class="span12">
				<div class="well">
					<p>Shortly, you will receive an e-mail with your new password.</p>
					<p>Once you've received your password, click <a href="login.php">here</a> to login.</p>
				</div>
			</div>
		</div>
	{else}
		<div class="row">
			<div class="span12">
				<form name="forgotform" id="forgotform" method="post" action="forgot.php" class="well form-horizontal">	
					<input type="hidden" name="action" value="forgot">
					<fieldset>
						<legend>Reset Your Password</legend>
						<div class="control-group {if isset($error)}warning{/if}">
							<label class="control-label" for="username">Username</label>
							<div class="controls">
								<input id="username" name="username" type="text" class="input-xlarge" value="{$username|escape:'htmlall'}">
								{if isset($error)}
									<span class="help-inline">{$error|escape:'htmlall'}</span>
								{/if}
								<p class="help-block">
									Supply your username and click Submit.<br /> 
									Your password will be reset and the new password will be sent to the e-mail address you have associated with your account.
								</p>
							</p>
						</div>
						<div class="form-actions">
							<button type="submit" class="btn btn-primary">Submit</button>
							<button type="button" class="btn" onClick="document.location.href='login.php';">Cancel</button>
						</div>
					</fieldset>
				</form>
			</div>
		</div>
	{/if}
	</div>
</body>
</html>
