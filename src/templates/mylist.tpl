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
	<title>Gift Registry - My Items</title>
 	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

	<script language="JavaScript">
		function printPage() {
			window.print();
		}
	</script>
</head>
<body>
	 {include file='navbar.tpl' isadmin=$isadmin}

	 <div class="container" style="padding-top: 60px;">
	 	{if $opt.show_helptext}
			<div class="row">
				<div class="span12">
					<div class="alert alert-info">
						<ul>
							<li>You can click the column headers to sort by that attribute.</li>
							<li>Once you've bought or decided not to buy an item, remember to return to the recipient's gift lists and mark it accordingly.</li>
							<li><strong>Please login to the Gift Registry site to get the most recent version of this list.</strong></li>
							<li>For better printing results, please change your print orientation to "Landscape" mode.</li>
						</ul>
					</div>
				</div>
			</div>
		{/if}

		<div class="row">
			<div class="span12">
				<div class="well">
					<h1>My Items</h1>
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th><a href="mylist.php?sort=ranking">Ranking</a></th>
								<th><a href="mylist.php?sort=source">Source</a></th>
								<th><a href="mylist.php?sort=description">Description</a></th>
								<th><a href="mylist.php?sort=category">Category</a></th>
								<th><a href="mylist.php?sort=price">Price</a></th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$shoplist item=row}
								<tr>
									<td>{$row.rendered}</td>
									<td>{$row.source|escape:'htmlall'}</td>
									<td>{$row.description|escape:'htmlall'}</td>
									<td>{$row.category|escape:'htmlall'}</td>
									<td>{$row.price}</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
					<h5>{$itemcount} item(s), {$totalprice} total.</h5>
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

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
