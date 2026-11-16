<?php
/* Adds items.date_added.  Items that already exist get NULL rather than the
	time of the migration, since when they were really added is unknown. */
return function($dbh, $opt) {
	$items = $opt["table_prefix"] . "items";
	if (!getColumnInfo($dbh, $items, "date_added")) {
		$dbh->exec("ALTER TABLE `$items` ADD COLUMN `date_added` datetime NULL");
		$dbh->exec("ALTER TABLE `$items` MODIFY COLUMN `date_added` datetime NULL default CURRENT_TIMESTAMP");
	}
};
?>
