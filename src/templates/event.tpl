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
	<title>Gift Registry - Manage Events</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<link href="datepicker/css/datepicker.css" rel="stylesheet">
	<script src="datepicker/js/bootstrap-datepicker.js"></script>
	<script src="js/jquery.validate.min.js"></script>
	<script src="js/giftreg.js"></script>

	<script language="JavaScript" type="text/javascript">
		$(document).ready(function() {
			$('#eventdate').datepicker();

			$('#eventform').validate({
				highlight: validate_highlight,
				success: validate_success,
				rules: {
					description: {
						required: true,
						maxlength: 255
					},
					eventdate: {
						required: true,
						"date": true
					}
				},
				messages: {
					description: {
						required: "A description of the event is required.",
						maxlength: "The description must be 255 characters or less."
					},
					eventdate: {
						required: "The event date is required.",
						"date": "The event date must be a valid date in mm/dd/yyyy format."
					}
				}
			});
		});
	</script>
</head>
<body>
	{include file='navbar.tpl' isadmin=$isadmin}

	<div class="container" style="padding-top: 60px;">
	{if isset($message)}
		<div class="row">
			<div class="span12">
    			<div class="alert alert-block">
					{$message|escape:'htmlall'}
				</div>
			</div>
		</div>
	{/if}
	{if $opt.show_helptext}
		<div class="row">
			<div class="span12">
				<div class="alert alert-info">
					Here you can specify events <strong>of your own</strong>, like your birthday or your anniversary.  When the event occurs within {$opt.event_threshold} days, an event reminder will appear in the display of everyone who shops for you.
					{if $isadmin}
						<strong>System events</strong> are events which belong to no one -- like Christmas -- and will appear on everyone's display.
					{/if}
					Marking an item as <strong>Recurring yearly</strong> will cause them to show up year after year.
				</div>
			</div>
		</div>
	{/if}
	<div class="row">
		<div class="span12">
			<div class="well">
				<h1>Events</h1>
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>Event date</th>
							<th>Description</th>
							<th>Recurring?</th>
							{if $isadmin}
								<th>System event?</th>
							{/if}
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$events item=row}
							<tr>
								<td>{$row.eventdate}</td>
								<td>{$row.description|escape:'htmlall'}</td>
								<td>{if $row.recurring}Yes{else}No{/if}</td>
								{if $isadmin}
									<td>
										{if $row.userid == ''}Yes{else}No{/if}
									</td>
								{/if}
								<td>
									<a href="event.php?action=edit&eventid={$row.eventid}"><img alt="Edit Event" src="images/pencil.png" border="0" title="Edit Event" /></a>&nbsp;<a href="event.php?action=delete&eventid={$row.eventid}"><img alt="Delete Event" src="images/bin.png" border="0" title="Delete Event" /></a>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				<h5><a href="event.php">Add a new event</a></h5>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="span8 offset2">
			<form name="eventform" id="eventform" method="get" action="event.php" class="well form-horizontal">
				<fieldset>
					<legend>Event Details</legend>
					{if $action == "edit" || (isset($haserror) && $action == "update")}
						<input type="hidden" name="eventid" value="{$eventid}">
						<input type="hidden" name="action" value="update">
					{elseif $action == "" || (isset($haserror) && $action == "insert")}
						<input type="hidden" name="action" value="insert">
					{/if}
					<div class="control-group {if isset($description_error)}warning{/if}">
						<label class="control-label" for="description">Description</label>
						<div class="controls">
							<input id="description" name="description" type="text" value="{$description|escape:'htmlall'}" class="input-xlarge" maxlength="255" placeholder="Description">
							{if isset($description_error)}
								<span class="help-inline">{$description_error}</span>
							{/if}
						</div>
					</div>
					<div class="control-group {if isset($eventdate_error)}warning{/if}">
						<label class="control-label" for="eventdate">Event date</label>
						<div class="controls">
							<input id="eventdate" name="eventdate" type="text" value="{$eventdate|escape:'htmlall'}" class="input-xlarge" placeholder="mm/dd/yyyy" data-date-format="mm/dd/yyyy">
							<p class="help-block">mm/dd/yyyy</p>
							{if isset($eventdate_error)}
								<span class="help-inline">{$eventdate_error}</span>
							{/if}
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="recurring">Recurring</label>
						<div class="controls">
							<input type="checkbox" name="recurring" {if $recurring}CHECKED{/if}>
							Recurring yearly
						</div>
					</div>
					{if $isadmin}
						<div class="control-group">
							<label class="control-label" for="systemevent">System event</label>
							<div class="controls">
								<input type="checkbox" name="systemevent" {if $systemevent}CHECKED{/if}>
								System event
							</div>
						</div>
					{/if}
					<div class="form-actions">
						<button type="submit" class="btn btn-primary">{if $action == "" || $action == "insert"}Add{else}Update{/if}</button>
						<button type="button" class="btn" onClick="document.location.href='event.php';">Cancel</button>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
</div>
</body>
</html>
