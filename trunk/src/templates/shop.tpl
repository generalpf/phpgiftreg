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
	<title>Gift Registry - Shopping List for {$ufullname}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
    <script language="JavaScript" type="text/javascript">
		function printPage() {
			window.print();
		}
	</script>
</head>
<body>
	<div class="container">
	{if isset($message)}
		<div class="row">
			<div class="span12">
				<div class="alert alert-success">
					{$message|escape:'htmlall'}
				</div>
			</div>
		</div>
	{/if}
	<div class="row">
		<h1>Gift Registry - Shopping List for {$ufullname|escape:'htmlall'}</h1>
	</div>
	{if $opt.show_helptext}
		<div class="row">
			<div class="span12">
				<div class="alert alert-info">
					<ul>
						<li>If you intend to purchase a gift for this person, click the <img src="images/lock_co.gif"> icon.  If you end up actually purchasing it, come back and click the <img src="images/step_done.gif"> icon.  If you change your mind and don't want to buy it, come back and click the <img src="images/unlock_co.gif"> icon.</li>
						<li>If you return something you've purchased, come back and click the <img src="images/run_exc.gif"> icon.  It will remain reserved for you.</li>
						<li>Just because an item has a URL listed doesn't mean you have to buy it from there (unless the comment says so).</li>
						<li>You can click the column headers to sort by that attribute.</li>
						<li>If you see something you'd like for yourself, click the <img src="images/toolbar_replace.gif"> icon to copy it to your own list.</li>
					</ul>
				</div>
			</div>
		</div>
	{/if}
	<div class="row">
		<div class="span6 offset6">
			<div class="alert alert-info">
		<img src="images/lock_co.gif" alt="Reserve" title="Reserve"> = Reserve, <img src="images/unlock_co.gif" alt="Release" title="Release"> = Release, <img src="images/step_done.gif" alt="Purchase" title="Purchase"> = Purchase, <img src="images/run_exc.gif" alt="Return" title="Return"> = Return, <img src="images/toolbar_replace.gif" alt="I Want This Too" title="I Want This Too"> = I Want This Too
			</div>
		</div>
	</div>
	<div class="row">
		<div class="span12">
			<div class="well">
		<table>
			<thead>
			<tr>
				<th class="colheader"><a href="shop.php?shopfor={$shopfor}&sort=ranking">Rank</a></th>
				<th class="colheader"><a href="shop.php?shopfor={$shopfor}&sort=description">Description</a></th>
				<th class="colheader"><a href="shop.php?shopfor={$shopfor}&sort=category">Category</a></th>
				<th class="rcolheader"><a href="shop.php?shopfor={$shopfor}&sort=price">Price</a></th>
				<th class="colheader"><a href="shop.php?shopfor={$shopfor}&sort=source">Store/Location</a></th>
				<th class="colheader"><a href="shop.php?shopfor={$shopfor}&sort=status">Status</a></th>
				<th class="rcolheader">&nbsp;</th>
				<th class="rcolheader">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			{foreach from=$shoprows item=row}
				<tr valign="top">
					<td nowrap>{$row.rendered}</td>
					<td>
						{$row.description|escape:'htmlall'}
						{if $row.url != ''}
							<a href="{$row.url}" target="_blank"><img src="images/links_view.gif" border="0" alt="URL" title="URL"></a>
						{/if}
						{if $row.comment != ''}
							<img src="images/topic.gif" border="0" alt="{$row.comment|escape:'htmlall'}" />
						{/if}
						{if $row.image_filename != '' && $opt.allow_images}
							<img src="images/image_obj.gif" border="0" alt="Image" />
						{/if}
					</td>
					<td>{$row.category|default:"&nbsp;"}</td>
					<td align="right">{$row.price}</td>
					<td>{$row.source|escape:'htmlall'}</td>
					{if $row.quantity > 1}
						<td>
							{foreach from=$row.allocs item=alloc}
								<b>{$alloc}</b><br />
							{/foreach}
							{$row.avail} remaining.<br />
						</td>
						<td nowrap align="right">
							{if $row.avail > 0 || $row.ireserved > 0 || $row.ibought > 0}
								{if $row.ireserved > 0}
									{assign var="reservetext" value="Reserve Another"}
								{else}
									{assign var="reservetext" value="Reserve Item"}
								{/if}
								{if $row.ibought > 0}
									{assign var="purchasetext" value="Purchase Another"}
								{elseif $row.ireserved > 0}
									{assign var="purchasetext" value="Convert Reserve to Purchase"}
								{else}
									{assign var="purchasetext" value="Purchase Item"}
								{/if}
								{if $row.avail > 0}
									<a href="shop.php?action=reserve&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="{$reservetext|escape:'htmlall'}" title="{$reservetext|escape:'htmlall'}" src="images/lock_co.gif" border="0" /></a>
								{/if}
								{if $row.avail > 0 || $row.ireserved > 0}
									<a href="shop.php?action=purchase&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="{$purchasetext|escape:'htmlall'}" title="{$purchasetext|escape:'htmlall'}" src="images/step_done.gif" border="0" /></a>
								{/if}
							{/if}
							{if $row.ireserved > 0}
								<a href="shop.php?action=release&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="Release Item" title="Release Item" src="images/unlock_co.gif" border="0" /></a>
							{/if}
							{if $row.ibought > 0}
								<a href="shop.php?action=return&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="Return Item" title="Return Item" src="images/run_exc.gif" border="0" /></a>
							{/if}
						</td>
					{else}
						{if $row.rfullname == '' && $row.bfullname == ''}
							<td>
								<i>Available.</i>
							</td>
							<td nowrap align="right">
								<a href="shop.php?action=reserve&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="Reserve Item" title="Reserve Item" src="images/lock_co.gif" border="0" /></a>&nbsp;<a href="shop.php?action=purchase&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="Purchase Item" title="Purchase Item" src="images/step_done.gif" border="0" /></a>
							</td>
						{elseif $row.rfullname != ''}
							{if $row.reservedid == $userid}
								<td>
									<i><b>Reserved by you.</b></i>
								</td>
								<td align="right">
									<a href="shop.php?action=release&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="Release Item" title="Release Item" src="images/unlock_co.gif" border="0" /></a>&nbsp;<a href="shop.php?action=purchase&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="Purchase Item" title="Purchase Item" src="images/step_done.gif" border="0" /></a>
								</td>
							{else}
								<td>
									{if $opt.anonymous_purchasing}
										<i>Reserved.</i>
									{else}
										<i>Reserved by {$row.rfullname|escape:'htmlall'}.</i>
									{/if}
								</td>
								<td>&nbsp;</td>
							{/if}
						{elseif $row.bfullname != ''}
							{if $row.boughtid == $userid}
								<td>
									<i><b>Bought by you.</b></i>
								</td>
								<td align="right">
									<a href="shop.php?action=return&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="Return Item" title="Return Item" src="images/run_exc.gif" border="0" /></a>
								</td>
							{else}
								{if $opt.anonymous_purchasing}
									<td>
										<i>Bought.</i>
									</td>
									<td>&nbsp;</td>
								{else}
									<td>
										<i>Bought by {$row.bfullname|escape:'htmlall'}.</i>
									</td>
									<td>&nbsp;</td>
								{/if}
							{/if}
						{/if}
					{/if}
					<td>
						<a href="shop.php?action=copy&itemid={$row.itemid}&shopfor={$shopfor}"><img alt="I Want This Too" title="I Want This Too" src="images/toolbar_replace.gif" border="0" /></a>
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	</div>
	</div>
	</div>
	<div class="row">
		<div class="span6">
			<div class="well">
				<a onClick="printPage()" href="#">Send to printer</a>
			</div>
		</div>
	</div>
	</div>
</body>
</html>
