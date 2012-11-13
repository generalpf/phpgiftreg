{*
This program is free software; you can redistribute it and/or modify
t under the terms of the GNU General Public License as published by
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
	<title>Gift Registry - Login</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="span8 offset2">
		<form name="login" method="post" action="login.php" class="well form-horizontal">
			<fieldset>
				<legend>Gift Registry</legend>
				{if isset($username)}
					<div class="alert alert-error">Bad login.</div>
				{/if}
				<div class="control-group">
					<label class="control-label" for="username">Username</label>
					<div class="controls">
						<input id="username" name="username" type="text" class="input-xlarge" placeholder="username" />
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="password">Password</label>
					<div class="controls">
						<input id="password" name="password" type="password" class="input-xlarge" placeholder="password" />
					</div>
				</div>
				<div class="form-actions">
					<button type="submit" class="btn btn-primary">Login</button>
				</div>
			</fieldset>
		</form>
			</div>
		</div>
		<div class="row">
			<div class="span4 offset2">
				<div class="well">
					<a href="signup.php">Need an account?</a>
				</div>
			</div>
			<div class="span4">
				<div class="well">
					<a href="forgot.php">Forgot your password?</a>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
