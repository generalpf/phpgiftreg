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
	<title>Gift Registry - Sign Up</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
	<script language="JavaScript" type="text/javascript">
		function validateSignup() {
			field = document.signup.username;
			if (field == null || field == undefined || !field.value.match("\\S")) {
				alert("You must supply a username.");
				field.focus();
				return false;
			}
		
			field = document.signup.fullname;
			if (field == null || field == undefined || !field.value.match("\\S")) {
				alert("You must supply your full name.");
				field.focus();
				return false;
			}
		
			field = document.signup.email;
			if (!field.value.match("\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*")) {
				alert("The e-mail address '" + field.value + "' is not a valid address.");
				field.focus();
				return false;
			}
		
			return true;
		}
	</script>
</head>
<body>
	<div class="container">
		{if isset($error)}
			<div class="row">
				<div class="span8 offset2">
					<div class="alert alert-block">{$error|escape:'htmlall'}</div>
				</div>
			</div>
		{/if}
		{if isset($action) && $action == "signup" && !isset($error)}
			<div class="row">
				<div class="span8 offset2">
					<div class="well">
						<p>Thank you for signing up.</p>
						{if $opt.newuser_requires_approval}
							<p>The administrators have been informed of your request and you will receive an e-mail once they've made a decision.</p>
						{else}
							<p>Shortly, you will receive an e-mail with your initial password.</p>
						{/if}
						<p>Once you've received your password, click <a href="login.php">here</a> to login.</p>
					</div>
				</div>
			</div>
		{else}
			<div class="row">
				<div class="span8 offset2">
					<div class="well">
						<p>Complete the form below and click Submit.</p>
						{if $opt.newuser_requires_approval}
							<p>The list administrators will be notified of your request by e-mail and will approve or decline your request.
</p>
							<p>If the e-mail address you supply is valid, you will be notified once a decision is made.</p>
						{else}
							<p>If the e-mail address you supply is valid, you will shortly receive an e-mail with your initial password.</p>
						{/if}
					</div>
				</div>
			</div>

			<div class="row">
				<div class="span8 offset2">
					<form name="signup" method="post" action="signup.php" class="well form-horizontal">	
						<input type="hidden" name="action" value="signup">
						<fieldset>
							<legend>Sign Up for the Gift Registry</legend>
							<div class="control-group">
								<label class="control-label" for="username">Username</label>
								<div class="controls">
									<input id="username" name="username" type="text" class="input-xlarge" value="{$username|escape:'htmlall'}" placeholder="Username">
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="fullname">Full name</label>
								<div class="controls">
									<input id="fullname" name="fullname" type="text" class="input-xlarge" value="{$fullname|escape:'htmlall'}" placeholder="Full name">
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="email">E-mail address</label>
								<div class="controls">
									<input id="email" name="email" type="text" class="input-xlarge" value="{$email|escape:'htmlall'}" placeholder="you@somewhere.com">
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="familyid">Family</label>
								<div class="controls">
									<select name="familyid">
										{foreach from=$families item=row}
											<option value="{$row.familyid}">{$row.familyname|escape:'htmlall'}</option>
										{/foreach}
									</select>
								</div>
							</div>
							<div class="form-actions">
								<button type="submit" class="btn btn-primary" onClick="return validateSignup();">Submit</button>
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
