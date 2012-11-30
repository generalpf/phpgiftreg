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
	<title>Gift Registry - Home Page for {$fullname|escape:'htmlall'}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<link href="lightbox/css/jquery.lightbox-0.5.css" rel="stylesheet">
	<script src="lightbox/js/jquery.lightbox-0.5.min.js"></script>
	
	<script language="JavaScript" type="text/javascript">
		$(document).ready(function() {
			$('a[rel=lightbox]').lightBox({
				imageLoading: 'lightbox/images/lightbox-ico-loading.gif',
				imageBtnClose: 'lightbox/images/lightbox-btn-close.gif',
				imageBtnPrev: 'lightbox/images/lightbox-btn-prev.gif',
				imageBtnNext: 'lightbox/images/lightbox-btn-next.gif'
			});
			$('a[rel=popover]').removeAttr('href').popover();
			{if $opt.confirm_item_deletes}
				$('a[rel=confirmitemdelete]').click(function(event) {
					var desc = $(this).attr('data-content');
					if (!window.confirm('Are you sure you want to delete "' + desc + '"?')) {
						event.preventDefault();
					}
				});
			{/if}
			{if $opt.shop_requires_approval}
				$('a[rel=confirmunshop]').click(function(event) {
					var fn = $(this).attr('data-content');
					if (!window.confirm('Are you sure you no longer wish to shop for ' + fn + '?')) {
						event.preventDefault();
					}
				});
			{/if}
		});
	</script>
</head>
<body>
	{include file='navbar.tpl'}

	<div class="container" style="padding-top: 60px;">
	{if isset($message)}
		<div class="row">
			<div class="span12">
				<div class="alert alert-block">{$message|escape:'htmlall'}</div>
			</div>
		</div>
	{/if}
	{if $opt.show_helptext}
		<section id="help">
	 	<div class="row">
			<div class="span12">
				<div class="alert alert-info">
				<ul>
					<li>You can click the column headers to sort by that attribute.</li>
					<li>List each item seperately on your list - do not combine items. (i.e. list each book of a 4-part series separately.)</li>
					<li>Once you've bought or decided not to buy an item, remember to return to the recipient's gift lists and mark it accordingly.</li>
					<li>If someone purchases an item on your list, click <img src="images/return.png" /> to mark it as received.</li>
				</ul>
				</div>
			</div>
		</div>
		</section>
	{/if}
	<section id="myitems">
		<div class="well">
		<div class="page-header">
			<h1>My Items</h1>
		</div>
		<table class="table table-bordered table-striped">
			<thead>
				<tr>
					<th class="colheader"><a href="index.php?mysort=description">Description</a></th>
					<th class="colheader"><a href="index.php?mysort=ranking">Ranking</a></th>
					<th class="colheader"><a href="index.php?mysort=category">Category</a></th>
					<th class="rcolheader"><a href="index.php?mysort=price">Price</a></th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$myitems item=row}
					<tr valign="top">
						<td>
							{$row.description|escape:'htmlall'}
							 {if $row.comment != ''}
								<a class="btn btn-small" rel="popover" href="#" data-placement="right" data-original-title="Comment" data-content="{$row.comment|escape:'htmlall'}">...</a>
							{/if}
							{if $row.url != ''}
								<a href="{$row.url|escape:'htmlall'}" target="_blank"><img src="images/link.png" border="0" alt="URL" title="URL"></a>
							{/if}
							{if $row.image_filename != '' && $opt.allow_images}
								<a rel="lightbox" href="{$opt.image_subdir}/{$row.image_filename}"><img src="images/image.png" border="0" alt="Image" /></a>
							{/if}
						</td>
						<td nowrap>{$row.rendered}</td>
						<td>{$row.category|default:"&nbsp;"}</td>
						<td align="right">{$row.price}</td>
						<td align="right" nowrap>
							<a href="receive.php?itemid={$row.itemid}"><img alt="Mark Item Received" src="images/return.png" border="0" title="Mark Item Received" /></a>&nbsp;
							<a href="item.php?action=edit&itemid={$row.itemid}"><img alt="Edit Item" src="images/pencil.png" border="0" title="Edit Item" /></a>&nbsp;
							<a rel="confirmitemdelete" data-content="{$row.description|escape:'htmlall'}" href="item.php?action=delete&itemid={$row.itemid}"><img alt="Delete Item" src="images/bin.png" border="0" alt="Delete" title="Delete Item" /></a>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
		{if $myitems_count > $opt.items_per_page}
			<div class="pagination">
				<ul>
					{if $offset >= $opt.items_per_page}
						<li><a href="index.php?offset={$offset - $opt.items_per_page}">&lt;</a></li>
					{/if}
					{for $i=0 to $myitems_count step $opt.items_per_page}
						<li {if $offset >= $i && $offset < $i + $opt.items_per_page}class="active"{/if}><a href="index.php?offset={$i}">{$i + $opt.items_per_page}</a></li>
					{/for}
					{if $offset + $opt.items_per_page < $myitems_count}
						<li><a href="index.php?offset={$offset + $opt.items_per_page}">&gt;</a></li>
					{/if}
				</ul>
			</div>
		{/if}
		<h5><a href="item.php?action=add">Add a new item</a></h5>
		</div>
	</section>
	<section id="otherstuff">
		<div class="row">
			<div class="span6">
				<div class="well">
				<h3>People I'm shopping for</h3>
		<table class="table table-bordered table-striped">
			<thead>
				<tr>
					<th class="colheader">Name</th>
					<th class="rcolheader">Last Updated</th>
					<th class="rcolheader"># Items</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$shoppees item=row}
					<tr>
						<td>
							<a href="shop.php?shopfor={$row.userid}">{$row.fullname|escape:'htmlall'}</a>
							{if $row.comment != ''}
								<a class="btn btn-small" rel="popover" href="#" data-placement="right" data-original-title="Comment" data-content="{$row.comment|escape:'htmlall'}">...</a>
							{/if}
						</td>
						<td align="right">{$row.list_stamp}</td>
						<td align="right">{$row.itemcount}</td>
						<td align="right" nowrap>
							{if $row.itemcount > 0}
								<a href="shop.php?shopfor={$row.userid}"><img alt="Shop for {$row.fullname|escape:'htmlall'}" src="images/store.png" border="0" title="Shop for {$row.fullname|escape:'htmlall'}"></a>
							{/if}
							{if !$row.is_unsubscribed}
								<a href="index.php?action=unsubscribe&shoppee={$row.userid}"><img alt="Unsubscribe from {$row.fullname|escape:'htmlall'}'s updates" src="images/delete.png" border="0" title="Unsubscribe from {$row.fullname|escape:'htmlall'}'s updates"></a>
							{else}
								<a href="index.php?action=subscribe&shoppee={$row.userid}"><img alt="Subscribe to {$row.fullname|escape:'htmlall'}'s updates" src="images/podcast.png" border="0" title="Subscribe to {$row.fullname|escape:'htmlall'}'s updates"></a>
							{/if}
							<a rel="confirmunshop" data-content="{$row.fullname|escape:'htmlall'}" href="index.php?action=cancel&shopfor={$row.userid}"><img src="images/bin.png" border="0" alt="Don't shop for {$row.fullname|escape:'htmlall'} anymore" title="Don't shop for {$row.fullname|escape:'htmlall'} anymore" /></a>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
			</div>
			</div>
			<div class="span6">
				<div class="well">
				<h3>People I'm not shopping for</h3>
					<table class="table table-bordered table-striped"> 
						<thead>
						<tr>
							<th class="colheader">Name</th>
							<th>&nbsp;</th>
						</tr>
						</thead>
						<tbody>
						{foreach from=$prospects item=row}
							<tr>
								<td>{$row.fullname|escape:'htmlall'}</td>
								<td align="right" nowrap>
									{if $row.pending}
										<a href="index.php?action=cancel&shopfor={$row.userid}"><img src="images/delete.png" border="0" alt="Cancel" title="Cancel" /></a>
									{else}
										<a href="index.php?action=request&shopfor={$row.userid}">
											{if $opt.shop_requires_approval}
												<img src="images/cloud-add.png" border="0" alt="Request" title="Request" />
											{else}
												<img src="images/cloud-add.png" border="0" alt="Add" title="Add" />	
											{/if}
										</a>
									{/if}
								</td>
							</tr>
						{/foreach}
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</section>
	<section id="morestuffstill">
		<div class="row">
			<div class="span6">
				<div class="well">
				<h3>Messages</h3>
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th class="colheader">Date</th>
							<th class="colheader">Sender</th>
							<th class="colheader">Message</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$messages item=row}
							<tr>
								<td>{$row.created}</td>
								<td>{$row.fullname|escape:'htmlall'}</td>
								<td>{$row.message|escape:'htmlall'}</td>
								<td align="right">
									<a href="index.php?action=ack&messageid={$row.messageid}"><img alt="Acknowledge" title="Acknowledge" src="images/delete.png" border="0"></a>
								</td>
							</tr>
						{/foreach}
					</tbody>
					</table>
					<h5><a href="message.php">Send a message</a></h5>
					</div>
				</div>
				<div class="span6">
					<div class="well">
					<h3>Upcoming events (within {$opt.event_threshold} days)</h3>
					<table class="table table-bordered table-striped">
						<thead>
						<tr>
							<th class="colheader">Name</th>
							<th class="colheader">Event</th>
							<th class="colheader">Date</th>
							<th class="colheader">Days left</th>
						</tr>
						</thead>
						<tbody>
						{foreach from=$events item=row}
							<tr>
								<td>
									{if $row.fullname == ''}
										<i>System event</i>
									{else}
										{$row.fullname|escape:'htmlall'}
									{/if}
								</td>
								<td>{$row.eventname|escape:'htmlall'}</td>
								<td>{$row.date}</td>
								<td>
									{if $row.daysleft == 0}
										<b>Today</b>
									{else}
										{$row.daysleft}
									{/if}
								</td>
							</tr>
						{/foreach}
						</tbody>
					</table>
					</div>
				</div>
			</div>
			{if $opt.shop_requires_approval || ($isadmin && $opt.newuser_requires_approval)}
				<div class="row">
					{if $opt.shop_requires_approval}
						<div class="span6">
							<div class="well">
								<h3>People who want to shop for me</h3>
								<table class="table table-bordered table-striped">
									<thead>
										<tr>	
											<th class="colheader">Name</th>
											<th>&nbsp;</th>
										</tr>
									</thead>
									<tbody>
										{foreach from=$pending item=row}
											<tr>
												<td>{$row.fullname|escape:'htmlall'}</td>
												<td align="right">
													<a href="index.php?action=approve&shopper={$row.userid}">Approve</a>&nbsp;/
													<a href="index.php?action=decline&shopper={$row.userid}">Decline</a>
												</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							</div>
						</div>
					{/if}
					{if $isadmin && $opt.newuser_requires_approval}
						<div class="span6">
							<div class="well">
								<h3>People waiting for approval</h3>
								<table class="table table-bordered table-striped">
									<thead>
										<tr>
											<th class="colheader">Name</th>
											<th class="colheader">Family</th>
											<th>&nbsp;</th>
										</tr>
									</thead>
									<tbody>
										{foreach from=$approval item=row}
											<tr>
												<td>{$row.fullname|escape:'htmlall'} &lt;<a href="mailto:{$row.email|escape:'htmlall'}">{$row.email|escape:'htmlall'}</a>&gt;</td>
												<td>{$row.familyname|escape:'htmlall'}</td>
												<td align="right">
													<a href="admin.php?action=approve&userid={$row.userid}&familyid={$row.initialfamilyid}">Approve</a>&nbsp;/
													<a href="admin.php?action=reject&userid={$row.userid}">Reject</a>
												</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							</div>
						</div>
					{/if}
				</div>
			{/if}
		</section>
	</div>
</body>
</html>
