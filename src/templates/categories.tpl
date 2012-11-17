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
	<title>Gift Registry - Manage Categories</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
</head>
<body>
	{include file='navbar.tpl' isadmin=$isadmin}

	<div class="container" style="padding-top: 60px;">

		{if isset($message)}
			<div class="row">
				<div class="span12">
					<div class="alert alert-block">{$message|escape:'htmlall'}</div>
				</div>
			</div>
		{/if}

		{if $opt.show_helptext}
			<div class="row">
				<div class="span12">
					<div class="alert alert-info">
						Here you can specify categories <strong>of your own</strong>, like &quot;Motorcycle stuff&quot; or &quot;Collectibles&quot;.  This will help you categorize your gifts.
						Warning: deleting a category will uncategorize all items that used that category.
					</div>
				</div>
			</div>
		{/if}

		<div class="row">
			<div class="span12">
				<div class="well">
					<h3>Categories</h3>
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Category</th>
								<th># Items</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$categories item=row}
								<tr>
									<td>{$row.category|escape:'htmlall'}</td>
									<td>{$row.itemsin}</td>
									<td>
										<a href="categories.php?action=edit&categoryid={$row.categoryid}#catform"><img src="images/write_obj.gif" border="0" title="Edit Category" alt="Edit Category" /></a>
										/
										<a href="categories.php?action=delete&categoryid={$row.categoryid}"><img src="images/remove.gif" border="0" title="Delete Category" alt="Delete Category" /></a>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
					<h5><a href="categories.php">Add a new category</a></h5>
				</div>
			</div>
		</div>

		<a name="catform">
		<div class="row">
			<div class="span12">
				<form name="category" method="get" action="categories.php" class="well form-horizontal">
					{if $action == "edit" || (isset($haserror) && $action == "update")}
						<input type="hidden" name="categoryid" value="{$categoryid}">
						<input type="hidden" name="action" value="update">
					{elseif $action == "" || (isset($haserror) && $action == "insert")}
						<input type="hidden" name="action" value="insert">
					{/if}
					<fieldset>
						<legend>{if $action == "edit"}Edit Category '{$category|escape:'htmlall'}'{else}Add Category{/if}</legend>
						<div class="control-group {if isset($category_error)}warning{/if}">
							<label class="control-label" for="category">Category name</label>
							<div class="controls">
								<input id="category" name="category" type="text" class="input-xlarge" value="{$category|escape:'htmlall'}" maxlength="255">
								{if isset($category_error)}
									<span class="help-inline">{$category_error|escape:'htmlall'}</span>
								{/if}
							</div>
						</div>
						<div class="form-actions">
							<button type="submit" class="btn btn-primary">{if $action == "" || $action == "insert"}Add{else}Update{/if}</button>
							<button type="button" class="btn" onClick="document.location.href='categories.php';">Cancel</button>
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
