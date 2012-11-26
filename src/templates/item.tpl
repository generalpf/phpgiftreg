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
	<title>Gift Registry - Edit Item</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
</head>
<body>
	{include file='navbar.tpl' isadmin=$isadmin}

	<div class="container" style="padding-top: 60px;">
	{if $opt.show_helptext}
		<div class="row">
			<div class="span12">
				<div class="alert alert-info">
		Helpful hints:
		<ul>
			<li>Include a URL copied &amp; pasted from the address bar of your browser so that potential buyers can see exactly what you want.</li>
			<li>If the item description and URL can't describe exactly what you want, use the <strong>Comment</strong> area to mention anything you feel is necessary.  It doesn't mean the shopper has to buy the item from that website.</li>
			<li>If you don't know the price of the item, simply enter <strong>0</strong>.</li>
			<li>Try not to set all your items at the same ranking level.  When someone is shopping for you, they'll rely on the ranking to know what you want the most.  If you don't think there are enough levels, or the descriptions aren't adequate, ask an administrator to add or change them.</li>
			{if $opt.allow_multiples}
				<li>The quantity field indicates the number of that item that you want.  Once that many are bought or reserved, no more will be available.  If you have no limit on how many you want, enter 999 (for example).</li>
			{/if}
		</ul>
				</div>
			</div>
		</div>
	{/if}
	<div class="row">
		<div class="span8 offset2">
			<form name="item" method="POST" action="item.php" enctype="multipart/form-data" class="well form-horizontal">
				<fieldset>
					<legend>{if $action == 'edit' || (isset($haserror) && $action == 'update')}Edit Item{else}Add Item{/if}</legend>
					{if $action == 'edit' || (isset($haserror) && $action == 'update')}
						<input type="hidden" name="itemid" value="{$itemid}">
						<input type="hidden" name="action" value="update">
					{elseif $action == "add" || (isset($haserror) && $action == 'insert')}
						<input type="hidden" name="action" value="insert">
					{/if}
					<div class="control-group {if isset($description_error)}warning{/if}">
						<label class="control-label" for="description">Description</label>
						<div class="controls">
							<input id="description" name="description" type="text" value="{$description|escape:'htmlall'}" class="input-xlarge" placeholder="Description" maxlength="255">
							{if isset($description_error)}
								<span class="help-inline">{$description_error}</span>
							{/if}
						</div>
					</div>
					<div class="control-group {if isset($category_error)}warning{/if}">
						<label class="control-label" for="category">Category</label>
						<div class="controls">
							<select id="category" name="category" class="input-xlarge">
								<option value="" {if $category == NULL}SELECTED{/if}>Uncategorized</option>
								{foreach from=$categories item=row}
									<option value="{$row.categoryid}" {if $row.categoryid == $category}SELECTED{/if}>{$row.category|escape:'htmlall'}</option>
								{/foreach}
							</select>
							{if isset($category_error)}
								<span class="help-inline">{$category_error}</span>
							{/if}
						</div>
					</div>
					<div class="control-group {if isset($price_error)}warning{/if}">
						<label class="control-label" for="price">Price ({$opt.currency_symbol})</label>
						<div class="controls">
							<input id="price" name="price" type="text" value="{$price|escape:'htmlall'}" class="input-xlarge" placeholder="5.00">
							{if isset($price_error)}
								<span class="help-inline">{$price_error}</span>
							{/if}
						</div>
					</div>
					<div class="control-group {if isset($source_error)}warning{/if}">
						<label class="control-label" for="source">Store/Retailer</label>
						<div class="controls">
							<input id="source" name="source" type="text" value="{$source|escape:'htmlall'}" class="input-xlarge" maxlength="255" placeholder="Source">
							{if isset($source_error)}
								<span class="help-inline">{$source_error}</span>
							{/if}
						</div>
					</div>
					<div class="control-group {if isset($ranking_error)}warning{/if}">
						<label class="control-label" for="ranking">Ranking</label>
						<div class="controls">
							<select id="ranking" name="ranking" multiple="multiple" class="input-xlarge">
								{foreach from=$ranks item=row}
									<option value="{$row.ranking}" {if $row.ranking == $ranking}SELECTED{/if}>{$row.title}</option>
								{/foreach}
							</select>
							{if isset($ranking_error)}
								<span class="help-inline">{$ranking_error}</span>
							{/if}
						</div>
					</div>
					{if $opt.allow_multiples}
						<div class="control-group {if isset($quantity_error)}warning{/if}">
							<label class="control-label" for="quantity">Quantity</label>
							<div class="controls">
								<input id="quantity" name="quantity" type="text" value="{$quantity|escape:'htmlall'}" class="input-xlarge" maxlength="3">
								{if isset($quantity_error)}
									<span class="help-inline">{$quantity_error}</span>
								{/if}
							</div>
						</div>
					{else}
						<input type="hidden" name="quantity" value="1">
					{/if}
					<div class="control-group {if isset($url_error)}warning{/if}">
						<label class="control-label" for="url">URL (optional)</label>
						<div class="controls">
							<input id="url" name="url" type="text" value="{$url|escape:'htmlall'}" class="input-xlarge" maxlength="255">
							{if isset($url_error)}
								<span class="help-inline">{$url_error}</span>
							{/if}
						</div>
					</div>
					{if $opt.allow_images}
						<div class="control-group">
							<label class="control-label" for="image">Image (optional)</label>
							<div class="controls">
								{if $image_filename == ''}
									<input type="radio" name="image" value="none" CHECKED>
									No image.<br />
									<input type="radio" name="image" value="upload">
									Upload image:
									<input type="file" name="imagefile">
								{else}
									<input type="radio" name="image" value="remove">
									Remove existing image.<br />
									<input type="radio" name="image" value="keep" CHECKED>
									Keep existing image.<br />
									<input type="radio" name="image" value="replace">
									Replace existing image:
									<input type="file" name="imagefile">
								{/if}
							</div>
						</div>
					{/if}
					<div class="control-group">
						<label class="control-label" for="comment">Comment</label>
						<div class="controls">
							<textarea id="comment" name="comment" class="input-xlarge" rows="2" cols="40">{$comment|escape:'htmlall'}</textarea>
						</div>
					</div>
					<div class="form-actions">
						<button type="submit" class="btn btn-primary">Save</button>
						<button type="button" class="btn" onclick="document.location.href='index.php';">Cancel</button>
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
