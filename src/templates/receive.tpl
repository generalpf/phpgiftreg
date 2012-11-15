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
	<title>Gift Registry - Receive an Item</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="span8 offset2">
<form name="receiver" method="get" action="receive.php" class="well form-horizontal">
	<input type="hidden" name="action" value="receive">
	<input type="hidden" name="itemid" value="{$itemid}">
	<fieldset>
		<legend>Select the buyer and quantity</legend>
		<div class="control-group">
			<label class="control-label" for="buyer">Buyer</label>
			<div class="controls">
				<select id="buyer" name="buyer" class="input-xlarge">
					{foreach from=$buyers item=row}
						<option value="{$row.userid}">{$row.fullname|escape:'htmlall'}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="quantity">Quantity received (maximum of {$quantity})</label>
			<div class="controls">
				<input type="text" id="quantity" name="quantity" value="1" maxlength="3">
				<p class="help-block">Once you have received all of an item, it will be deleted.</p>
			</div>
		</div>
		<div class="form-actions">
			<button type="submit" class="btn btn-primary">Receive Item</button>
			<button type="button" onClick="document.location.href='index.php';">Cancel</button>
		</div>
	</fieldset>
</form>
			</div>
		</div>
	</div>
</body>
</html>