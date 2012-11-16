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
	<title>Gift Registry - Compose a Message</title>
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
</head>
<body>
	{include file='navbar.tpl' isadmin=$isadmin}
	
	<div class="container" style="padding-top: 60px;">
		<div class="row">
			<div class="span12">
				<form name="message" method="get" action="message.php" class="well form-horizontal">
					<input type="hidden" name="action" value="send">
					<fieldset>
						<legend>Send a message</legend>
						<div class="control-group">
							<label class="control-label" for="recipients[]">Recipients</label>
							<div class="controls">
								<select name="recipients[]" size="{$rcount}" MULTIPLE class="input-xlarge">
									{foreach from=$recipients item=row}
										<option value="{$row.userid}">{$row.fullname|escape:'htmlall'}</option>
									{/foreach}
								</select>
					 			<p class="help-block">(Hold CTRL while clicking to select multiple names.)</p>
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="msg">Message</label>
							<div class="controls">
								<textarea id="msg" name="msg" rows="5" cols="40" class="input-xlarge"></textarea>
							</div>
						</div>
						<div class="form-actions">
							<button type="submit" class="btn btn-primary">Send Message</button>
							<button type="button" onClick="document.location.href='index.php';">Cancel</button>
						</div>
					</fieldset>
				</form>
			</div>
		</div>
	</div>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
