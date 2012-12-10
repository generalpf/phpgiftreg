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

<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</a>
			<a class="brand" href="index.php">Gift Registry</a>
			<div id="main-menu" class="nav-collapse">
				<ul id="main-menu-left" class="nav">
					<li><a href="shoplist.php">My Shopping List</a></li>
					<li><a href="mylist.php">My Items (printable)</a></li>
				</ul>
				<ul id="main-menu-right" class="nav pull-right">
					<li><a href="profile.php">Update Profile</a></li>
					<li><a href="event.php">Manage Events</a></li>
					{if $isadmin}
						<li class="dropdown">
							<a class="dropdown-toggle" href="#" data-toggle="dropdown">
								Admin
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu">
								<li><a href="users.php">Manage Users</a></li>
								<li><a href="families.php">Manage Families</a></li>
								<li><a href="categories.php">Manage Categories</a></li>
								<li><a href="ranks.php">Manage Ranks</a></li>
							</ul>
						</li>
					{/if}
					<li><a href="login.php?action=logout">Logout</a></li>
				</ul>
			</div>
		</div>
	</div>
</div>
