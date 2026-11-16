<?php
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

/* Brings an existing database up to date with the current code.  Each file in
	migrations/ returns a function that inspects the live schema and changes
	it only if needed, so they are safe to run on every request and in any
	order relative to each other. */
function runMigrations($dbh, $opt) {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	foreach (glob(dirname(__FILE__) . "/migrations/*.php") as $file) {
		$migrate = require($file);
		$migrate($dbh, $opt);
	}
}

/* Returns the information_schema row for a column, or false if it doesn't exist. */
function getColumnInfo($dbh, $table, $column) {
	$stmt = $dbh->prepare("SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
	$stmt->bindParam(1, $table, PDO::PARAM_STR);
	$stmt->bindParam(2, $column, PDO::PARAM_STR);
	$stmt->execute();
	return $stmt->fetch();
}
?>
